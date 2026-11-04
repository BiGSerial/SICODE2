<?php

namespace App\Http\Livewire\Closure\Dispatch;

use App\Http\Livewire\Closure\Work\Index as WorkIndex;
use App\Models\Closure\ClosureOrderState;
use App\Models\{Company, User};
use App\Services\Closure\{ClosureOrderWorkflowService, ClosurePartnerSignalService};
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

    protected function listView(): string
    {
        return 'livewire.closure.dispatch.control';
    }

    public function render()
    {
        $data    = parent::render()->getData();
        $signals = app(ClosurePartnerSignalService::class);
        $states  = $data['states']->getCollection();

        return view('livewire.closure.dispatch.control', $data + [
            'controlMode'    => true,
            'dueDeposits'    => \App\Models\Closure\ClosureDepositCalendarEntry::query()->pending()->whereDate('scheduled_date', '<=', today())->count(),
            'manageState'    => $this->manageStateId ? ClosureOrderState::query()->with(['Order.Note', 'CurrentHolderUser', 'CurrentHolderCompany'])->find($this->manageStateId) : null,
            'activeDeposits' => $signals->activeSignals(),
            'centerSignals'  => $states->pluck('cen_trab')->filter()->unique()->mapWithKeys(fn ($center) => [$center => $signals->signalForCenter($center)])->filter(),
            'hasCriteria'    => $this->hasCriteria(),
            'criteriaChips'  => $this->criteriaChips(),
            'batchActions'   => $this->availableBatchActions(),
        ]);
    }

    /** @return array<string, array{label: string, depends?: array<int, string>}> */
    protected function listFilterDefinitions(): array
    {
        return [
            'statusUser' => ['label' => 'Status usuário'],
            'company'    => ['label' => 'Empresa (posse atual)'],
            'user'       => ['label' => 'Responsável', 'depends' => ['company']],
            'rubric'     => ['label' => 'Rubrica'],
            'status'     => ['label' => 'Status da atividade'],
        ];
    }

    /** @return array<string, array<int, array{v: string, l: string, p?: array<string, string>}>> */
    protected function listFilterOptions(): array
    {
        return [
            'statusUser' => $this->statusUserFilterOptions(),
            'company'    => $this->rememberFilterOptions('company', fn () => Company::query()->orderBy('name')->get(['id', 'name'])->map(fn ($c) => ['v' => (string) $c->id, 'l' => (string) $c->name])->all()),
            'user'       => $this->rememberFilterOptions('user', fn () => User::query()
                ->whereNotNull('company_id')
                ->where(fn ($q) => $q->where('closure_operator', true)->orWhere('closure_manager', true)->orWhere('admin', true)->orWhere('superadm', true))
                ->orderBy('name')->get(['id', 'name', 'company_id'])
                ->map(fn ($u) => ['v' => (string) $u->id, 'l' => (string) $u->name, 'p' => ['company' => (string) $u->company_id]])->all()),
            'rubric' => $this->rubricFilterOptions(),
            'status' => $this->statusFilterOptions(),
        ];
    }

    /** @return array<string, array{label: string, icon: string, hint: string}> */
    public function availableBatchActions(): array
    {
        $all = [
            'assign'         => ['label' => 'Atribuir responsável', 'icon' => 'ri-user-add-line', 'hint' => 'Escolhe empresa e usuário; a Ordem passa a ser dele.'],
            'transfer'       => ['label' => 'Transferir para outra empresa', 'icon' => 'ri-exchange-line', 'hint' => 'Envia à pilha da empresa (ou a um usuário dela), com motivo.'],
            'set_priority'   => ['label' => 'Ajustar prioridade', 'icon' => 'ri-flag-2-line', 'hint' => 'Marca ou tira a prioridade das Ordens.'],
            'return_to_pool' => ['label' => 'Devolver à pilha', 'icon' => 'ri-arrow-go-back-line', 'hint' => 'Tira o responsável e devolve à pilha da empresa, com motivo.'],
        ];

        return $this->isManager() ? $all : array_intersect_key($all, ['return_to_pool' => true]);
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

    /** Controle mostra tudo que já foi despachado, inclusive a pilha sem responsável (Disponível); só ficam de fora as encerradas e canceladas. */
    /** Controle mostra também a pilha sem responsável (Disponível); só ficam de fora as encerradas e canceladas. */
    protected function hiddenStatuses(): array
    {
        return [ClosureOrderWorkflowService::STATUS_CLOSED, ClosureOrderWorkflowService::STATUS_CANCELLED];
    }

    protected function visibleStatesQuery(): Builder
    {
        $query     = parent::visibleStatesQuery();
        $companies = $this->filterValues('company');
        $users     = $this->filterValues('user');

        if ($companies !== []) {
            $query->whereIn('closure_order_states.current_holder_company_id', $companies);
        }

        if ($users !== []) {
            $query->where(function (Builder $own) use ($users) {
                $own->whereIn('closure_order_states.assigned_user_id', $users)
                    ->orWhereIn('closure_order_states.current_holder_user_id', $users);
            });
        }

        return $query;
    }
}
