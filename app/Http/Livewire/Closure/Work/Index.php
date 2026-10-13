<?php

namespace App\Http\Livewire\Closure\Work;

use App\Models\Closure\ClosureOrderState;
use App\Models\{Company, User};
use App\Services\Closure\{ClosureOrderBatchWorkflowService, ClosureOrderWorkflowService};
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class Index extends Component
{
    public string $status = 'all';

    public string $possession = 'all';

    public array $selectedStateIds = [];

    public string $batchAction = '';

    public string $batchReason = '';

    public ?string $managerCompanyId = null;

    public ?int $managerAssigneeId = null;

    public string $managerPriority = '0';

    public array $batchPreview = [];

    public ?int $pendingStateId = null;

    public ?string $pendingAction = null;

    public string $actionReason = '';

    public array $batchReceipt = [];

    public ?string $feedbackMessage = null;

    public ?string $feedbackType = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.work'), 403);
    }

    public function render()
    {
        return view('livewire.closure.work.index', [
            'states' => $this->visibleStatesQuery()->limit(100)->get(),
        ]);
    }

    public function updatedStatus(): void
    {
        $this->clearSelection();
    }

    public function updatedPossession(): void
    {
        $this->clearSelection();
    }

    public function updatedBatchAction(): void
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
        $visible  = $this->visibleStatesQuery()->limit(100)->pluck('id')->map(fn ($id) => (int) $id);
        $selected = collect($this->selectedStateIds)->map(fn ($id) => (int) $id);

        $this->selectedStateIds = $visible->every(fn (int $id) => $selected->contains($id))
            ? $selected->reject(fn (int $id) => $visible->contains($id))->values()->all()
            : $selected->merge($visible)->unique()->values()->all();
        $this->batchPreview = [];
    }

    public function runAction(int $stateId, string $action): void
    {
        $state = $this->visibleStatesQuery()->find($stateId);

        if (!$state) {
            $this->feedback('Esta Ordem não está disponível na sua fila.', 'warning');

            return;
        }

        if (in_array($action, ['wait', 'return'], true)) {
            $this->pendingStateId = $state->id;
            $this->pendingAction  = $action;
            $this->actionReason   = '';

            return;
        }

        $this->runWorkflow($action, $state);
    }

    public function confirmActionWithReason(): void
    {
        if (!$this->pendingStateId || !$this->pendingAction) {
            $this->feedback('Escolha novamente a ação que deseja realizar.', 'warning');

            return;
        }

        if (trim($this->actionReason) === '') {
            $this->feedback('Informe o motivo antes de continuar.', 'warning');

            return;
        }

        $state = $this->visibleStatesQuery()->find($this->pendingStateId);

        if (!$state) {
            $this->cancelActionWithReason();
            $this->feedback('Esta Ordem não está mais disponível na sua fila.', 'warning');

            return;
        }

        $this->runWorkflow($this->pendingAction, $state, $this->actionReason);
        $this->cancelActionWithReason();
    }

    public function cancelActionWithReason(): void
    {
        $this->pendingStateId = null;
        $this->pendingAction  = null;
        $this->actionReason   = '';
    }

    public function prepareBatchPreview(): void
    {
        if (!$this->batchAction || empty($this->selectedStateIds)) {
            $this->feedback('Selecione Ordens e a ação que deseja aplicar.', 'warning');

            return;
        }

        if (!$this->validateBatchPayload()) {

            return;
        }

        $states = $this->visibleStatesQuery()->whereIn('id', $this->selectedStateIds)->get();

        if ($states->count() !== count($this->selectedStateIds)) {
            $this->feedback('Uma ou mais Ordens deixaram de estar disponíveis na sua fila. Atualize a seleção.', 'warning');

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
            $this->feedback('Confira a prévia e confirme o lote para executar.', 'info');
        } catch (\Throwable $exception) {
            $this->feedback($exception->getMessage() ?: 'Não foi possível preparar a prévia do lote.', 'warning');
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
            $this->feedback('A seleção ou os dados mudaram. Gere uma nova prévia antes de confirmar.', 'warning');

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
            $this->clearSelection();
            $this->batchReason       = '';
            $this->managerCompanyId  = null;
            $this->managerAssigneeId = null;
            $this->batchPreview      = [];
            $this->feedback('O lote foi concluído. Consulte o recibo abaixo.', 'success');
        } catch (\Throwable $exception) {
            $this->feedback($exception->getMessage() ?: 'Não foi possível concluir o lote.', 'warning');
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
            ? $state->entered_current_status_at->diffForHumans(null, true) . ' em posse'
            : 'Tempo não informado';
    }

    public function canRun(ClosureOrderState $state, string $action): bool
    {
        $user = auth()->user();

        return match ($action) {
            'claim' => $state->current_holder_type === 'PARTNER_COMPANY'
                && (string) $state->current_holder_company_id === (string) $user->company_id
                && $state->operational_status === ClosureOrderWorkflowService::STATUS_AVAILABLE,
            'start' => (string) $state->current_holder_user_id === (string) $user->id
                && $state->operational_status === ClosureOrderWorkflowService::STATUS_ASSIGNED,
            'wait' => (string) $state->current_holder_user_id === (string) $user->id
                && $state->operational_status === ClosureOrderWorkflowService::STATUS_IN_PROGRESS,
            'return' => (string) $state->current_holder_user_id === (string) $user->id
                && in_array($state->operational_status, [
                    ClosureOrderWorkflowService::STATUS_ASSIGNED,
                    ClosureOrderWorkflowService::STATUS_IN_PROGRESS,
                    ClosureOrderWorkflowService::STATUS_WAITING_RETURN,
                ], true),
            default => false,
        };
    }

    private function visibleStatesQuery(): Builder
    {
        $user = auth()->user();

        return ClosureOrderState::query()
            ->with(['Order.Note', 'CurrentHolderUser', 'CurrentHolderCompany'])
            ->when(!$user->can('closure.manage'), function (Builder $query) use ($user) {
                $query->where(function (Builder $ownershipQuery) use ($user) {
                    $ownershipQuery->where('current_holder_user_id', $user->id)
                        ->orWhere(function (Builder $companyQuery) use ($user) {
                            $companyQuery->where('current_holder_type', 'PARTNER_COMPANY')
                                ->where('current_holder_company_id', $user->company_id);
                        });
                });
            })
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('operational_status', $this->status))
            ->when($this->possession === 'mine', fn (Builder $query) => $query->where('current_holder_user_id', $user->id))
            ->when($this->possession === 'stack', fn (Builder $query) => $query->where('current_holder_type', 'PARTNER_COMPANY')->where('current_holder_company_id', $user->company_id))
            ->orderBy('entered_current_status_at');
    }

    private function runWorkflow(string $action, ClosureOrderState $state, ?string $reason = null): void
    {
        if (!$this->canRun($state, $action)) {
            $this->feedback('Esta ação não está disponível para a posse atual da Ordem.', 'warning');

            return;
        }

        try {
            $workflow = app(ClosureOrderWorkflowService::class);

            match ($action) {
                'claim'  => $workflow->claim($state, auth()->user()),
                'start'  => $workflow->start($state, auth()->user()),
                'wait'   => $workflow->waitForReturn($state, auth()->user(), (string) $reason),
                'return' => $workflow->returnToPool($state, auth()->user(), (string) $reason),
                default  => throw new \LogicException('Ação operacional inválida.'),
            };
            $this->feedback('A situação da Ordem foi atualizada.', 'success');
        } catch (\Throwable $exception) {
            $this->feedback($exception->getMessage() ?: 'Não foi possível atualizar a Ordem.', 'warning');
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
        if ($this->isManagerAction() && !auth()->user()->can('closure.manage')) {
            abort(403);
        }

        if (in_array($this->batchAction, ['wait_for_return', 'return_to_pool', 'transfer'], true) && trim($this->batchReason) === '') {
            $this->feedback('Informe o motivo antes de aplicar esta ação em lote.', 'warning');

            return false;
        }

        if ($this->batchAction === 'assign' && (!$this->managerCompanyId || !$this->managerAssigneeId)) {
            $this->feedback('Informe empresa e usuário de destino para atribuir.', 'warning');

            return false;
        }

        if ($this->batchAction === 'transfer' && !$this->managerCompanyId) {
            $this->feedback('Informe a empresa de destino para transferir.', 'warning');

            return false;
        }

        if ($this->managerAssigneeId && $this->managerCompanyId && !User::query()->whereKey($this->managerAssigneeId)->where('company_id', $this->managerCompanyId)->exists()) {
            $this->feedback('O usuário de destino não pertence à empresa informada.', 'warning');

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

        return $payload;
    }

    private function feedback(string $message, string $type): void
    {
        $this->feedbackMessage = $message;
        $this->feedbackType    = $type;
    }
}
