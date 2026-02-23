<?php

namespace App\Http\Livewire\Protests\Dispatch\Actions;

use App\Enum\ProtestJobPriority;
use App\Enum\ProtestJobStatus;
use App\Models\EvidenceFile;
use App\Models\MedProtest;
use App\Models\ProtestJob;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class EditControlMedProtest extends Component
{
    use WithFileUploads;

    public ?ProtestJob $job = null;

    // form editável
    public ?string $owner_id = null;
    public string $priority = '';
    public bool $is_advance = false;
    public bool $need_evidence = false;
    public ?string $sla_due_at = null;
    public string $notes = ''; // pode ser "instrução / atualização"
    public string $reason_close = '';
    public ?string $result = null;
    public array $resultOptions = [];

    // UI aux
    public string $userSearch = '';
    public $userList = [];
    public bool $showReasonClose = false;

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
        'openJobEditor', // recebe o ID do job
        'refreshJobEditor' => '$refresh',
    ];

    protected array $fileValidationMessages = [
        'files.array'   => 'Selecione arquivos vÇ­lidos.',
        'files.max'     => 'VocÇ¦ pode anexar no mÇ­ximo 5 arquivos por vez.',
        'files.*.mimes' => 'Formato nÇœo permitido. Tipos aceitos: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, TXT.',
        'files.*.max'   => 'Cada arquivo pode ter no mÇ­ximo 10MB.',
    ];

    public function mount()
    {
        $this->userList = User::whereNull('deleted_at')
            ->orderBy('name')
            ->get();
    }

    protected function resolveSlaDefault(): ?string
    {
        $current = $this->job?->sla_due_at;

        if ($current) {
            return $current->copy()->setMinute(0)->setSecond(0)->format('Y-m-d H:i');
        }

        $medProtest = $this->job?->medProtest;
        $protest = $medProtest?->protest;

        if (!$medProtest || !$protest) {
            return null;
        }

        if ($protest->tipoNota === 'NA') {
            $date = $protest->dtConclusaoDesej;
        } else {
            $date = $medProtest->dtFimMedidaDesej;
        }

        if (!$date) {
            return null;
        }

        if ($date->lt(now())) {
            $date = now();
        }

        $allowedHours = [0, 8, 12, 18];
        $hour = $date->hour;

        $closestHour = collect($allowedHours)->first(function ($value) use ($hour) {
            return $value >= $hour;
        });

        if ($closestHour === null) {
            $closestHour = $allowedHours[array_key_first($allowedHours)];
            $date = $date->addDay();
        }

        $date = $date->copy()->setHour($closestHour)->setMinute(0)->setSecond(0);

        return $date->format('Y-m-d H:i');
    }

    public function openJobEditor(ProtestJob $job)
    {
        $this->job = $job->load('owner', 'creator', 'medProtest.protest', 'medProtest.evidenceFiles', 'events');

        $this->owner_id      = $this->job->owner_id;
        $this->priority      = $this->job->priority->value;
        $this->is_advance    = (bool)$this->job->is_advance;
        $this->need_evidence = (bool)$this->job->need_evidence;
        $this->sla_due_at    = $this->resolveSlaDefault();
        $this->notes         = $this->job->notes ?? '';
        $this->reason_close  = '';
        $this->showReasonClose = false;
        $this->result = $this->job?->medProtest?->result;
        $this->resultOptions = MedProtest::resultOptions();
        $this->resetFileUploads();

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'editProtestJobModal',
        ]);
    }

    public function updatedUserSearch()
    {
        $needle = trim($this->userSearch);

        $this->userList = User::query()
            ->whereNull('deleted_at')
            ->when(
                $needle,
                fn ($q) =>
                $q->where('name', 'like', '%' . $needle . '%')
            )
            ->orderBy('name')
            ->get();
    }

    protected function validateJobEdit(): array
    {
        return $this->validate([
            'owner_id'      => 'required|exists:users,id',
            'priority'      => 'required|in:' .
                implode(',', array_map(fn ($e) => $e->value, ProtestJobPriority::cases())),
            'is_advance'    => 'boolean',
            'need_evidence' => 'boolean',
            'sla_due_at'    => 'nullable|date',
            'notes'         => 'nullable|string|max:5000',
        ]);
    }

    /* ===================== AÇÕES PRINCIPAIS ===================== */

    // salvar alterações normais (responsável, prioridade, flags, SLA, notes)
    public function saveJob()
    {
        if (!$this->job) {
            return;
        }

        $data = $this->validateJobEdit();

        DB::transaction(function () use ($data) {

            // se mudou o responsável
            if ($this->job->owner_id !== $data['owner_id']) {
                $this->job->reassignTo($data['owner_id'], auth()->id());
            }

            $this->job->update([
                'priority'      => ProtestJobPriority::from($data['priority']),
                'is_advance'    => $data['is_advance'] ?? false,
                'need_evidence' => $data['need_evidence'] ?? false,
                'sla_due_at'    => $data['sla_due_at'] ?? null,
                'notes'         => $data['notes'] ?? null,
            ]);

            $this->job->events()->create([
                'type'        => 'updated',
                'actor_id'    => auth()->id(),
                'meta'        => [
                    'changes' => 'priority/flags/sla/notes updated',
                ],
                'occurred_at' => now(),
            ]);
        });

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Atividade atualizada!',
        ]);

        $this->emit('refreshJobEditor');
        $this->closeModalAndReset();

    }

    // reabrir (caso DONE ou CANCELED → REOPENED → ASSIGNED)
    public function reopenJob()
    {
        if (!$this->job) {
            return;
        }

        try {
            $this->job->reopen('Reaberta manualmente por ' . auth()->user()->name);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'Transição inválida para REOPENED.',
            ]);
            return;
        }

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Atividade reaberta!',
        ]);

        $this->emit('refreshJobEditor');
        $this->closeModalAndReset();

    }

    // finalizar (DONE)
    public function promptFinishReason(): void
    {
        if (!$this->job || $this->job->status?->value === ProtestJobStatus::DONE->value) {
            return;
        }

        $this->showReasonClose = true;
    }

    public function cancelFinishReason(): void
    {
        $this->showReasonClose = false;
        $this->reason_close = '';
        $this->resetErrorBag('reason_close');
    }

    public function finishJob()
    {
        if (!$this->job) {
            return;
        }

        $this->validate([
            'reason_close' => 'required|string|max:5000',
        ]);

        $this->validate([
            'result' => 'required|in:' . implode(',', MedProtest::resultOptions()),
        ]);

        try {
            $this->prepareJobForFinish();

            $this->job->finish([
                'finished_by' => auth()->id(),
                'reason'      => 'Concluído manualmente por ' . auth()->user()->name,

            ], $this->reason_close);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'Não foi possível finalizar a atividade.',
            ]);
            return;
        }

        $this->job->confirmJob(null, $this->result);

        $this->cancelFinishReason();

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Atividade marcada como concluída!',
        ]);

        $this->emit('refreshJobEditor');
        $this->closeModalAndReset();
    }

    protected function prepareJobForFinish(): void
    {
        if (!$this->job || !$this->job->status) {
            return;
        }

        $currentStatus = $this->job->status;

        if ($currentStatus === ProtestJobStatus::DONE) {
            return;
        }

        if ($currentStatus === ProtestJobStatus::CANCELED) {
            $this->job->reopen('Reaberta automaticamente para conclusão por ' . auth()->user()->name);
            $currentStatus = $this->job->status;
        }

        if (in_array($currentStatus->value, [
            ProtestJobStatus::OPENED->value,
            ProtestJobStatus::ASSIGNED->value,
            ProtestJobStatus::WAITING->value,
            ProtestJobStatus::REOPENED->value,
        ], true)) {
            $this->job->start();
        }
    }

    // cancelar
    public function cancelJob()
    {
        if (!$this->job) {
            return;
        }

        try {
            $this->job->cancel('Cancelado manualmente');
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'Não foi possível cancelar.',
            ]);
            return;
        }

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Atividade cancelada!',
        ]);

        $this->emit('refreshJobEditor');
        $this->emitUp('refreshComponent');
    }

    /* ===================== RECEBIDOS / ARQUIVOS ===================== */

    public function updatedFiles(): void
    {
        if (! $this->job?->medProtest) {
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
        if (! $this->job?->medProtest) {
            return;
        }

        if (empty($this->tempFiles)) {
            $this->dispatch('showAlert', [
                'type'    => 'warning',
                'message' => 'Nenhum arquivo recebido para salvar.',
            ]);
            return;
        }

        $medProtest = $this->job->medProtest;

        foreach ($this->tempFiles as $file) {
            try {
                if (! $file instanceof TemporaryUploadedFile) {
                    continue;
                }

                $filename = 'DESPACHO_' .
                    $medProtest->protest->nota . '_' .
                    $medProtest->med_id . '_' .
                    uniqid() . '.' . $file->getClientOriginalExtension();

                $path = $file->storeAs(
                    $this->filesConfig['path'] . '/' . $medProtest->protest->nota,
                    $filename,
                    $this->filesConfig['disk']
                );

                $medProtest->EvidenceFiles()->create([
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
                logger()->error('Erro ao salvar recebidos (ediÇðo): ' . $e->getMessage(), [
                    'file' => $file instanceof TemporaryUploadedFile ? $file->getClientOriginalName() : null,
                    'job'  => $this->job?->id,
                ]);

                $this->dispatch('showAlert', [
                    'type'    => 'error',
                    'message' => 'Erro ao salvar um dos arquivos. Tente novamente.',
                ]);
            }
        }

        $this->tempFiles = [];
        $this->reset('files');
        $this->refreshJobData();
        $this->emitSelf('refreshJobEditor');

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
        if (! $this->job?->medProtest) {
            return;
        }

        $file->delete();

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Recebido removido com sucesso!',
        ]);

        $this->refreshJobData();
        $this->emitSelf('refreshJobEditor');
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

    protected function refreshJobData(): void
    {
        if ($this->job) {
            $this->job = $this->job->refresh();
            $this->job->load('medProtest.protest', 'medProtest.evidenceFiles');
        }
    }

    protected function resetFileUploads(): void
    {
        $this->tempFiles = [];
        $this->reset('files');
    }

    public function closeEditor()
    {
        $this->dispatchBrowserEvent('hideModal', [
            'id' => 'editProtestJobModal',
        ]);

        $this->resetEditor();
    }


    protected function closeModalAndReset(): void
    {
        $this->emit('refreshComponent');

        $this->resetEditor();

        $this->dispatchBrowserEvent('hideModal', [
            'id' => 'editProtestJobModal',
        ]);


    }


    protected function resetEditor(): void
    {
        $this->reset([
            'job',
            'owner_id',
            'priority',
            'is_advance',
            'need_evidence',
            'sla_due_at',
            'notes',
            'userSearch',
            'reason_close',
            'showReasonClose',
            'tempFiles',
            'files',
            'result',
            'resultOptions',
        ]);

        $this->userList = User::whereNull('deleted_at')
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.protests.dispatch.actions.edit-control-med-protest', [
            'priorityOptions' => ProtestJobPriority::cases(),
            'status'          => $this->job?->status,
        ]);
    }
}
