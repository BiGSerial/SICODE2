<?php

namespace App\Http\Livewire\Protests\Dispatch\Actions;

use App\Models\EvidenceFile;
use App\Models\MedProtest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class UploadMedProtestFiles extends Component
{
    use WithFileUploads;

    public ?MedProtest $medProtest = null;

    /** Uploads */
    public array $filesConfig = [
        'disk'         => 'public',
        'path'         => 'protest_attachments',
        'maxSize'      => (10 * 1024),
        'allowedTypes' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt'],
    ];

    /**
     * @var TemporaryUploadedFile[]
     */
    public $tempFiles = [];

    /**
     * @var TemporaryUploadedFile[]|null
     */
    public $files = [];

    protected $listeners = [
        'openUploader',
        'refreshUploader' => '$refresh',
    ];

    protected array $fileValidationMessages = [
        'files.array'   => 'Selecione arquivos válidos.',
        'files.max'     => 'Você pode anexar no máximo 5 arquivos por vez.',
        'files.*.mimes' => 'Formato não permitido. Tipos aceitos: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, TXT.',
        'files.*.max'   => 'Cada arquivo pode ter no máximo 10MB.',
    ];

    public function openUploader(MedProtest $medProtest): void
    {
        $this->medProtest = $medProtest->load('protest', 'EvidenceFiles');
        $this->resetFileUploads();

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'uploadMedProtestFilesModal',
        ]);
    }

    public function closeModal(): void
    {
        $this->dispatchBrowserEvent('hideModal', [
            'id' => 'uploadMedProtestFilesModal',
        ]);

        $this->resetUploader();
    }

    /* ===================== RECEBIDOS / ARQUIVOS ===================== */

    public function updatedFiles(): void
    {
        if (! $this->medProtest) {
            $this->reset('files');
            return;
        }

        try {
            $this->validate([
                'files'   => 'array|max:5',
                'files.*' => 'mimes:' . implode(',', $this->filesConfig['allowedTypes']) .
                    '|max:' . $this->filesConfig['maxSize'],
            ], $this->fileValidationMessages);

            foreach ($this->files as $file) {
                if (! $file instanceof TemporaryUploadedFile) {
                    continue;
                }

                $fileName = $file->getClientOriginalName();

                foreach ($this->tempFiles as $index => $existingFile) {
                    if ($existingFile->getClientOriginalName() === $fileName) {
                        unset($this->tempFiles[$index]);
                        break;
                    }
                }

                $this->tempFiles[] = $file;
            }

            $this->tempFiles = array_values($this->tempFiles);
            $this->files     = [];
        } catch (ValidationException $e) {
            $this->emit('showAlert', [
                'type'    => 'error',
                'message' => 'Erro ao validar arquivos.',
                'errors'  => $e->errors(),
            ]);
            $this->reset('files');
            throw $e;
        }
    }

    public function saveFiles(): void
    {
        if (! $this->medProtest) {
            return;
        }

        if (empty($this->tempFiles)) {
            $this->dispatch('showAlert', [
                'type'    => 'warning',
                'message' => 'Nenhum arquivo recebido para salvar.',
            ]);
            return;
        }

        foreach ($this->tempFiles as $file) {
            try {
                if (! $file instanceof TemporaryUploadedFile) {
                    continue;
                }

                $filename = 'DESPACHO_' .
                    $this->medProtest->protest->nota . '_' .
                    $this->medProtest->med_id . '_' .
                    uniqid() . '.' . $file->getClientOriginalExtension();

                $path = $file->storeAs(
                    $this->filesConfig['path'] . '/' . $this->medProtest->protest->nota,
                    $filename,
                    $this->filesConfig['disk']
                );

                $this->medProtest->EvidenceFiles()->create([
                    'user_id'       => auth()->id(),
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name'   => $filename,
                    'disk'          => $this->filesConfig['disk'],
                    'path'          => $path,
                    'mime'          => $file->getClientMimeType(),
                    'extension'     => $file->getClientOriginalExtension(),
                    'size'          => $file->getSize(),
                    'sha256'        => hash_file('sha256', $file->getRealPath()),
                    'uploaded_at'   => now(),
                ]);
            } catch (\Throwable $e) {
                logger()->error('Erro ao salvar recebidos (medida): ' . $e->getMessage(), [
                    'file'  => $file instanceof TemporaryUploadedFile ? $file->getClientOriginalName() : null,
                    'medId' => $this->medProtest->id ?? null,
                ]);

                $this->dispatch('showAlert', [
                    'type'    => 'error',
                    'message' => 'Erro ao salvar um dos arquivos. Tente novamente.',
                ]);
            }
        }

        $this->tempFiles = [];
        $this->reset('files');
        $this->refreshMedProtestData();
        $this->emitSelf('refreshUploader');
        $this->emitUp('refreshComponent');

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Recebidos salvos com sucesso!',
        ]);
    }

    public function removeFile(int $index): void
    {
        if (isset($this->tempFiles[$index])) {
            unset($this->tempFiles[$index]);
            $this->tempFiles = array_values($this->tempFiles);
        }
    }

    public function clearAllFiles(): void
    {
        $this->tempFiles = [];
        $this->reset('files');
    }

    public function downloadFile(EvidenceFile $file)
    {
        $disk = $file->disk ?? 'public';

        if (Storage::disk($disk)->exists($file->path)) {
            return Storage::disk($disk)->download($file->path, $file->original_name);
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'Arquivo inexistente!',
            'timer'    => 5000,
        ]);
        return;
    }

    public function deleteFile(EvidenceFile $file): void
    {
        if (! $this->medProtest) {
            return;
        }

        $file->delete();

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Recebido removido com sucesso!',
        ]);

        $this->refreshMedProtestData();
        $this->emitSelf('refreshUploader');
        $this->emitUp('refreshComponent');
    }

    public function getFileIconClass(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf'                => 'bg-danger text-white',
            'doc', 'docx'        => 'bg-primary text-white',
            'xls', 'xlsx'        => 'bg-success text-white',
            'jpg', 'jpeg', 'png' => 'bg-info text-white',
            'txt'                => 'bg-secondary text-white',
            default              => 'bg-dark text-white',
        };
    }

    public function getFileIcon(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf'                => 'ri-file-pdf-fill',
            'doc', 'docx'        => 'ri-file-word-fill',
            'xls', 'xlsx'        => 'ri-file-excel-fill',
            'jpg', 'jpeg', 'png' => 'ri-image-fill',
            'txt'                => 'ri-file-text-fill',
            default              => 'ri-file-fill',
        };
    }

    public function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        if ($bytes > 1) {
            return $bytes . ' bytes';
        }
        return '0 bytes';
    }

    protected function refreshMedProtestData(): void
    {
        if ($this->medProtest) {
            $this->medProtest = $this->medProtest->refresh();
            $this->medProtest->load('EvidenceFiles', 'protest');
        }
    }

    protected function resetFileUploads(): void
    {
        $this->tempFiles = [];
        $this->reset('files');
    }

    protected function resetUploader(): void
    {
        $this->resetFileUploads();
        $this->medProtest = null;
    }

    public function render()
    {
        return view('livewire.protests.dispatch.actions.upload-med-protest-files');
    }
}
