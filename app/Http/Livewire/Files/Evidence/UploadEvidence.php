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

    /** @var array<\Livewire\TemporaryUploadedFile> */
    public $files = [];
    public $tempFiles = [];

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
        // Recebe: saveEvidences($fiveId = null)
        'saveEvidences'   => 'saveEvidences',
        'cancelEvidences' => 'cancelEvidences',
    ];

    /**
     * Agora $five é opcional. Se vier, ótimo; se não, salvamos depois
     * quando o pai enviar o ID em saveEvidences($fiveId).
     */
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

    public function updatedFiles(): void
    {
        $this->validate();

        if (!count($this->files)) {
            $this->emitUp('hasEvidence', false);
            return;
        }

        $allowed  = array_map('strtolower', $this->config['allowed_exts']);
        $maxBytes = $this->config['max_size_mb'] * 1024 * 1024;

        foreach ($this->files as $file) {
            $ext = strtolower($file->getClientOriginalExtension());

            if (!in_array($ext, $allowed, true)) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => "Extensão não permitida: {$file->getClientOriginalName()}",
                    'timer'    => 1500,
                ]);
                continue;
            }

            if ($file->getSize() > $maxBytes) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => "Tamanho excede {$this->config['max_size_mb']}MB: {$file->getClientOriginalName()}",
                    'timer'    => 1500,
                ]);
                continue;
            }

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
                    'file'          => $file,
                ];
            }
        }

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

    /** Permite setar/atualizar o FiveNote a qualquer momento. */
    public function resolveFive(int $fiveId): void
    {
        $this->five = FiveNote::query()->findOrFail($fiveId);
    }

    /** Próximo sequencial dentro da relação de UM FiveNote. */
    protected function nextSequence(FiveNote $five, $note): int
    {
        $prefix = "{$note}_{$this->origin}_{$this->type}_";

        $count = $five->EvidenceFiles()
            ->where('stored_name', 'like', $prefix.'%')
            ->count();

        return $count + 1;
    }

    /**
     * saveEvidences agora pode receber o ID do FiveNote.
     * Ex.: $emitTo('files.evidence.upload-evidence','saveEvidences', $fiveId)
     */
    public function saveEvidences(?int $fiveId = null): void
    {


        if ($fiveId !== null) {
            $this->resolveFive($fiveId);
        }

        if (!$this->five) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'FiveNote não informado',
                'html'     => '<small>Envie o ID do FiveNote ao salvar as evidências.</small>',
            ]);
            return;
        }

        if (!count($this->tempFiles)) {
            // Nada na fila: ainda assim liberamos fluxo do pai

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
                $path       = "{$dir}/{$storedName}";

                $storedPath = $t['file']->storeAs($dir, $storedName, $disk);



                if (!Storage::disk($disk)->exists($storedPath)) {
                    throw new RuntimeException("Falha ao salvar {$t['original_name']}");
                }

                // Metadados
                $fileContents = Storage::disk($disk)->get($storedPath);
                $sha256     = hash('sha256', $fileContents);
                $mime       = $t['file']->getMimeType();
                $size       = Storage::disk($disk)->size($storedPath);

                // Persiste via morphMany
                $this->five->EvidenceFiles()->create([
                    'user_id'       => Auth::id(),
                    'original_name' => $t['original_name'],
                    'stored_name'   => pathinfo($storedName, PATHINFO_FILENAME),
                    'disk'          => $disk,
                    'path'          => $storedPath,
                    'mime'          => $mime,
                    'extension'     => $t['extension'],
                    'size'          => $size,
                    'sha256'        => $sha256,
                    'uploaded_at'   => now(),
                    'origin'        => $this->origin,
                ]);

                $seq++;
            }

            DB::commit();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Evidências salvas com sucesso',
                'timer'    => 1300,
            ]);

            $this->files = [];
            $this->tempFiles = [];
            // $this->emitUp('hasEvidence', false);
            $this->emitUp('evidenceSaved');

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao salvar evidências',
                'html'     => '<small>'.e($e->getMessage()).'</small>',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.files.evidence.upload-evidence');
    }
}
