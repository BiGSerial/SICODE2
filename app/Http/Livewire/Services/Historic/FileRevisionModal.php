<?php

namespace App\Http\Livewire\Services\Historic;

use App\Models\File;
use App\Models\Production;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class FileRevisionModal extends Component
{
    use WithFileUploads;

    public int $productionId;
    public ?string $historicServiceId = null;
    public ?Production $production = null;
    public ?int $selectedFileId = null;
    public $upload;

    protected $rules = [
        'selectedFileId' => 'required|integer|exists:files,id',
        'upload' => 'required|file|max:41943',
    ];

    public function mount(Production $production, ?string $historicServiceId = null): void
    {
        $this->productionId = (int) $production->id;
        $this->historicServiceId = $historicServiceId;
        $this->production = $this->resolveProduction();
    }

    public function getFilesProperty()
    {
        $production = $this->resolveProduction();
        $serviceId = (string) ($this->historicServiceId ?: $production->service_id);

        return File::query()
            ->where('note_id', (int) $production->note_id)
            ->where('service_id', $serviceId)
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn ($file) => $file instanceof File)
            ->unique('id')
            ->values();
    }

    public function getSelectableFilesProperty()
    {
        $grouped = [];

        foreach ($this->files as $file) {
            $meta = $this->extractRevisionMeta((string) $file->file_name);
            $key = $meta['base_name'].'|'.$meta['pattern'];

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'file' => $file,
                    'meta' => $meta,
                ];
                continue;
            }

            $current = $grouped[$key];
            $currentRank = (int) $current['meta']['current_number'];
            $incomingRank = (int) $meta['current_number'];

            if ($incomingRank > $currentRank || ($incomingRank === $currentRank && $file->id > $current['file']->id)) {
                $grouped[$key] = [
                    'file' => $file,
                    'meta' => $meta,
                ];
            }
        }

        return collect($grouped)
            ->map(function ($row) {
                return [
                    'id' => $row['file']->id,
                    'file' => $row['file'],
                    'base_name' => $row['meta']['base_name'],
                    'current_label' => $row['meta']['current_label'],
                    'next_label' => $row['meta']['next_label'],
                    'current_number' => $row['meta']['current_number'],
                ];
            })
            ->sortBy('base_name')
            ->values();
    }

    public function getImageFilesProperty()
    {
        return $this->selectableFiles->filter(function (array $row) {
            /** @var File $file */
            $file = $row['file'];
            return $this->isImageExtension((string) $file->ext);
        })->values();
    }

    public function getOtherFilesProperty()
    {
        return $this->selectableFiles->reject(function (array $row) {
            /** @var File $file */
            $file = $row['file'];
            return $this->isImageExtension((string) $file->ext);
        })->values();
    }

    public function getSelectedFileProperty(): ?File
    {
        return $this->files->firstWhere('id', $this->selectedFileId);
    }

    public function getNextNameProperty(): ?string
    {
        if (!$this->selectedFile) {
            return null;
        }

        return $this->buildNextRevisionName((string) $this->selectedFile->file_name);
    }

    public function getSelectedFileMetaProperty(): ?array
    {
        if (!$this->selectedFile) {
            return null;
        }

        return $this->extractRevisionMeta((string) $this->selectedFile->file_name);
    }

    public function saveRevision(): void
    {
        $this->validate();

        $selected = $this->selectedFile;
        if (!$selected) {
            $this->addError('selectedFileId', 'Arquivo selecionado não pertence a esta produção.');
            return;
        }

        $nextName = $this->buildNextRevisionName((string) $selected->file_name);
        $extension = strtolower((string) $this->upload->getClientOriginalExtension());
        $directory = trim((string) dirname((string) $selected->path), '.');
        $storedName = $nextName . '.' . $extension;

        DB::beginTransaction();

        try {
            $path = $this->upload->storeAs($directory, $storedName);

            if (!Storage::exists($path)) {
                throw new \RuntimeException('Falha ao salvar arquivo no disco.');
            }

            $createdFile = File::create([
                'note_id' => $selected->note_id,
                'user_id' => Auth::id(),
                'service_id' => $selected->service_id ?: $this->production->service_id,
                'file_name' => $nextName,
                'original_name' => $this->upload->getClientOriginalName(),
                'path' => $path,
                'ext' => $extension,
                'suspicious' => false,
                'noexists' => false,
            ]);

            if (Schema::hasTable('fileables')) {
                $this->production->morphFiles()->syncWithoutDetaching([$createdFile->id]);
            }

            if (Schema::hasTable('file_production')) {
                $this->production->Files()->syncWithoutDetaching([$createdFile->id]);
            }

            DB::commit();

            $this->reset(['upload', 'selectedFileId']);
            $this->production->refresh();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'success',
                'title' => 'Nova revisão enviada com sucesso',
                'timer' => 1500,
            ]);
            $this->emitUp('refresh');
            $this->emitSelf('$refresh');
        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'Não foi possível salvar a revisão',
            ]);
        }
    }

    public function confirmSaveRevision(): void
    {
        $this->validate();

        $selected = $this->selectedFile;
        if (!$selected) {
            $this->addError('selectedFileId', 'Arquivo selecionado não pertence a esta produção.');
            return;
        }

        $this->dispatchBrowserEvent('confirm-file-revision-upload', [
            'componentId' => $this->id,
            'currentName' => $selected->file_name,
            'nextName' => $this->nextName,
        ]);
    }

    public function filePreviewUrl(int $fileId): ?string
    {
        $file = $this->files->firstWhere('id', $fileId);
        if (!$file || !$this->isImageExtension((string) $file->ext)) {
            return null;
        }

        try {
            if (!Storage::exists($file->path)) {
                return null;
            }

            return Storage::url($file->path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildNextRevisionName(string $fileName): string
    {
        if (preg_match('/^(.*)_Rev[-_]?(\d+)$/i', $fileName, $m)) {
            return $m[1] . '_Rev' . ((int) $m[2] + 1);
        }

        if (preg_match('/^(.*)_N(\d{3,})$/i', $fileName, $m)) {
            $next = (int) $m[2] + 1;
            return $m[1] . '_N' . str_pad((string) $next, strlen($m[2]), '0', STR_PAD_LEFT);
        }

        return $fileName . '_Rev1';
    }

    private function isImageExtension(string $ext): bool
    {
        return in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg'], true);
    }

    private function extractRevisionMeta(string $fileName): array
    {
        if (preg_match('/^(.*)_Rev[-_]?(\\d+)$/i', $fileName, $m)) {
            $current = (int) $m[2];

            return [
                'pattern' => 'rev',
                'base_name' => $m[1],
                'current_number' => $current,
                'current_label' => 'Rev'.$current,
                'next_label' => 'Rev'.($current + 1),
            ];
        }

        if (preg_match('/^(.*)_N(\\d{3,})$/i', $fileName, $m)) {
            $size = strlen($m[2]);
            $current = (int) $m[2];
            $next = str_pad((string) ($current + 1), $size, '0', STR_PAD_LEFT);

            return [
                'pattern' => 'n',
                'base_name' => $m[1],
                'current_number' => $current,
                'current_label' => 'N'.$m[2],
                'next_label' => 'N'.$next,
            ];
        }

        return [
            'pattern' => 'rev',
            'base_name' => $fileName,
            'current_number' => 0,
            'current_label' => 'Rev0',
            'next_label' => 'Rev1',
        ];
    }

    public function render()
    {
        $this->production = $this->resolveProduction();
        $files = $this->selectableFiles;
        $imageFiles = $this->imageFiles;
        $otherFiles = $this->otherFiles;
        $previews = [];

        foreach ($imageFiles as $row) {
            $imageFile = $row['file'];
            $previews[$imageFile->id] = $this->filePreviewUrl((int) $imageFile->id);
        }

        return view('livewire.services.historic.file-revision-modal', [
            'files' => $files,
            'imageFiles' => $imageFiles,
            'otherFiles' => $otherFiles,
            'previews' => $previews,
            'nextName' => $this->nextName,
            'selectedMeta' => $this->selectedFileMeta,
        ]);
    }

    private function resolveProduction(): Production
    {
        $production = Production::query()
            ->with(['Note.Files', 'Files', 'morphFiles'])
            ->findOrFail($this->productionId);

        return $production;
    }
}
