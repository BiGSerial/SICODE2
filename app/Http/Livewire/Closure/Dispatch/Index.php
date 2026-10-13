<?php

namespace App\Http\Livewire\Closure\Dispatch;

use App\Models\{ClosureCycle, Company, Order, User};
use App\Services\Closure\{ClosureDispatchException, ClosureDispatchService};
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    public string $scope = 'all';

    public array $selectedOrderIds = [];

    public ?string $companyId = null;

    public ?int $assigneeId = null;

    public bool $showPreview = false;

    public array $previewRows = [];

    public ?string $previewNoteLabel = null;

    public ?string $feedbackMessage = null;

    public ?string $feedbackType = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'scope'  => ['except' => 'all'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.dispatch'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPageState();
    }

    public function updatedScope(): void
    {
        $this->resetPageState();
    }

    public function updatedCompanyId(): void
    {
        $this->assigneeId  = null;
        $this->showPreview = false;
        $this->previewRows = [];
    }

    public function toggleOrder(int $orderId): void
    {
        $selected = collect($this->selectedOrderIds)->map(fn ($id) => (int) $id);

        $this->selectedOrderIds = $selected->contains($orderId)
            ? $selected->reject(fn (int $id) => $id === $orderId)->values()->all()
            : $selected->push($orderId)->unique()->values()->all();

        $this->showPreview = false;
        $this->previewRows = [];
    }

    public function toggleVisibleOrders(): void
    {
        $visibleIds = $this->ordersQuery()->limit(100)->pluck('orders.id')->map(fn ($id) => (int) $id);
        $selected   = collect($this->selectedOrderIds)->map(fn ($id) => (int) $id);

        $this->selectedOrderIds = $visibleIds->every(fn (int $id) => $selected->contains($id))
            ? $selected->reject(fn (int $id) => $visibleIds->contains($id))->values()->all()
            : $selected->merge($visibleIds)->unique()->values()->all();

        $this->showPreview = false;
        $this->previewRows = [];
    }

    public function preparePreview(): void
    {
        $this->resetFeedback();

        if (!$this->companyId) {
            $this->feedback('Informe a empresa de destino antes de continuar.', 'warning');

            return;
        }

        $orders = $this->selectedOrders();

        if ($orders->isEmpty()) {
            $this->feedback('Selecione ao menos uma Ordem para preparar o despacho.', 'warning');

            return;
        }

        if ($orders->pluck('note_id')->unique()->count() !== 1) {
            $this->feedback('Selecione Ordens de uma única Nota/OV por vez.', 'warning');

            return;
        }

        $company = Company::query()->find($this->companyId);

        if (!$company) {
            $this->feedback('A empresa de destino não está mais disponível. Escolha outra empresa.', 'warning');

            return;
        }

        $assignee = $this->selectedAssignee($company);

        if ($this->assigneeId && !$assignee) {
            $this->feedback('O usuário escolhido não pertence à empresa de destino.', 'warning');

            return;
        }

        $this->previewRows      = $orders->map(fn (Order $order) => $this->eligibilityFor($order, $company, $assignee))->all();
        $this->previewNoteLabel = (string) $orders->first()->Note?->note;
        $this->showPreview      = true;
    }

    public function confirmDispatch(): void
    {
        $this->preparePreview();

        if (!$this->showPreview) {
            return;
        }

        if (collect($this->previewRows)->contains(fn (array $row) => !$row['eligible'])) {
            $this->feedback('Revise as recusas da prévia antes de confirmar o despacho.', 'warning');

            return;
        }

        $orders   = $this->selectedOrders();
        $note     = $orders->first()?->Note;
        $company  = Company::query()->find($this->companyId);
        $assignee = $company ? $this->selectedAssignee($company) : null;

        if (!$note || !$company) {
            $this->feedback('A seleção mudou. Atualize a prévia antes de confirmar.', 'warning');

            return;
        }

        try {
            $result = app(ClosureDispatchService::class)->dispatch(
                $note,
                $orders->pluck('id')->all(),
                $company,
                $assignee,
                auth()->user(),
            );
        } catch (ClosureDispatchException $exception) {
            $this->feedback($exception->getMessage(), 'warning');

            return;
        } catch (\Throwable $exception) {
            report($exception);

            $this->feedback('Não foi possível concluir o despacho agora. Tente novamente em alguns minutos.', 'danger');

            return;
        }

        $count   = $result->addedOrderIds->count();
        $message = $result->isIdempotent()
            ? 'As Ordens já estavam vinculadas a este mesmo despacho. Nenhuma duplicidade foi criada.'
            : ($count === 1 ? '1 Ordem foi despachada com sucesso.' : "{$count} Ordens foram despachadas com sucesso.");

        $this->selectedOrderIds = [];
        $this->showPreview      = false;
        $this->previewRows      = [];
        $this->previewNoteLabel = null;
        $this->feedback($message, 'success');
    }

    public function getCompaniesProperty()
    {
        return Company::query()->orderBy('name')->get(['id', 'name']);
    }

    public function getAssigneesProperty()
    {
        if (!$this->companyId) {
            return collect();
        }

        return User::query()->where('company_id', $this->companyId)->orderBy('name')->get(['id', 'name', 'company_id']);
    }

    public function render()
    {
        return view('livewire.closure.dispatch.index', [
            'orders' => $this->ordersQuery()->limit(100)->get(),
        ]);
    }

    private function ordersQuery(): Builder
    {
        $latestCycle = ClosureCycle::query()->orderByDesc('year')->orderByDesc('month')->first();

        return Order::query()
            ->select('orders.*')
            ->with(['Note:id,note', 'ClosureTarget.Cycle', 'ClosureState.Production'])
            ->whereHas('ClosureTarget')
            ->when(trim($this->search) !== '', function (Builder $query) {
                $term = '%' . trim($this->search) . '%';
                $query->where(fn (Builder $searchQuery) => $searchQuery->where('orders.ordem', 'like', $term)
                    ->orWhereHas('Note', fn (Builder $noteQuery) => $noteQuery->where('note', 'like', $term)));
            })
            ->when($this->scope === 'meta' && $latestCycle, fn (Builder $query) => $query->whereHas('ClosureTarget', fn (Builder $targetQuery) => $targetQuery->where('closure_cycle_id', $latestCycle->id)))
            ->when($this->scope === 'passive' && $latestCycle, fn (Builder $query) => $query->whereHas('ClosureTarget.Cycle', fn (Builder $cycleQuery) => $cycleQuery->whereRaw('(year * 100 + month) < ?', [$latestCycle->periodKey()])))
            ->when(in_array($this->scope, ['meta', 'passive'], true) && !$latestCycle, fn (Builder $query) => $query->whereRaw('0 = 1'))
            ->orderBy('orders.ordem');
    }

    private function selectedOrders()
    {
        $ids = collect($this->selectedOrderIds)->map(fn ($id) => (int) $id)->filter(fn (int $id) => $id > 0)->unique()->values();

        return Order::query()->with(['Note:id,note', 'ClosureTarget.Cycle', 'ClosureState.Production'])
            ->whereIn('id', $ids)->orderBy('ordem')->get();
    }

    private function selectedAssignee(Company $company): ?User
    {
        if (!$this->assigneeId) {
            return null;
        }

        return User::query()->whereKey($this->assigneeId)->where('company_id', $company->id)->first();
    }

    private function eligibilityFor(Order $order, Company $company, ?User $assignee): array
    {
        $reason     = null;
        $state      = $order->ClosureState;
        $production = $state?->Production;

        if ($order->canceled) {
            $reason = 'Ordem cancelada.';
        } elseif ($this->isSapClosed((string) $order->statusSist)) {
            $reason = 'Ordem já encerrada no SAP.';
        } elseif (!$order->ClosureTarget) {
            $reason = 'Ordem fora da meta de Encerramento.';
        } elseif ($state && (!$production || (string) $production->company_id !== (string) $company->id || (string) $production->user_id !== (string) ($assignee?->id))) {
            $reason = 'Ordem já vinculada a outro despacho.';
        }

        return ['id' => $order->id, 'ordem' => $order->ordem, 'note' => $order->Note?->note, 'eligible' => $reason === null, 'reason' => $reason];
    }

    private function isSapClosed(string $status): bool
    {
        return str_starts_with($status, 'ENTE') || str_starts_with($status, 'ENCE');
    }

    private function resetPageState(): void
    {
        $this->selectedOrderIds = [];
        $this->showPreview      = false;
        $this->previewRows      = [];
        $this->previewNoteLabel = null;
        $this->resetFeedback();
    }

    private function feedback(string $message, string $type): void
    {
        $this->feedbackMessage = $message;
        $this->feedbackType    = $type;
    }

    private function resetFeedback(): void
    {
        $this->feedbackMessage = null;
        $this->feedbackType    = null;
    }
}
