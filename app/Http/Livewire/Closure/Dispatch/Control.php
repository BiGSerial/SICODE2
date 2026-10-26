<?php

namespace App\Http\Livewire\Closure\Dispatch;

use App\Http\Livewire\Closure\Work\Index as WorkIndex;
use App\Models\Closure\ClosureOrderState;
use App\Models\{Company, User};
use App\Services\Closure\ClosureOrderWorkflowService;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Controle de Encerramento" (dispatch.stack): o que já foi despachado, de todos os usuários e
 * empresas. Reaproveita a lista/ações do Work\Index em modo gestor (atribuir, transferir, prioridade,
 * alterar status) e acrescenta filtros por empresa, responsável e prioridade. Exige closure.dispatch.
 */
class Control extends WorkIndex
{
    /** Ação de gestão em andamento na linha: 'reassign' (trocar responsável), 'unassign' (desatribuir) ou 'remove' (devolver à pilha). */
    public ?string $manageMode = null;

    public ?int $manageStateId = null;

    public string $manageReason = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.dispatch'), 403);
    }

    public function render()
    {
        return parent::render()->with([
            'controlMode' => true,
            'dueDeposits' => \App\Models\Closure\ClosureDepositCalendarEntry::query()->pending()->whereDate('scheduled_date', '<=', today())->count(),
            'manageState' => $this->manageStateId ? ClosureOrderState::query()->with(['Order.Note', 'CurrentHolderUser', 'CurrentHolderCompany'])->find($this->manageStateId) : null,
        ]);
    }

    public function canManage(ClosureOrderState $state, string $mode): bool
    {
        return match ($mode) {
            'reassign' => in_array($state->operational_status, ClosureOrderWorkflowService::WORKING_STATUSES, true),
            'unassign' => $state->assigned_user_id && in_array($state->operational_status, ClosureOrderWorkflowService::WORKING_STATUSES, true),
            'remove'   => in_array($state->operational_status, array_diff(ClosureOrderWorkflowService::WORKING_STATUSES, [ClosureOrderWorkflowService::STATUS_AVAILABLE]), true),
            default    => false,
        };
    }

    public function openManage(int $stateId, string $mode): void
    {
        $state = $this->visibleStatesQuery()->find($stateId);

        if (!$state || !$this->canManage($state, $mode)) {
            $this->toast('Ação indisponível', 'Esta ação não está disponível para a Ordem no estado atual.', 'warning');

            return;
        }

        $this->manageStateId     = $state->id;
        $this->manageMode        = $mode;
        $this->manageReason      = '';
        $this->managerCompanyId  = $mode === 'reassign' ? (string) ($state->current_holder_company_id ?? '') ?: null : null;
        $this->managerAssigneeId = null;
    }

    public function cancelManage(): void
    {
        $this->manageStateId     = null;
        $this->manageMode        = null;
        $this->manageReason      = '';
        $this->managerCompanyId  = null;
        $this->managerAssigneeId = null;
    }

    public function confirmManage(): void
    {
        $state = $this->manageStateId ? $this->visibleStatesQuery()->find($this->manageStateId) : null;
        $mode  = $this->manageMode;

        if (!$state || !$mode || !$this->canManage($state, $mode)) {
            $this->cancelManage();
            $this->toast('Ação indisponível', 'Esta Ordem não está mais disponível para esta ação.', 'warning');

            return;
        }

        if (trim($this->manageReason) === '') {
            $this->toast('Motivo obrigatório', 'Informe o motivo antes de continuar.', 'warning');

            return;
        }

        $ordem    = $state->Order?->ordem ?? "#{$state->id}";
        $actor    = auth()->user();
        $workflow = app(ClosureOrderWorkflowService::class);

        try {
            if ($mode === 'reassign') {
                if (!$this->managerCompanyId || !$this->managerAssigneeId) {
                    $this->toast('Destino obrigatório', 'Escolha a empresa e o novo responsável.', 'warning');

                    return;
                }

                $assignee = User::query()->where('company_id', $this->managerCompanyId)->findOrFail($this->managerAssigneeId);
                $workflow->transfer($state, Company::query()->findOrFail($this->managerCompanyId), $assignee, $actor, $this->manageReason);
                $this->toast('Responsável alterado', "Ordem {$ordem} agora é de {$assignee->name}.");
            } elseif ($mode === 'unassign') {
                $workflow->transfer($state, Company::query()->findOrFail($state->current_holder_company_id), null, $actor, $this->manageReason);
                $this->toast('Ordem desatribuída', "Ordem {$ordem} voltou para a pilha da empresa, sem responsável.");
            } else {
                $workflow->returnToPool($state, $actor, $this->manageReason);
                $this->toast('Ordem removida', "Ordem {$ordem} foi devolvida à pilha.");
            }

            $this->cancelManage();
        } catch (\Throwable $exception) {
            $this->toast('Não foi possível atualizar a Ordem', $exception->getMessage() ?: 'Tente novamente.', 'warning');
        }
    }

    public function updatedFilterCompany(): void
    {
        $this->filterUser       = null;
        $this->selectedStateIds = [];
        $this->resetPage();
    }

    public function updatedFilterUser(): void
    {
        $this->resetPage();
        $this->selectedStateIds = [];
    }

    public function getControlUsersProperty()
    {
        return User::query()
            ->whereNotNull('company_id')
            ->when($this->filterCompany, fn ($q) => $q->where('company_id', $this->filterCompany))
            ->where(fn ($q) => $q->where('closure_operator', true)->orWhere('closure_manager', true)->orWhere('admin', true)->orWhere('superadm', true))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function visibleStatesQuery(): Builder
    {
        $query = parent::visibleStatesQuery();

        if ($this->filterCompany) {
            $query->where('closure_order_states.current_holder_company_id', $this->filterCompany);
        }

        if ($this->filterUser) {
            $query->where(function (Builder $own) {
                $own->where('closure_order_states.assigned_user_id', $this->filterUser)
                    ->orWhere('closure_order_states.current_holder_user_id', $this->filterUser);
            });
        }

        return $query;
    }
}
