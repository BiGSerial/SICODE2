<?php

namespace App\Http\Livewire\Protests\Dispatch\Actions;

use App\Enum\ProtestJobPriority;
use App\Enum\ProtestJobStatus;
use App\Models\MedProtest;
use App\Models\ProtestJob;
use App\Models\Service;
use App\Models\User;
use App\Notifications\SystemNotification;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ControlMedProtest extends Component
{
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

    // suporte UI
    public string $userSearch = '';
    public $userList = [];
    public $serviceList = [];

    // comentários da medida
    public $deleteCommentId = null;
    public string $comment = '';

    // flag pra confirmar encerramento imediato
    public bool $pendingCloseNow = false;

    protected $listeners = [
        'openModProtestControl',
        'refreshComponent'       => '$refresh',
        'confirmCloseMeasureNow' => 'doCloseMeasureNow',
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
            'protest.allNotes', // precisa do accessor/método no model Protest
        ]);

        $this->notePage = 0;

        // abre modal
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'controlModProtestModal',
        ]);
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
        $this->pendingCloseNow  = false;
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
        $total = $this->modProtest?->protest?->allNotes?->count() ?? 0;

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

    protected function validateJobForm(): array
    {
        return $this->validate([
            'selectedUser'  => 'required|exists:users,id',
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
            ]);

            // notificar responsável
            $job->owner?->notify(new SystemNotification(
                titulo: 'Nova atividade de reclamação',
                mensagem: "Você recebeu uma tarefa referente à Medida {$this->modProtest->id}.",
                link: route('protests.dispatch.view', $this->modProtest->protest?->nota),
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

    /**
     * Passo 1: botão "Encerrar agora" chama isso.
     * A gente só valida e dispara o alerta de confirmação.
     */
    public function closeNow()
    {
        if (!$this->modProtest) {
            return;
        }

        // valida pra garantir que os campos obrigatórios estão OK
        $this->validateJobForm();

        $this->pendingCloseNow = true;

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Encerrar agora?',
            'msg'           => "Isso vai encerrar a medida e registrar a atividade como concluída imediatamente.",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Encerrar!',
            'btnCanceltxt'  => 'Não, Cancelar',
            'action'        => 'confirmCloseMeasureNow',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma medida encerrada.',
        ]);
    }

    /**
     * Passo 2: se o usuário confirmar no swal, o front dispara
     * o listener 'confirmCloseMeasureNow', que cai aqui.
     */
    public function doCloseMeasureNow()
    {
        if (!$this->modProtest || !$this->pendingCloseNow) {
            return;
        }

        $data = $this->validateJobForm();

        DB::transaction(function () use ($data) {

            // cria o job já finalizado, atribuído a mim
            $job = ProtestJob::create([
                'protest_id'     => $this->modProtest->protest_id,
                'med_protest_id' => $this->modProtest->id,

                'created_by'     => auth()->id(),
                'owner_id'       => auth()->id(),
                'closed_by'      => auth()->id(),

                // SALVANDO STRING DO ENUM
                'status'         => ProtestJobStatus::DONE->value,
                'priority'       => ProtestJobPriority::from($data['priority'])->value,

                'is_advance'     => $data['is_advance'] ?? false,
                'need_evidence'  => $data['need_evidence'] ?? false,

                'sla_due_at'     => $data['sla_due_at'] ?? null,
                'notes'          => $data['notes'] ?? null,

                'finished_at'    => now(),
                'closed_at'      => now(),
            ]);

            // marca a medida como concluída
            $this->modProtest->update([
                'completed'     => true,
                'completed_at'  => now(),
            ]);

            // registra evento explícito de término
            $job->events()->create([
                'type'        => 'status_changed',
                'actor_id'    => auth()->id(),
                'meta'        => [
                    'from' => null,
                    'to'   => ProtestJobStatus::DONE->value,
                ],
                'occurred_at' => now(),
            ]);
        });

        $this->pendingCloseNow = false;

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
    }

    /* ===================== RENDER ===================== */

    public function render()
    {
        return view('livewire.protests.dispatch.actions.control-med-protest', [
            'priorityOptions' => ProtestJobPriority::cases(),
        ]);
    }
}
