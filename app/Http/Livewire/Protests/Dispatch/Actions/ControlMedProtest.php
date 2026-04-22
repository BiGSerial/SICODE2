<?php

namespace App\Http\Livewire\Protests\Dispatch\Actions;

use App\Enum\ProtestJobPriority;
use App\Enum\ProtestJobStatus;
use App\Models\EvidenceFile;
use App\Models\MedProtest;
use App\Models\ProtestJob;
use App\Models\Service;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ControlMedProtest extends Component
{
    use WithFileUploads;

    /* ===================== CONTEXTO ===================== */

    public ?MedProtest $modProtest = null;
    public int $notePage = 0;

    // formulário de criação do job
    public ?string $selectedUser = null;      // owner_id (UUID do usuário)
    public string $priority = '';             // string (ex: 'normal')
    public bool $is_advance = false;          // avanço parceiro?
    public bool $need_evidence = false;       // precisa evidência obrigatória?
    public ?string $sla_due_at = null;        // prazo de retorno (SLA)
    public string $notes = '';                // instrução/comentário inicial para o executor
    public string $reason_close = '';
    public ?string $result = null;
    public array $resultOptions = [];

    // suporte UI
    public string $userSearch = '';
    public $userList = [];
    public $serviceList = [];

    // comentários da medida
    public $deleteCommentId = null;
    public string $comment = '';

    public bool $showReasonClose = false;

    /** Uploads */
    public array $filesConfig = [
        'disk'         => 'public',
        'path'         => 'protest_attachments',
        'maxSize'      => (10 * 1024), // 10MB em KB
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
        'openModProtestControl',
        'refreshComponent'       => '$refresh',
    ];

    protected array $fileValidationMessages = [
        'files.array'     => 'Selecione arquivos vÇílidos.',
        'files.max'       => 'VocÇ¦ pode anexar no mÇ­ximo 5 arquivos por vez.',
        'files.*.mimes'   => 'Formato nÇœo permitido. Tipos aceitos: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, TXT.',
        'files.*.max'     => 'Cada arquivo pode ter no mÇ­ximo 10MB.',
    ];

    /* ===================== MOUNT ===================== */

    public function mount()
    {
        // lista de serviços (pode virar filtro ou só contexto exibido)
        $this->serviceList = Service::orderBy('service')->get();

        // lista inicial de usuários disponíveis pra atribuir
        $this->userList = User::whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        // prioridade padrão
        $this->priority = ProtestJobPriority::NORMAL->value;
        $this->resultOptions = MedProtest::resultOptions();
    }


    /* ===================== ABRIR MODAL ===================== */

    public function openModProtestControl(MedProtest $modProtest)
    {
        // limpa o form sempre que abrir
        $this->resetFormForNewJob();

        // carrega tudo que a UI precisa
        $this->modProtest = $modProtest->load([
            'protest',
            'comments.user',
            'protest.notes',
            'protest.medProtests.notes',
            'EvidenceFiles',
        ]);

        $this->notePage = 0;

        $this->sla_due_at = $this->resolveSlaDefault();

        $this->userSearch = '';

        // abre modal
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'controlModProtestModal',
        ]);

        $this->resetFileUploads();
    }

    protected function resolveSlaDefault(): ?string
    {
        if (!$this->modProtest) {
            return null;
        }

        $protest = $this->modProtest->protest;

        if (!$protest) {
            return null;
        }

        if ($protest->tipoNota === 'NA') {
            $date = $protest->dtConclusaoDesej;
        } else {
            $date = $this->modProtest->dtFimMedidaDesej;
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

    protected function resetFormForNewJob(): void
    {
        $this->selectedUser     = null;
        $this->priority         = ProtestJobPriority::NORMAL->value;
        $this->is_advance       = false;
        $this->need_evidence    = false;
        $this->sla_due_at       = null;
        $this->notes            = '';
        $this->comment          = '';
        $this->deleteCommentId  = null;
        $this->reason_close     = '';
        $this->showReasonClose  = false;
        $this->result           = null;
        $this->resetFileUploads();
    }

    /* ===================== BUSCA DINÂMICA DE USUÁRIO ===================== */

    public function updatedUserSearch()
    {
        $needle = trim($this->userSearch);

        $this->userList = User::query()
            ->whereNull('deleted_at')
            ->when($needle, function ($q) use ($needle) {
                $q->where('name', 'like', '%' . $needle . '%');
            })
            ->orderBy('name')
            ->get();
    }

    /* ===================== PAGINAR NOTAS ASSOCIADAS ===================== */

    public function nextPage()
    {
        $total = $this->modProtest?->protest?->all_notes?->count() ?? 0;

        if ($this->notePage < $total - 1) {
            $this->notePage++;
        }
    }

    public function previousPage()
    {
        if ($this->notePage > 0) {
            $this->notePage--;
        }
    }

    /* ===================== COMENTÁRIOS DA MEDIDA ===================== */

    public function addCommentToMedProtest()
    {
        if (!$this->modProtest || trim($this->comment) === '') {
            return;
        }

        $newComment = $this->modProtest->Comments()->create([
            'message' => $this->comment,
            'user_id' => auth()->id(),
        ]);

        if ($newComment) {
            $this->notifyInvolvedUsers(
                "Novo comentário na medida {$this->modProtest->protest?->nota}."
            );
        }

        $this->comment = '';
        $this->emit('refreshComponent');

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Comentário adicionado com sucesso!',
        ]);
    }

    public function markCommentForDeletion($commentId)
    {
        $this->deleteCommentId = $commentId;

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Remover Comentário?',
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Remover!',
            'btnCanceltxt'  => 'Não, Cancelar',
            'action'        => 'removeCommentFromMedProtest',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum comentário removido.',
        ]);
    }

    public function removeCommentFromMedProtest()
    {
        if (!$this->modProtest || !$this->deleteCommentId) {
            return;
        }

        $comment = $this->modProtest->Comments()
            ->where('id', $this->deleteCommentId)
            ->first();

        if ($comment) {
            $comment->delete();
        }

        $this->deleteCommentId = null;

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Comentário removido com sucesso!',
        ]);

        $this->emit('refreshComponent');
    }

    protected function notifyInvolvedUsers(string $msg): void
    {
        // Aqui você pode disparar SystemNotification pros envolvidos na medida,
        // se quiser manter teu comportamento antigo.
        // Mantido vazio pra não quebrar nada agora.
    }

    /* ===================== VALIDAÇÃO DO FORM DO JOB ===================== */

    protected function validateJobForm(bool $requireOwner = true): array
    {
        $ownerRule = $requireOwner ? 'required|exists:users,id' : 'nullable|exists:users,id';

        return $this->validate([
            'selectedUser'  => $ownerRule,
            'priority'      => 'required|in:' .
                implode(',', array_map(fn ($e) => $e->value, ProtestJobPriority::cases())),
            'is_advance'    => 'boolean',
            'need_evidence' => 'boolean',
            'sla_due_at'    => 'nullable|date',
            'notes'         => 'nullable|string|max:5000',
        ]);
    }

    /* ===================== CRIAR JOB (DESPACHAR) ===================== */

    public function dispatchJob()
    {
        if (!$this->modProtest) {
            return;
        }

        $data = $this->validateJobForm();

        DB::transaction(function () use ($data) {

            $job = ProtestJob::create([
                'protest_id'     => $this->modProtest->protest_id,
                'med_protest_id' => $this->modProtest->id,

                'created_by'     => auth()->id(),
                'owner_id'       => $data['selectedUser'],

                // IMPORTANTE: salvar string, não o objeto enum
                'status'         => ProtestJobStatus::OPENED->value,
                'priority'       => ProtestJobPriority::from($data['priority'])->value,

                'is_advance'     => $data['is_advance'] ?? false,
                'need_evidence'  => $data['need_evidence'] ?? false,

                'sla_due_at'     => $data['sla_due_at'] ?? null,
                'notes'          => $data['notes'] ?? null,

                'sent_at'        => now(),
            ]);

            if ($job->owner?->onlyparner) {
                $link = route('protests.partner.view', $this->modProtest->protest?->nota);
            } else {
                $link = route('protests.services.view', $this->modProtest->protest?->nota);
            }

            // notificar responsável
            $job->owner?->notify(new SystemNotification(
                titulo: 'Nova atividade de reclamação',
                mensagem: "Você recebeu uma tarefa referente à Medida {$this->modProtest->id}.",
                link: $link,
                status: 7,
                extras: [
                    'protest_job_id' => $job->id,
                    'med_protest_id' => $this->modProtest->id,
                ]
            ));
        });

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Atividade criada e despachada!',
        ]);

        $this->closeModalAndReset();
    }

    /* ===================== ENCERRAR DIRETO ===================== */

    public function closeNow()
    {
        if (!$this->modProtest) {
            return;
        }

        // valida pra garantir que os campos obrigatórios estão OK,
        // mas permite que o responsável fique vazio (vamos assumir quem encerrou).
        $this->validateJobForm(false);

        $this->reason_close = '';
        $this->result = $this->modProtest?->result;
        $this->resetErrorBag('reason_close');
        $this->resetErrorBag('result');
        $this->showReasonClose = true;
    }

    public function cancelCloseNow(): void
    {
        $this->showReasonClose = false;
        $this->reason_close = '';
        $this->result = null;
        $this->resetErrorBag('reason_close');
        $this->resetErrorBag('result');
    }

    public function doCloseMeasureNow()
    {
        if (!$this->modProtest) {
            return;
        }

        $data = $this->validateJobForm(false);

        $this->validate([
            'reason_close' => 'required|string|max:5000',
            'result' => 'required|in:' . implode(',', MedProtest::resultOptions()),
        ]);

        DB::transaction(function () use ($data) {

            $ownerId = $data['selectedUser'] ?? $this->selectedUser ?? auth()->id();

            // cria o job
            $job = ProtestJob::create([
                'protest_id'     => $this->modProtest->protest_id,
                'med_protest_id' => $this->modProtest->id,

                'created_by'     => auth()->id(),
                'owner_id'       => $ownerId,

                // SALVANDO STRING DO ENUM
                'status'         => ProtestJobStatus::OPENED->value,
                'priority'       => ProtestJobPriority::from($data['priority'])->value,

                'is_advance'     => $data['is_advance'] ?? false,
                'need_evidence'  => $data['need_evidence'] ?? false,

                'sla_due_at'     => $data['sla_due_at'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'sent_at'        => now(),
            ]);

            // garante fluxo mínimo antes de finalizar
            $job->start();

            $job->finish([
                'finished_by' => auth()->id(),
                'reason'      => 'Concluído manualmente por ' . auth()->user()->name,
            ], $this->reason_close);

            $job->confirmJob(null, $this->result);

            // marca a medida como concluída
            $this->modProtest->update([
                'completed'     => true,
                'completed_at'  => now(),
            ]);
        });

        $this->cancelCloseNow();

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Medida encerrada e atividade registrada!',
        ]);

        $this->closeModalAndReset();
    }

    /* ===================== FECHAR MODAL / RESET ===================== */

    protected function closeModalAndReset(): void
    {
        $this->emit('refreshComponent');

        $this->dispatchBrowserEvent('hideModal', [
            'id' => 'controlModProtestModal',
        ]);

        $this->resetFormForNewJob();
        $this->modProtest  = null;
        $this->notePage    = 0;
        $this->resetFileUploads();
    }

    public function cancelChanges()
    {

    }


    public function closeModal()
    {
        $this->closeModalAndReset();
    }

    /* ===================== RECEBIDOS / ARQUIVOS ===================== */

    public function updatedFiles(): void
    {
        if (! $this->modProtest) {
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
        if (! $this->modProtest) {
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
                    $this->modProtest->protest->nota . '_' .
                    $this->modProtest->med_id . '_' .
                    uniqid() . '.' . $file->getClientOriginalExtension();

                $path = $file->storeAs(
                    $this->filesConfig['path'] . '/' . $this->modProtest->protest->nota,
                    $filename,
                    $this->filesConfig['disk']
                );

                $this->modProtest->EvidenceFiles()->create([
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
                logger()->error('Erro ao salvar recebidos: ' . $e->getMessage(), [
                    'file'        => $file instanceof TemporaryUploadedFile ? $file->getClientOriginalName() : null,
                    'medId'       => $this->modProtest->id ?? null,
                ]);

                $this->dispatch('showAlert', [
                    'type'    => 'error',
                    'message' => 'Erro ao salvar um dos arquivos. Tente novamente.',
                ]);
            }
        }

        $this->tempFiles = [];
        $this->reset('files');
        $this->refreshModProtestData();
        $this->emitSelf('refreshComponent');

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
            $this->emitSelf('refreshComponent');
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
        if (! $this->modProtest) {
            return;
        }

        $file->delete();

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Recebido removido com sucesso!',
        ]);

        $this->refreshModProtestData();
        $this->emitSelf('refreshComponent');
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

    protected function refreshModProtestData(): void
    {
        if ($this->modProtest) {
            $this->modProtest = $this->modProtest->refresh();
            $this->modProtest->load('EvidenceFiles');
        }
    }

    protected function resetFileUploads(): void
    {
        $this->tempFiles = [];
        $this->reset('files');
    }

    /* ===================== RENDER ===================== */

    public function render()
    {
        return view('livewire.protests.dispatch.actions.control-med-protest', [
            'priorityOptions' => ProtestJobPriority::cases(),
        ]);
    }
}
