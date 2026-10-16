<?php

namespace App\Http\Livewire\Closure\Dispatch;

use App\Enum\ClosureOperationalStatus;
use App\Models\Closure\ClosureOrderState;
use App\Models\{Company, Order, User};
use App\Services\Closure\{ClosureDispatchException, ClosureDispatchService, ClosureNotifier, ClosureSettings};
use App\Services\Closure\Queries\ClosureDispatchListQuery;
use Livewire\{Component, WithPagination};

class Index extends Component
{
    use WithPagination;

    public const PER_PAGE = 25;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    /** @var array<int, string> P/M/F */
    public array $types = [];

    public ?string $status = null;

    public ?string $filterCompanyId = null;

    public ?string $cityId = null;

    /** Sem tipo: ids de usuário são UUID e o select envia string. */
    public $filterAssigneeId = null;

    public bool $onlyUndispatched = false;

    public array $selectedOrderIds = [];

    public ?string $companyId = null;

    public $assigneeId = null;

    public bool $showPreview = false;

    public array $previewRows = [];

    /** Recibo por Ordem do último despacho. */
    public array $receipt = [];

    public ?string $feedbackMessage = null;

    public ?string $feedbackType = null;

    public bool $showSettings = false;

    public bool $allowFutureCompletion = false;

    public bool $selfAssignEnabled = false;

    public array $selfAssignTypes = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'types'  => ['except' => []],
        'status' => ['except' => null],
    ];

    public function mount(): void
    {
        $this->authorizeDispatch();

        $this->loadSettings();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updated(string $name): void
    {
        if (in_array($name, ['types', 'status', 'filterCompanyId', 'cityId', 'filterAssigneeId', 'onlyUndispatched'], true)
            || str_starts_with($name, 'types.')) {
            $this->resetPage();
        }
    }

    public function updatedCompanyId(): void
    {
        $this->assigneeId = null;
        $this->invalidatePreview();
    }

    public function updatedAssigneeId(): void
    {
        $this->invalidatePreview();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'types', 'status', 'filterCompanyId', 'cityId', 'filterAssigneeId', 'onlyUndispatched']);
        $this->resetPage();
    }

    public function toggleOrder(int $orderId): void
    {
        $selected = collect($this->selectedOrderIds)->map(fn ($id) => (int) $id);

        $this->selectedOrderIds = $selected->contains($orderId)
            ? $selected->reject(fn (int $id) => $id === $orderId)->values()->all()
            : $selected->push($orderId)->unique()->values()->all();

        $this->invalidatePreview();
    }

    public function toggleVisibleOrders(): void
    {
        $visible  = $this->listQuery()->query($this->filters())->forPage($this->page, self::PER_PAGE)->pluck('orders.id')->map(fn ($id) => (int) $id);
        $selected = collect($this->selectedOrderIds)->map(fn ($id) => (int) $id);

        $this->selectedOrderIds = $visible->every(fn (int $id) => $selected->contains($id))
            ? $selected->reject(fn (int $id) => $visible->contains($id))->values()->all()
            : $selected->merge($visible)->unique()->values()->all();

        $this->invalidatePreview();
    }

    public function clearSelection(): void
    {
        $this->selectedOrderIds = [];
        $this->invalidatePreview();
    }

    public function preparePreview(): void
    {
        $this->authorizeDispatch();
        $this->resetFeedback();
        $this->receipt = [];

        [$company, $assignee] = $this->resolveTarget();

        if (!$company) {
            return;
        }

        $this->previewRows = $this->listQuery()->eligibility($this->selectedOrderIds, $company, $assignee);

        if ($this->previewRows === []) {
            $this->feedback('Selecione ao menos uma Ordem para preparar o despacho.', 'warning');

            return;
        }

        $this->showPreview = true;
    }

    public function confirmDispatch(): void
    {
        $this->authorizeDispatch();
        $this->resetFeedback();

        [$company, $assignee] = $this->resolveTarget();

        if (!$company) {
            return;
        }

        try {
            $result = app(ClosureDispatchService::class)->dispatchOrders(
                $this->selectedOrderIds,
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

        if ($assignee) {
            $this->notifyAssigned($result->dispatchedOrderIds()->all());
        }

        $ordens = Order::query()->with('Note:id,note')->whereIn('id', collect($result->orders)->pluck('order_id'))->get()->keyBy('id');

        $this->receipt = collect($result->orders)->map(fn (array $item) => [
            'order_id' => $item['order_id'],
            'ordem'    => $ordens->get($item['order_id'])?->ordem,
            'note'     => $ordens->get($item['order_id'])?->Note?->note,
            'type'     => $item['meta_type'],
            'status'   => $item['status'],
            'message'  => $item['message'],
        ])->all();

        $done    = $result->dispatchedOrderIds()->count();
        $already = $result->alreadyDispatchedOrderIds()->count();
        $refused = $result->refusedOrderIds()->count();

        // Mantém selecionadas apenas as recusadas para o usuário corrigir/reenviar.
        $this->selectedOrderIds = $result->refusedOrderIds()->all();
        $this->invalidatePreview();
        $this->feedback(
            "{$done} despachada(s), {$already} já despachada(s), {$refused} recusada(s).",
            $refused === 0 ? 'success' : ($done + $already > 0 ? 'warning' : 'danger')
        );
    }

    public function openSettings(): void
    {
        $this->authorizeDispatch();
        $this->loadSettings();
        $this->showSettings = true;
    }

    public function closeSettings(): void
    {
        $this->showSettings = false;
    }

    public function saveSettings(): void
    {
        $this->authorizeDispatch();

        $settings = app(ClosureSettings::class);
        $actor    = auth()->user();

        $settings->setAllowFutureCompletion($this->allowFutureCompletion, $actor);
        $settings->setSelfAssignEnabled($this->selfAssignEnabled, $actor);
        $settings->setSelfAssignTypes($this->selfAssignTypes, $actor);

        $this->loadSettings();
        $this->showSettings = false;
        $this->dispatchBrowserEvent('notification:toast', [
            'icon'  => 'success',
            'title' => 'Configurações salvas',
            'text'  => 'As configurações do Encerramento foram atualizadas.',
        ]);
    }

    public function getCompaniesProperty()
    {
        return $this->listQuery()->companies();
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
        $query = $this->listQuery();

        return view('livewire.closure.dispatch.index', [
            'orders'       => $query->paginate($this->filters(), self::PER_PAGE, $this->page),
            'cities'       => $query->cities(),
            'responsibles' => $query->assignees(),
            'statuses'     => ClosureOperationalStatus::cases(),
        ]);
    }

    private function listQuery(): ClosureDispatchListQuery
    {
        return app(ClosureDispatchListQuery::class);
    }

    private function filters(): array
    {
        return [
            'search'           => $this->search,
            'types'            => $this->types,
            'status'           => $this->status,
            'companyId'        => $this->filterCompanyId,
            'cityId'           => $this->cityId,
            'assigneeId'       => $this->filterAssigneeId ?: null,
            'onlyUndispatched' => $this->onlyUndispatched,
        ];
    }

    /** @return array{0: ?Company, 1: ?User} */
    private function resolveTarget(): array
    {
        if (!$this->companyId) {
            $this->feedback('Informe a empresa de destino antes de continuar.', 'warning');

            return [null, null];
        }

        if ($this->selectedOrderIds === []) {
            $this->feedback('Selecione ao menos uma Ordem para o despacho.', 'warning');

            return [null, null];
        }

        $company = Company::query()->find($this->companyId);

        if (!$company) {
            $this->feedback('A empresa de destino não está mais disponível. Escolha outra empresa.', 'warning');

            return [null, null];
        }

        $assignee = null;

        if ($this->assigneeId) {
            $assignee = User::query()->whereKey($this->assigneeId)->where('company_id', $company->id)->first();

            if (!$assignee) {
                $this->feedback('O usuário escolhido não pertence à empresa de destino.', 'warning');

                return [null, null];
            }
        }

        return [$company, $assignee];
    }

    /** Uma notificação por Ordem atribuída com sucesso (o service de despacho não notifica). */
    private function notifyAssigned(array $orderIds): void
    {
        if ($orderIds === []) {
            return;
        }

        $notifier = app(ClosureNotifier::class);

        foreach (ClosureOrderState::query()->whereIn('order_id', $orderIds)->get() as $state) {
            try {
                $notifier->orderAssigned($state, auth()->user());
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    private function loadSettings(): void
    {
        $settings = app(ClosureSettings::class);

        $this->allowFutureCompletion = $settings->allowFutureCompletion();
        $this->selfAssignEnabled     = $settings->selfAssignEnabled();
        $this->selfAssignTypes       = $settings->selfAssignTypes();
    }

    private function authorizeDispatch(): void
    {
        abort_unless(auth()->user()?->can('closure.dispatch'), 403);
    }

    private function invalidatePreview(): void
    {
        $this->showPreview = false;
        $this->previewRows = [];
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
