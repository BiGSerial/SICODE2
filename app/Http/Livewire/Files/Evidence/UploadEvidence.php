<?php

namespace App\Http\Livewire\Files\Evidence;

use App\Models\FiveNote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

class UploadEvidence extends Component
{
    use WithFileUploads;

    public ?FiveNote $five = null;
    public ?string $type = null;
    public ?string $origin = null;

    public $files = []; // Buffer temporário do Livewire
    public $tempFiles = []; // Lista visual de arquivos prontos para salvar

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
    }

    protected function rules(): array
    {
        $maxKb = $this->config['max_size_mb'] * 1024;
        $mimes = implode(',', $this->config['allowed_exts']);
        return [ 'files.*' => "nullable|file|mimes:{$mimes}|max:{$maxKb}" ];
    }

    public function updatedFiles(): void
    {
        $this->validate();

        foreach ($this->files as $file) {
            $this->tempFiles[] = [
                'original_name' => $file->getClientOriginalName(),
                'extension'     => strtolower($file->getClientOriginalExtension()),
                'size'          => $file->getSize(),
                'file'          => $file,
            ];
        }

        $this->files = []; // Limpa o input para novos uploads
        $this->emitUp('hasEvidence', count($this->tempFiles) > 0);
    }

    public function removeTemp($index): void
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
        $this->emitUp('hasEvidence', false);
    }

    public function saveEvidences(?int $fiveId = null): void
    {
        if ($fiveId) $this->five = FiveNote::find($fiveId);
        if (!$this->five || !count($this->tempFiles)) {
            $this->emitUp('evidenceSaved');
            return;
        }

        DB::beginTransaction();
        try {
            $dir = "evidences/{$this->origin}/{$this->type}";
            $sequenceCache = [];

            foreach ($this->tempFiles as $t) {
                $storedName = $this->buildEvidenceStoredName($t, $sequenceCache);
                $path = $t['file']->storeAs($dir, $storedName, $this->config['disk']);

                $this->five->EvidenceFiles()->create([
                    'user_id'       => Auth::id(),
                    'original_name' => $t['original_name'],
                    'stored_name'   => $storedName,
                    'disk'          => $this->config['disk'],
                    'path'          => $path,
                    'mime'          => $t['file']->getMimeType(),
                    'extension'     => $t['extension'],
                    'size'          => $t['size'],
                    'sha256'        => hash('sha256', Storage::disk($this->config['disk'])->get($path)),
                    'uploaded_at'   => now(),
                    'origin'        => $this->origin,
                ]);
            }
            DB::commit();
            $this->tempFiles = [];
            $this->emitUp('evidenceSaved');
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Salvo!']);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    protected function buildEvidenceStoredName(array $tempFile, array &$sequenceCache): string
    {
        $originToken = $this->normalizeToken((string) $this->origin, 'origem');
        $numberToken = $this->resolveNumberToken();
        $prefix = "evidencia_{$originToken}_{$numberToken}";

        if (!array_key_exists($prefix, $sequenceCache)) {
            $sequenceCache[$prefix] = $this->nextSequenceForPrefix($prefix);
        } else {
            $sequenceCache[$prefix]++;
        }

        $seq = str_pad((string) $sequenceCache[$prefix], 3, '0', STR_PAD_LEFT);
        $hash = substr(hash(
            'sha256',
            ($tempFile['original_name'] ?? '').'|'.microtime(true).'|'.random_int(1000, 9999)
        ), 0, 12);
        $ext = strtolower((string) ($tempFile['extension'] ?? 'bin'));

        return "{$prefix}_{$seq}_{$hash}.{$ext}";
    }

    protected function resolveNumberToken(): string
    {
        $raw = (string) ($this->five?->note_d5
            ?? $this->five?->note?->note
            ?? $this->five?->note_id
            ?? $this->five?->id
            ?? 'sem_numero');

        return $this->normalizeToken($raw, 'sem_numero');
    }

    protected function nextSequenceForPrefix(string $prefix): int
    {
        $current = $this->five?->EvidenceFiles()
            ->where('origin', $this->origin)
            ->where('stored_name', 'like', $prefix.'_%')
            ->count() ?? 0;

        return $current + 1;
    }

    protected function normalizeToken(string $value, string $fallback): string
    {
        $normalized = Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();

        return $normalized !== '' ? $normalized : $fallback;
    }

    public function render() { return view('livewire.files.evidence.upload-evidence'); }
}
