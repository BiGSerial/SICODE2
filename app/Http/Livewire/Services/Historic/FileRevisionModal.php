<?php

namespace App\Http\Livewire\Services\Historic;

use App\Helpers\SelectOptions;
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

    public bool $isSingleton = false;
    public int $productionId = 0;
    public ?string $historicServiceId = null;
    public ?Production $production = null;
    public ?int $selectedFileId = null;
    public ?string $uploadType = null;
    public bool $appendSheets = false;
    public bool $prependSheets = false;
    public $upload;
    public $newUploads = [];

    protected $listeners = [
        'openFileRevisionModal' => 'loadAndOpen',
    ];

    protected $rules = [
        'upload' => 'nullable|file|max:41943',
        'newUploads.*' => 'nullable|file|max:41943',
    ];

    public function mount(?Production $production = null, ?string $historicServiceId = null, bool $isSingleton = false): void
    {
        $this->isSingleton = $isSingleton;
        if ($production?->exists) {
            $this->productionId = (int) $production->id;
            $this->historicServiceId = $historicServiceId;
        }
    }

    public function loadAndOpen(int $productionId, string $historicServiceId): void
    {
        $this->productionId = $productionId;
        $this->historicServiceId = $historicServiceId;
        $this->resetModalState();
        $this->production = $this->resolveProduction();
        $this->dispatchBrowserEvent('show-file-revision-modal-singleton');
    }

    public function getFilesProperty()
    {
        if (!$this->productionId) {
            return collect();
        }
        $production = $this->resolveProduction();
        $serviceId = $this->targetServiceId($production);

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

    public function getUploadTypeOptionsProperty(): array
    {
        return SelectOptions::getProductionFilesType();
    }

    public function getPendingUploadsProperty(): array
    {
        return $this->normalizedNewUploads();
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
        if ($this->isNewUploadMode()) {
            $this->saveAsNewFiles();
            return;
        }

        if ($this->appendSheets) {
            $this->saveAsAdditionalSheets();
            return;
        }

        $this->validate([
            'selectedFileId' => 'required|integer|exists:files,id',
            'upload' => 'required|file|max:41943',
        ]);

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
                'service_id' => $this->targetServiceId($this->production),
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

            $this->finishSuccess('Nova revisão enviada com sucesso');
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
        if ($this->isNewUploadMode()) {
            $this->validate([
                'uploadType' => 'required|string',
                'newUploads' => 'required|array|min:1',
                'newUploads.*' => 'required|file|max:41943',
            ]);

            $this->dispatchBrowserEvent('confirm-file-revision-upload', [
                'componentId' => $this->id,
                'currentName' => 'Novo arquivo',
                'nextName' => 'Criar novo grupo por tipo '.$this->uploadType,
                'mode' => 'new',
            ]);

            return;
        }

        if ($this->appendSheets) {
            $this->validate([
                'selectedFileId' => 'required|integer|exists:files,id',
                'newUploads' => 'required|array|min:1',
                'newUploads.*' => 'required|file|max:41943',
            ]);

            $uploads = $this->normalizedNewUploads();

            $selected = $this->selectedFile;
            if (!$selected) {
                $this->addError('selectedFileId', 'Arquivo selecionado não pertence a esta produção.');
                return;
            }

            $this->dispatchBrowserEvent('confirm-file-revision-upload', [
                'componentId' => $this->id,
                'currentName' => $selected->file_name,
                'nextName' => 'Adicionar '.count($uploads).' folha(s)',
                'mode' => 'append',
            ]);

            return;
        }

        $this->validate([
            'selectedFileId' => 'required|integer|exists:files,id',
            'upload' => 'required|file|max:41943',
        ]);

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

    public function updatedSelectedFileId(): void
    {
        $this->appendSheets = false;
        $this->prependSheets = false;
        $this->uploadType = null;
        $this->reset(['upload', 'newUploads']);
    }

    public function toggleSelectedFile(int $fileId): void
    {
        if ((int) $this->selectedFileId === $fileId) {
            $this->selectedFileId = null;
            $this->appendSheets = false;
            $this->prependSheets = false;
            $this->reset(['upload', 'newUploads']);

            return;
        }

        $this->selectedFileId = $fileId;
    }

    public function updatedAppendSheets(): void
    {
        $this->prependSheets = false;
        $this->reset(['upload', 'newUploads']);
    }

    public function removePendingUpload(int $index): void
    {
        $uploads = $this->normalizedNewUploads();
        if (!isset($uploads[$index])) {
            return;
        }

        if (method_exists($uploads[$index], 'delete')) {
            try {
                $uploads[$index]->delete();
            } catch (\Throwable $e) {
                // Ignore cleanup failure of temporary file and keep flow.
            }
        }

        unset($uploads[$index]);
        $this->newUploads = array_values($uploads);
    }

    private function isNewUploadMode(): bool
    {
        return empty($this->selectedFileId);
    }

    private function saveAsNewFiles(): void
    {
        $this->validate([
            'uploadType' => 'required|string',
            'newUploads' => 'required|array|min:1',
            'newUploads.*' => 'required|file|max:41943',
        ]);

        if (!is_array($this->newUploads)) {
            $this->newUploads = array_filter([$this->newUploads]);
        }

        $uploads = $this->normalizedNewUploads();

        $production = $this->resolveProduction();
        $targetServiceId = $this->targetServiceId($production);
        $serviceAbrev = mb_strtoupper(substr((string) optional($this->resolveServiceForContext($production))->service, 0, 4));
        $prefix = $this->uploadType.'_'.$serviceAbrev.'_'.$production->Note->note;

        $baseExists = File::query()
            ->where('note_id', (int) $production->note_id)
            ->where('service_id', $targetServiceId)
            ->where('file_name', 'like', $prefix.'_F%_Rev%')
            ->exists();

        if ($baseExists) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Já existe arquivo deste tipo. Selecione-o para adicionar folhas.',
            ]);
            return;
        }

        DB::beginTransaction();

        try {
            $total = count($uploads);
            foreach ($uploads as $idx => $uploadedFile) {
                $sheet = $idx + 1;
                $fileName = $prefix.'_F'.str_pad((string) $sheet, 2, '0', STR_PAD_LEFT)
                    .'-'.str_pad((string) $total, 2, '0', STR_PAD_LEFT)
                    .'_Rev0';
                $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
                $path = $uploadedFile->storeAs('/arquivos/'.$this->uploadType, $fileName.'.'.$extension);

                if (!Storage::exists($path)) {
                    throw new \RuntimeException('Falha ao salvar arquivo no disco.');
                }

                $createdFile = File::create([
                    'note_id' => $production->note->id,
                    'user_id' => Auth::id(),
                    'service_id' => $targetServiceId,
                    'file_name' => $fileName,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'path' => $path,
                    'ext' => $extension,
                    'suspicious' => false,
                    'noexists' => false,
                ]);

                $this->associateFileToProduction($createdFile);
            }

            DB::commit();
            $this->finishSuccess('Novo(s) arquivo(s) enviado(s) com sucesso');
            $this->emitUp('refreshLists');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'Não foi possível salvar os novos arquivos',
            ]);
        }
    }

    private function saveAsAdditionalSheets(): void
    {
        $this->validate([
            'selectedFileId' => 'required|integer|exists:files,id',
            'newUploads' => 'required|array|min:1',
            'newUploads.*' => 'required|file|max:41943',
        ]);

        if (!is_array($this->newUploads)) {
            $this->newUploads = array_filter([$this->newUploads]);
        }

        $uploads = $this->normalizedNewUploads();

        $selected = $this->selectedFile;
        if (!$selected) {
            $this->addError('selectedFileId', 'Arquivo selecionado não pertence a esta produção.');
            return;
        }

        $sheetMeta = $this->extractSheetMeta((string) $selected->file_name);
        if (!$sheetMeta) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Arquivo selecionado não segue o padrão de folhas.',
            ]);
            return;
        }

        $groupFiles = File::query()
            ->where('note_id', (int) $selected->note_id)
            ->where('service_id', $this->targetServiceId($this->production))
            ->where('file_name', 'like', $sheetMeta['prefix'].'_F%_Rev'.$sheetMeta['rev'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($groupFiles->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Não foi possível localizar o grupo de folhas.',
            ]);
            return;
        }

        $existingCount = $groupFiles->count();
        $incomingCount = count($uploads);
        $totalSheets = $existingCount + $incomingCount;
        $prepend = $this->prependSheets;

        DB::beginTransaction();

        try {
            foreach ($groupFiles as $idx => $file) {
                $sheetNo = $prepend ? ($incomingCount + $idx + 1) : ($idx + 1);
                $newName = $sheetMeta['prefix']
                    .'_F'.str_pad((string) $sheetNo, 2, '0', STR_PAD_LEFT)
                    .'-'.str_pad((string) $totalSheets, 2, '0', STR_PAD_LEFT)
                    .'_Rev'.$sheetMeta['rev'];

                $this->renameStoredFile($file, $newName);
            }

            $directory = trim((string) dirname((string) $selected->path), '.');
            foreach ($uploads as $idx => $uploadedFile) {
                $sheetNo = $prepend ? ($idx + 1) : ($existingCount + $idx + 1);
                $newName = $sheetMeta['prefix']
                    .'_F'.str_pad((string) $sheetNo, 2, '0', STR_PAD_LEFT)
                    .'-'.str_pad((string) $totalSheets, 2, '0', STR_PAD_LEFT)
                    .'_Rev'.$sheetMeta['rev'];
                $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
                $path = $uploadedFile->storeAs($directory, $newName.'.'.$extension);

                if (!Storage::exists($path)) {
                    throw new \RuntimeException('Falha ao salvar nova folha no disco.');
                }

                $createdFile = File::create([
                    'note_id' => $selected->note_id,
                    'user_id' => Auth::id(),
                    'service_id' => $this->targetServiceId($this->production),
                    'file_name' => $newName,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'path' => $path,
                    'ext' => $extension,
                    'suspicious' => false,
                    'noexists' => false,
                ]);

                $this->associateFileToProduction($createdFile);
            }

            DB::commit();
            $this->finishSuccess('Folhas adicionadas e grupo renumerado com sucesso');
            $this->emitUp('refreshLists');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'Não foi possível adicionar as folhas',
            ]);
        }
    }

    private function finishSuccess(string $title): void
    {
        $this->resetModalState();
        $this->production->refresh();
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => $title,
            'timer' => 1500,
        ]);
        $this->emitUp('refresh');
        $this->emitUp('refreshHistoric');
        $this->emitUp('$refresh');
        $this->emitSelf('$refresh');
        $this->emitUp('refreshLists');
        $modalId = $this->isSingleton
            ? 'fileRevisionModalSingleton'
            : 'fileRevisionModal-'.$this->productionId;
        $this->dispatchBrowserEvent('close-file-revision-modal', [
            'modalId' => $modalId,
        ]);
    }

    private function resetModalState(): void
    {
        $this->reset(['upload', 'newUploads', 'selectedFileId', 'uploadType', 'appendSheets', 'prependSheets']);
        $this->resetValidation();
        $this->resetErrorBag();
    }

    private function extractSheetMeta(string $fileName): ?array
    {
        if (!preg_match('/^(.*)_F(\d{2})-(\d{2})_Rev(\d+)$/i', $fileName, $m)) {
            return null;
        }

        return [
            'prefix' => $m[1],
            'sheet' => (int) $m[2],
            'total' => (int) $m[3],
            'rev' => (int) $m[4],
        ];
    }

    private function renameStoredFile(File $file, string $newName): void
    {
        if ((string) $file->file_name === $newName) {
            return;
        }

        $extension = strtolower((string) $file->ext);
        $directory = trim((string) dirname((string) $file->path), '.');
        $newPath = ltrim($directory ? $directory.'/' : '', '/').$newName.'.'.$extension;

        if (!Storage::exists((string) $file->path)) {
            throw new \RuntimeException('Arquivo base não encontrado para renomeação.');
        }

        if ((string) $file->path !== $newPath && !Storage::move((string) $file->path, $newPath)) {
            throw new \RuntimeException('Falha ao renomear arquivo base no disco.');
        }

        $file->file_name = $newName;
        $file->path = $newPath;
        $file->save();
    }

    private function associateFileToProduction(File $file): void
    {
        if (Schema::hasTable('fileables')) {
            $this->production->morphFiles()->syncWithoutDetaching([$file->id]);
        }

        if (Schema::hasTable('file_production')) {
            $this->production->Files()->syncWithoutDetaching([$file->id]);
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

    private function normalizedNewUploads(): array
    {
        if (is_array($this->newUploads)) {
            return array_values(array_filter($this->newUploads));
        }

        if (!$this->newUploads) {
            return [];
        }

        return [$this->newUploads];
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
        if ($this->productionId) {
            $this->production = $this->resolveProduction();
        }
        $files = $this->productionId ? $this->selectableFiles : collect();
        $imageFiles = $this->productionId ? $this->imageFiles : collect();
        $otherFiles = $this->productionId ? $this->otherFiles : collect();
        $previews = [];

        foreach ($imageFiles as $row) {
            $imageFile = $row['file'];
            $previews[$imageFile->id] = $this->filePreviewUrl((int) $imageFile->id);
        }

        return view('livewire.services.historic.file-revision-modal', [
            'isSingleton'      => $this->isSingleton,
            'production'       => $this->production,
            'selectedFileId'   => $this->selectedFileId,
            'appendSheets'     => $this->appendSheets,
            'prependSheets'    => $this->prependSheets,
            'uploadType'       => $this->uploadType,
            'files'            => $files,
            'imageFiles'       => $imageFiles,
            'otherFiles'       => $otherFiles,
            'previews'         => $previews,
            'nextName'          => $this->productionId ? $this->nextName : null,
            'selectedMeta'      => $this->productionId ? $this->selectedFileMeta : null,
            'uploadTypeOptions' => $this->uploadTypeOptions,
        ]);
    }

    private function resolveProduction(): Production
    {
        $production = Production::query()
            ->with(['Note.Files', 'Files', 'morphFiles'])
            ->findOrFail($this->productionId);

        return $production;
    }

    private function targetServiceId(?Production $production = null): string
    {
        $production = $production ?: $this->production ?: $this->resolveProduction();

        return (string) ($this->historicServiceId ?: $production->service_id);
    }

    private function resolveServiceForContext(Production $production)
    {
        if (!$this->historicServiceId || (string) $this->historicServiceId === (string) $production->service_id) {
            return $production->Service;
        }

        return \App\Models\Service::query()
            ->where('uuid', (string) $this->historicServiceId)
            ->first();
    }
}
