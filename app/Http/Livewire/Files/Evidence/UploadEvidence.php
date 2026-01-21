<?php

namespace App\Http\Livewire\Files\Evidence;

use App\Models\EvidenceFile;
use App\Models\FiveNote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

class UploadEvidence extends Component
{
    use WithFileUploads;

    /** @var FiveNote|null */
    public ?FiveNote $five = null;

    public ?string $type = null;
    public ?string $origin = null;

    /** @var array */
    public $files = []; // Buffer do Livewire
    public $tempFiles = []; // Lista de arquivos validados para exibição

    public array $config = [
        'disk'         => 'public',
        'base_path'    => 'evidences',
        'max_size_mb'  => 10,
        'allowed_exts' => [
            'jpg','jpeg','png','gif','bmp','svg','tiff','webp',
            'pdf','doc','docx','odt','xls','xlsx','xlsm','ods',
            'dwg','dxf','dws','dwt','dgn','rvt','rfa','skp','txt'
        ],
    ];

    protected $listeners = [
        'saveEvidences'   => 'saveEvidences',
        'cancelEvidences' => 'cancelEvidences',
    ];

    public function mount(?FiveNote $five = null, string $type, string $origin): void
    {
        $this->five   = $five;
        $this->type   = mb_strtoupper($type);
        $this->origin = mb_strtoupper($origin);

        $this->emitUp('hasEvidence', false);
    }

    protected function rules(): array
    {
        $maxKb = $this->config['max_size_mb'] * 1024;
        $mimes = implode(',', $this->config['allowed_exts']);

        return [
            'files.*' => "nullable|file|mimes:{$mimes}|max:{$maxKb}",
        ];
    }

    /**
     * Disparado automaticamente após o upload do Livewire
     */
    public function updatedFiles(): void
    {
        $this->validate();

        if (!count($this->files)) {
            $this->emitUp('hasEvidence', count($this->tempFiles) > 0);
            return;
        }

        foreach ($this->files as $file) {
            $ext = strtolower($file->getClientOriginalExtension());

            // Evitar duplicados na fila visual
            $duplicate = false;
            foreach ($this->tempFiles as $t) {
                if ($t['original_name'] === $file->getClientOriginalName()) {
                    $duplicate = true;
                    break;
                }
            }

            if (!$duplicate) {
                $this->tempFiles[] = [
                    'original_name' => $file->getClientOriginalName(),
                    'extension'     => $ext,
                    'size'          => $file->getSize(),
                    'file'          => $file, // Objeto TemporaryUploadedFile
                ];
            }
        }

        // Limpa o buffer para o próximo lote
        $this->files = [];
        $this->emitUp('hasEvidence', count($this->tempFiles) > 0);
    }

    public function removeTemp(int $index): void
    {
        if (isset($this->tempFiles[$index])) {
            unset($this->tempFiles[$index]);
            $this->tempFiles = array_values($this->tempFiles);
        }
        $this->emitUp('hasEvidence', count($this->tempFiles) > 0);
    }

    public function cancelEvidences(): void
    {
        $this->files = [];
        $this->tempFiles = [];
        $this->resetErrorBag();
        $this->emitUp('hasEvidence', false);
    }

    public function resolveFive(int $fiveId): void
    {
        $this->five = FiveNote::query()->findOrFail($fiveId);
    }

    protected function nextSequence(FiveNote $five, $note): int
    {
        $prefix = "{$note}_{$this->origin}_{$this->type}_";
        $count = $five->EvidenceFiles()
            ->where('stored_name', 'like', $prefix.'%')
            ->count();

        return $count + 1;
    }

    public function saveEvidences(?int $fiveId = null): void
    {
        if ($fiveId !== null) {
            $this->resolveFive($fiveId);
        }

        if (!$this->five) {
            $this->dispatchBrowserEvent('swal', [
                'icon'  => 'error',
                'title' => 'FiveNote não informado',
            ]);
            return;
        }

        if (!count($this->tempFiles)) {
            $this->emitUp('evidenceSaved');
            return;
        }

        DB::beginTransaction();

        try {
            $note = $this->five->Note?->note;
            $disk = $this->config['disk'];
            $base = trim($this->config['base_path'], '/');
            $dir  = "{$base}/{$this->origin}/{$this->type}";

            $seq = $this->nextSequence($this->five, $note);

            foreach ($this->tempFiles as $t) {
                $uniqueId = uniqid();
                $storedName = sprintf(
                    '%s_%s_%s_%03d_%s.%s',
                    $note ?? 'NOTE',
                    $this->origin ?? 'ORIGIN',
                    $this->type ?? 'TYPE',
                    $seq,
                    $uniqueId,
                    $t['extension']
                );

                $storedPath = $t['file']->storeAs($dir, $storedName, $disk);

                if (!Storage::disk($disk)->exists($storedPath)) {
                    throw new RuntimeException("Falha ao salvar {$t['original_name']}");
                }

                $fileContents = Storage::disk($disk)->get($storedPath);

                $this->five->EvidenceFiles()->create([
                    'user_id'       => Auth::id(),
                    'original_name' => $t['original_name'],
                    'stored_name'   => pathinfo($storedName, PATHINFO_FILENAME),
                    'disk'          => $disk,
                    'path'          => $storedPath,
                    'mime'          => $t['file']->getMimeType(),
                    'extension'     => $t['extension'],
                    'size'          => Storage::disk($disk)->size($storedPath),
                    'sha256'        => hash('sha256', $fileContents),
                    'uploaded_at'   => now(),
                    'origin'        => $this->origin,
                ]);

                $seq++;
            }

            DB::commit();

            $this->dispatchBrowserEvent('swal', [
                'icon'  => 'success',
                'title' => 'Evidências salvas com sucesso',
                'timer' => 1300,
            ]);

            $this->tempFiles = [];
            $this->emitUp('evidenceSaved');

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal', [
                'icon'  => 'error',
                'title' => 'Erro ao salvar evidências',
                'html'  => '<small>'.e($e->getMessage()).'</small>',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.files.evidence.upload-evidence');
    }
}
