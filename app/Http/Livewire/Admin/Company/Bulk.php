<?php

namespace App\Http\Livewire\Admin\Company;

use App\Exports\Admin\Company\CompanyBulkWorkbookExport;
use App\Exports\Admin\Company\Sheets\UserRegistrationErrorSheet;
use App\Models\File;
use App\Services\Admin\Company\CompanyBulkImporter;
use App\Services\Files\FileStorageService;
use Illuminate\Support\Str;
use Livewire\{Component, WithFileUploads};
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class Bulk extends Component
{
    use WithFileUploads;

    public $upload;

    public ?string $storedPath = null;

    public ?string $storedDisk = null;

    public array $summary = [];

    public array $rows = [];

    public ?string $fatal = null;

    public bool $applied = false;

    public string $filter = 'attention';

    protected $rules = [
        'upload' => 'required|file|mimes:xlsx|max:10240',
    ];

    protected $messages = [
        'upload.mimes' => 'Envie uma planilha .xlsx gerada pelo sistema.',
        'upload.max'   => 'O arquivo passa de 10 MB.',
    ];

    public function exportAll()
    {
        error_reporting(error_reporting() & ~E_DEPRECATED);

        return Excel::download(new CompanyBulkWorkbookExport(true), 'empresas-' . now()->format('Ymd-His') . '.xlsx');
    }

    public function downloadTemplate()
    {
        error_reporting(error_reporting() & ~E_DEPRECATED);

        return Excel::download(new CompanyBulkWorkbookExport(false), 'modelo-empresas-' . now()->format('Ymd-His') . '.xlsx');
    }

    public function updatedUpload(): void
    {
        $this->discard();
        $this->reset(['summary', 'rows', 'fatal', 'applied']);
        $this->validateOnly('upload');

        $stored           = app(FileStorageService::class)->putUploadedAs($this->upload, 'tmp/company-bulk', Str::uuid() . '.xlsx');
        $this->storedPath = $stored['path'];
        $this->storedDisk = $stored['disk'];

        try {
            $plan          = $this->withLocalFile(fn (string $path) => app(CompanyBulkImporter::class)->analyze($path));
            $this->summary = $plan['summary'];
            $this->rows    = collect($plan['ops'])
                ->filter(fn ($op) => !in_array($op['action'], ['ignored', 'noop'], true) || $op['errors'] || $op['warnings'])
                ->map(fn ($op) => collect($op)->only(['sheet', 'line', 'kind', 'label', 'action', 'detail', 'errors', 'warnings'])->all())
                ->take(1500)
                ->values()
                ->all();
            $this->filter = $this->summary['errors'] > 0 ? 'errors' : 'changes';
        } catch (RuntimeException $e) {
            $this->fatal = $e->getMessage();
        }
    }

    public function confirm(): void
    {
        if (!$this->storedPath || ($this->summary['errors'] ?? 1) > 0) {
            return;
        }

        try {
            $summary = $this->withLocalFile(fn (string $path) => app(CompanyBulkImporter::class)->apply($path));
        } catch (RuntimeException $e) {
            $this->fatal = $e->getMessage();

            return;
        }

        $this->discard();
        $this->reset(['upload', 'rows']);
        $this->summary = $summary;
        $this->applied = true;
        $this->emit('refresh_table_company');
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Importação concluída',
            'html'     => "{$summary['changes']} alteração(ões) gravada(s).",
            'timer'    => 3500,
        ]);
    }

    public function cancel(): void
    {
        $this->discard();
        $this->reset(['upload', 'summary', 'rows', 'fatal', 'applied']);
    }

    public function downloadIssues()
    {
        $lines = collect($this->rows)->flatMap(fn ($row) => array_merge(
            array_map(fn ($m) => [$row['sheet'], $row['line'], $row['label'], 'Erro', $m], $row['errors']),
            array_map(fn ($m) => [$row['sheet'], $row['line'], $row['label'], 'Aviso', $m], $row['warnings']),
        ))->all();

        error_reporting(error_reporting() & ~E_DEPRECATED);

        $export = new class ($lines) implements WithMultipleSheets {
            public function __construct(private readonly array $lines)
            {
            }

            public function sheets(): array
            {
                return [new UserRegistrationErrorSheet('Inconsistencias', ['Aba', 'Linha', 'Item', 'Tipo', 'Mensagem'], $this->lines)];
            }
        };

        return Excel::download($export, 'inconsistencias-empresas-' . now()->format('Ymd-His') . '.xlsx');
    }

    public function getVisibleRowsProperty(): array
    {
        return collect($this->rows)->filter(fn ($row) => match ($this->filter) {
            'errors'  => $row['errors'] !== [],
            'changes' => in_array($row['action'], ['create', 'update', 'remove'], true) && !$row['errors'],
            'warn'    => $row['warnings'] !== [],
            default   => true,
        })->take(300)->values()->all();
    }

    public function render()
    {
        return view('livewire.admin.company.bulk');
    }

    private function withLocalFile(callable $callback): mixed
    {
        $storage = app(FileStorageService::class);
        $file    = new File(['path' => $this->storedPath, 'disk' => $this->storedDisk]);
        $local   = $storage->temporaryLocalCopy($file);

        if (!$local) {
            throw new RuntimeException('O arquivo enviado expirou. Envie a planilha novamente.');
        }

        try {
            return $callback($local);
        } finally {
            @unlink($local);
        }
    }

    private function discard(): void
    {
        if ($this->storedPath) {
            app(FileStorageService::class)->delete(new File(['path' => $this->storedPath, 'disk' => $this->storedDisk]));
        }

        $this->storedPath = null;
        $this->storedDisk = null;
    }
}
