<?php

namespace App\Http\Livewire\Closure\Work;

use App\Enum\ClosureOperationalStatus;
use App\Http\Livewire\Closure\Work\Concerns\ClosureWorkListing;
use App\Models\Closure\ClosureOrderState;
use App\Models\{Company, User};
use App\Services\Closure\{ClosureMetaClassifier, ClosureOrderBatchWorkflowService, ClosureOrderWorkflowService, ClosureSettings};
use Illuminate\Database\Eloquent\Builder;
use Livewire\{Component, WithPagination};

class Index extends Component
{
    use ClosureWorkListing {
        clearFilters as protected clearListingFilters;
    }
    use WithPagination;

    public const PER_PAGE = 25;

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    protected $paginationTheme = 'bootstrap';

    public int $perPage = self::PER_PAGE;

    public array $selectedStateIds = [];

    public string $batchAction = '';

    public string $batchReason = '';

    public string $batchStatus = '';

    public ?string $managerCompanyId = null;

    public ?int $managerAssigneeId = null;

    public string $managerPriority = '0';

    public array $batchPreview = [];

    public ?int $pendingStateId = null;

    /** 'status' (mudança de status com motivo) ou 'return' (devolver à pilha). */
    public ?string $pendingAction = null;

    public ?string $pendingStatus = null;

    public string $actionReason = '';

    public ?int $confirmFinishId = null;

    public array $batchReceipt = [];

    protected $listeners = ['closureWorkFinishConfirmed' => 'finishConfirmed'];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.work'), 403);
    }

    public function render()
    {
        $settings = app(ClosureSettings::class);

        return view('livewire.closure.work.index', [
            'states'            => $this->visibleStatesQuery()->paginate($this->perPage),
            'selfAssignEnabled' => $settings->selfAssignEnabled(),
            'allowFuture'       => $settings->allowFutureCompletion(),
            'statusOptions'     => ClosureOperationalStatus::cases(),
        ]);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedType(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array((int) $this->perPage, self::PER_PAGE_OPTIONS, true) ? (int) $this->perPage : self::PER_PAGE;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->clearListingFilters();
        $this->resetPage();
    }

    public function updatedBatchAction(): void
    {
        $this->batchPreview = [];
    }

    public function updatedBatchStatus(): void
    {
        $this->batchPreview = [];
    }

    public function updatedManagerCompanyId(): void
    {
        $this->managerAssigneeId = null;
        $this->batchPreview      = [];
    }

    public function getDestinationCompaniesProperty()
    {
        return Company::query()->orderBy('name')->get(['id', 'name']);
    }

    public function getDestinationUsersProperty()
    {
        if (!$this->managerCompanyId) {
            return collect();
        }

        return User::query()
            ->where('company_id', $this->managerCompanyId)
            ->orderBy('name')
            ->get(['id', 'name', 'company_id']);
    }

    public function toggleState(int $stateId): void
    {
        $selected               = collect($this->selectedStateIds)->map(fn ($id) => (int) $id);
        $this->selectedStateIds = $selected->contains($stateId)
            ? $selected->reject(fn (int $id) => $id === $stateId)->values()->all()
            : $selected->push($stateId)->unique()->values()->all();
        $this->batchPreview = [];
    }

    public function toggleVisibleStates(): void
    {
        $visible  = $this->visibleStatesQuery()->forPage(max(1, (int) $this->page), $this->perPage)->pluck('closure_order_states.id')->map(fn ($id) => (int) $id);
        $selected = collect($this->selectedStateIds)->map(fn ($id) => (int) $id);

        $this->selectedStateIds = $visible->every(fn (int $id) => $selected->contains($id))
            ? $selected->reject(fn (int $id) => $visible->contains($id))->values()->all()
            : $selected->merge($visible)->unique()->values()->all();
        $this->batchPreview = [];
    }

    /** Ações simples por linha: start, return (pede motivo), finish (pede confirmação). */
    public function runAction(int $stateId, string $action): void
    {
        $state = $this->visibleStatesQuery()->find($stateId);

        if (!$state) {
            $this->toast('Ordem indisponível', 'Esta Ordem não está disponível na sua lista.', 'warning');

            return;
        }

        if ($action === 'return') {
            if (!$this->canRun($state, 'return')) {
                $this->toast('Ação indisponível', 'Esta ação não está disponível para a posse atual da Ordem.', 'warning');

                return;
            }

            $this->openReason($state->id, 'return');

            return;
        }

        if ($action === 'finish') {
            $this->askFinish($state);

            return;
        }

        $this->runWorkflow($action, $state);
    }

    /** Alteração de status (Pausa, Aguardando Parceira, Depósito, voltar a Em andamento). */
    public function changeStatus(int $stateId, string $newStatus): void
    {
        $state = $this->visibleStatesQuery()->find($stateId);

        if (!$state) {
            $this->toast('Ordem indisponível', 'Esta Ordem não está disponível na sua lista.', 'warning');

            return;
        }

        if (!in_array($newStatus, $this->statusTargets($state), true) || !$this->canChangeStatus($state)) {
            $this->toast('Transição não permitida', 'Este status não pode ser aplicado no estado atual da Ordem.', 'warning');

            return;
        }

        if ($this->statusNeedsReason($state, $newStatus)) {
            $this->openReason($state->id, 'status', $newStatus);

            return;
        }

        $this->runWorkflow('status', $state, null, $newStatus);
    }

    public function confirmActionWithReason(): void
    {
        if (!$this->pendingStateId || !$this->pendingAction) {
            $this->toast('Ação não identificada', 'Escolha novamente a ação que deseja realizar.', 'warning');

            return;
        }

        if (trim($this->actionReason) === '') {
            $this->toast('Motivo obrigatório', 'Informe o motivo antes de continuar.', 'warning');

            return;
        }

        $state = $this->visibleStatesQuery()->find($this->pendingStateId);

        if (!$state) {
            $this->cancelActionWithReason();
            $this->toast('Ordem indisponível', 'Esta Ordem não está mais disponível na sua lista.', 'warning');

            return;
        }

        $this->runWorkflow($this->pendingAction, $state, $this->actionReason, $this->pendingStatus);
        $this->cancelActionWithReason();
    }

    public function cancelActionWithReason(): void
    {
        $this->pendingStateId = null;
        $this->pendingAction  = null;
        $this->pendingStatus  = null;
        $this->actionReason   = '';
    }

    /** Retorno do SweetAlert de confirmação de "Finalizar". */
    public function finishConfirmed(): void
    {
        $stateId               = $this->confirmFinishId;
        $this->confirmFinishId = null;

        if (!$stateId) {
            return;
        }

        $state = $this->visibleStatesQuery()->find($stateId);

        if (!$state) {
            $this->toast('Ordem indisponível', 'Esta Ordem não está mais disponível na sua lista.', 'warning');

            return;
        }

        $this->runWorkflow('finish', $state);
    }

    public function prepareBatchPreview(): void
    {
        if (!$this->batchAction || empty($this->selectedStateIds)) {
            $this->toast('Seleção incompleta', 'Selecione Ordens e a ação que deseja aplicar.', 'warning');

            return;
        }

        if (!$this->validateBatchPayload()) {
            return;
        }

        $states = $this->visibleStatesQuery()->whereIn('closure_order_states.id', $this->selectedStateIds)->get();

        if ($states->count() !== count($this->selectedStateIds)) {
            $this->toast('Seleção desatualizada', 'Uma ou mais Ordens deixaram de estar na sua lista. Atualize a seleção.', 'warning');

            return;
        }

        try {
            $payload = $this->batchPayload();
            $result  = app(ClosureOrderBatchWorkflowService::class)->preview(
                $states->pluck('id')->all(),
                $this->batchAction,
                $payload,
                auth()->user(),
            );
            $labels             = $states->mapWithKeys(fn (ClosureOrderState $state) => [$state->id => $state->Order?->ordem ?? "Item {$state->id}"]);
            $this->batchPreview = [
                'action'   => $this->batchAction,
                'payload'  => $payload,
                'stateIds' => $states->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
                'eligible' => $result->eligible()->map(fn ($item) => $labels->get($item->stateId, "Item {$item->stateId}"))->all(),
                'refusals' => $result->refused()->map(fn ($item) => [
                    'order'  => $labels->get($item->stateId, "Item {$item->stateId}"),
                    'reason' => $item->reason,
                ])->all(),
            ];
        } catch (\Throwable $exception) {
            $this->toast('Não foi possível gerar a prévia', $exception->getMessage() ?: 'Tente novamente.', 'warning');
        }
    }

    public function confirmBatchAction(): void
    {
        if (!$this->validateBatchPayload()) {
            return;
        }

        if (!$this->batchPreview
            || $this->batchPreview['action'] !== $this->batchAction
            || $this->batchPreview['payload'] !== $this->batchPayload()
            || $this->batchPreview['stateIds'] !== collect($this->selectedStateIds)->map(fn ($id) => (int) $id)->sort()->values()->all()) {
            $this->toast('Prévia desatualizada', 'A seleção ou os dados mudaram. Gere uma nova prévia antes de confirmar.', 'warning');

            return;
        }

        try {
            $result = app(ClosureOrderBatchWorkflowService::class)->execute(
                $this->batchPreview['stateIds'],
                $this->batchAction,
                $this->batchPayload(),
                auth()->user(),
            );
            $states             = ClosureOrderState::query()->with('Order')->whereIn('id', $this->batchPreview['stateIds'])->get();
            $labels             = $states->mapWithKeys(fn (ClosureOrderState $state) => [$state->id => $state->Order?->ordem ?? "Item {$state->id}"]);
            $this->batchReceipt = [
                'batchId'   => $result->batchId,
                'successes' => $result->successful()->map(fn ($item) => $labels->get($item->stateId, "Item {$item->stateId}"))->all(),
                'refusals'  => $result->refused()->map(fn ($item) => [
                    'order'  => $labels->get($item->stateId, "Item {$item->stateId}"),
                    'reason' => $item->reason,
                ])->all(),
            ];
            $ok   = count($this->batchReceipt['successes']);
            $fail = count($this->batchReceipt['refusals']);
            $this->clearSelection();
            $this->batchReason       = '';
            $this->batchStatus       = '';
            $this->managerCompanyId  = null;
            $this->managerAssigneeId = null;
            $this->batchPreview      = [];
            $this->toast(
                'Lote concluído',
                "{$ok} Ordem(ns) processada(s), {$fail} recusada(s). Consulte o recibo abaixo.",
                $fail === 0 ? 'success' : ($ok === 0 ? 'error' : 'warning'),
            );
        } catch (\Throwable $exception) {
            $this->toast('Não foi possível concluir o lote', $exception->getMessage() ?: 'Tente novamente.', 'warning');
        }
    }

    public function holderLabel(ClosureOrderState $state): string
    {
        if ($state->current_holder_user_id) {
            return $state->CurrentHolderUser?->name ?? 'Usuário indisponível';
        }

        return $state->CurrentHolderCompany?->name ?? 'Pilha da empresa';
    }

    public function elapsedLabel(ClosureOrderState $state): string
    {
        return $state->entered_current_status_at
            ? $state->entered_current_status_at->diffForHumans(null, true)
            : '—';
    }

    public function isOwner(ClosureOrderState $state): bool
    {
        return $state->assigned_user_id && (string) $state->assigned_user_id === (string) auth()->id();
    }

    protected function isManager(): bool
    {
        return (bool) auth()->user()?->can('closure.manage');
    }

    public function canRun(ClosureOrderState $state, string $action): bool
    {
        return match ($action) {
            'start'  => $this->isOwner($state) && $state->operational_status === ClosureOrderWorkflowService::STATUS_ASSIGNED,
            'return' => $this->isOwner($state) && in_array($state->operational_status, array_diff(
                ClosureOrderWorkflowService::WORKING_STATUSES,
                [ClosureOrderWorkflowService::STATUS_AVAILABLE],
            ), true),
            'finish' => ($this->isOwner($state) || $this->isManager())
                && in_array($state->operational_status, ClosureOrderWorkflowService::FINISHABLE_STATUSES, true),
            default => false,
        };
    }

    public function canChangeStatus(ClosureOrderState $state): bool
    {
        return ($this->isOwner($state) || $this->isManager())
            && array_key_exists($state->operational_status, ClosureOrderWorkflowService::MANUAL_TRANSITIONS);
    }

    /** @return array<int, string> */
    public function statusTargets(ClosureOrderState $state): array
    {
        return ClosureOrderWorkflowService::MANUAL_TRANSITIONS[$state->operational_status] ?? [];
    }

    public function statusActionLabel(string $target): string
    {
        return $target === ClosureOrderWorkflowService::STATUS_IN_PROGRESS
            ? 'Voltar a Em andamento'
            : ClosureOperationalStatus::labelFor($target);
    }

    /** Motivo pelo qual Finalizar está bloqueado para a Ordem (null quando liberado). */
    public function finishBlockedReason(ClosureOrderState $state): ?string
    {
        if ($state->meta_type === ClosureMetaClassifier::TYPE_FUTURE && !app(ClosureSettings::class)->allowFutureCompletion()) {
            return 'Ordem de meta futura: o despachante ainda não liberou a finalização antes do mês da meta.';
        }

        return null;
    }

    private function statusNeedsReason(ClosureOrderState $state, string $target): bool
    {
        return in_array($target, ClosureOrderWorkflowService::REASON_REQUIRED_TARGETS, true)
            || in_array($state->operational_status, [
                ClosureOrderWorkflowService::STATUS_FINISHED,
                ClosureOrderWorkflowService::STATUS_WAITING,
            ], true);
    }

    private function openReason(int $stateId, string $action, ?string $status = null): void
    {
        $this->pendingStateId = $stateId;
        $this->pendingAction  = $action;
        $this->pendingStatus  = $status;
        $this->actionReason   = '';
    }

    private function askFinish(ClosureOrderState $state): void
    {
        if (!$this->canRun($state, 'finish')) {
            $this->toast('Ação indisponível', 'Esta Ordem não pode ser finalizada no estado atual.', 'warning');

            return;
        }

        if ($blocked = $this->finishBlockedReason($state)) {
            $this->toast('Finalização bloqueada', $blocked, 'warning');

            return;
        }

        $this->confirmFinishId = $state->id;
        $future                = $state->meta_type === ClosureMetaClassifier::TYPE_FUTURE;

        $this->dispatchBrowserEvent('closure:confirm', [
            'title' => 'Finalizar a Ordem ' . ($state->Order?->ordem ?? ''),
            'html'  => $future
                ? 'Esta é uma Ordem de <strong>meta futura</strong>. Ela ficará em <strong>Aguardando</strong> até o mês da meta e será finalizada automaticamente.'
                : 'A Ordem será marcada como <strong>Finalizada</strong> e aguardará o encerramento no SAP.',
            'confirmText' => 'Sim, finalizar',
            'cancelText'  => 'Cancelar',
            'action'      => 'closureWorkFinishConfirmed',
        ]);
    }

    protected function visibleStatesQuery(): Builder
    {
        $user  = auth()->user();
        $query = $this->baseListingQuery();

        $query->when(!$user->can('closure.manage'), function (Builder $builder) use ($user) {
            $builder->where(function (Builder $own) use ($user) {
                $own->where('closure_order_states.assigned_user_id', $user->id)
                    ->orWhere('closure_order_states.current_holder_user_id', $user->id);
            });
        });

        if ($this->status === 'all') {
            $query->whereNotIn('closure_order_states.operational_status', [
                ClosureOrderWorkflowService::STATUS_CLOSED,
                ClosureOrderWorkflowService::STATUS_CANCELLED,
                ClosureOrderWorkflowService::STATUS_AVAILABLE,
            ]);
        } else {
            $query->where('closure_order_states.operational_status', $this->status);
        }

        return $query
            ->orderByDesc('closure_order_states.priority')
            ->orderBy('closure_order_states.entered_current_status_at');
    }

    private function runWorkflow(string $action, ClosureOrderState $state, ?string $reason = null, ?string $newStatus = null): void
    {
        $user    = auth()->user();
        $ordem   = $state->Order?->ordem ?? "#{$state->id}";
        $allowed = match ($action) {
            'start', 'return', 'finish' => $this->canRun($state, $action),
            'status' => $this->canChangeStatus($state) && in_array($newStatus, $this->statusTargets($state), true),
            default  => false,
        };

        if (!$allowed) {
            $this->toast('Ação indisponível', 'Esta ação não está disponível para a Ordem no estado atual.', 'warning');

            return;
        }

        try {
            $workflow = app(ClosureOrderWorkflowService::class);

            switch ($action) {
                case 'start':
                    $workflow->start($state, $user);
                    $this->toast('Atividade iniciada', "Ordem {$ordem} em andamento.");

                    break;
                case 'return':
                    $workflow->returnToPool($state, $user, (string) $reason);
                    $this->toast('Ordem devolvida', "Ordem {$ordem} voltou para a pilha.");

                    break;
                case 'status':
                    $workflow->changeStatus($state, (string) $newStatus, $user, $reason);
                    $this->toast('Status alterado', "Ordem {$ordem}: " . ClosureOperationalStatus::labelFor($newStatus) . '.');

                    break;
                case 'finish':
                    $done = $workflow->finish($state, $user);
                    $this->toast(
                        $done->operational_status === ClosureOrderWorkflowService::STATUS_WAITING ? 'Ordem em Aguardando' : 'Ordem finalizada',
                        $done->operational_status === ClosureOrderWorkflowService::STATUS_WAITING
                            ? "Ordem {$ordem} é meta futura: fica em Aguardando até o mês da meta."
                            : "Ordem {$ordem} finalizada; aguardando o encerramento no SAP.",
                    );

                    break;
            }
        } catch (\Throwable $exception) {
            $this->toast('Não foi possível atualizar a Ordem', $exception->getMessage() ?: 'Tente novamente.', 'warning');
        }
    }

    private function clearSelection(): void
    {
        $this->selectedStateIds = [];
        $this->batchPreview     = [];
    }

    private function isManagerAction(): bool
    {
        return in_array($this->batchAction, ['assign', 'transfer', 'set_priority'], true);
    }

    private function validateBatchPayload(): bool
    {
        if ($this->isManagerAction() && !$this->isManager()) {
            abort(403);
        }

        $needsReason = in_array($this->batchAction, ['return_to_pool', 'transfer'], true)
            || ($this->batchAction === 'change_status' && in_array($this->batchStatus, ClosureOrderWorkflowService::REASON_REQUIRED_TARGETS, true));

        if ($needsReason && trim($this->batchReason) === '') {
            $this->toast('Motivo obrigatório', 'Informe o motivo antes de aplicar esta ação em lote.', 'warning');

            return false;
        }

        if ($this->batchAction === 'change_status' && $this->batchStatus === '') {
            $this->toast('Status obrigatório', 'Escolha o novo status para o lote.', 'warning');

            return false;
        }

        if ($this->batchAction === 'assign' && (!$this->managerCompanyId || !$this->managerAssigneeId)) {
            $this->toast('Destino obrigatório', 'Informe empresa e usuário de destino para atribuir.', 'warning');

            return false;
        }

        if ($this->batchAction === 'transfer' && !$this->managerCompanyId) {
            $this->toast('Destino obrigatório', 'Informe a empresa de destino para transferir.', 'warning');

            return false;
        }

        if ($this->managerAssigneeId && $this->managerCompanyId && !User::query()->whereKey($this->managerAssigneeId)->where('company_id', $this->managerCompanyId)->exists()) {
            $this->toast('Destino inválido', 'O usuário de destino não pertence à empresa informada.', 'warning');

            return false;
        }

        return true;
    }

    private function batchPayload(): array
    {
        $payload = ['reason' => trim($this->batchReason)];

        if ($this->isManagerAction()) {
            $payload['company_id']  = $this->managerCompanyId;
            $payload['assignee_id'] = $this->managerAssigneeId;
        }

        if ($this->batchAction === 'set_priority') {
            $payload['priority'] = $this->managerPriority === '1';
        }

        if ($this->batchAction === 'change_status') {
            $payload['status'] = $this->batchStatus;
        }

        return $payload;
    }
}
