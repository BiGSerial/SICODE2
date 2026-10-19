<?php

namespace App\Http\Livewire\Closure\Dispatch;

use App\Enum\ClosureOperationalStatus;
use App\Services\Closure\{ClosureMetaClassifier, ClosureSettings};
use App\Services\Closure\Queries\ClosureDispatchListQuery;
use Livewire\{Component, WithPagination};

class Index extends Component
{
    use WithPagination;

    public const PER_PAGE = 25;

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const SELECT_ALL_LIMIT = 1000;

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

    /** Competência (YYYY-MM) filtrada; vazio = todas. */
    public ?string $competence = null;

    /** Ordenação: 'ordem' (padrão) ou 'competence'. */
    public string $sortField = 'ordem';

    public string $sortDir = 'asc';

    /** Seleção "servidor" (API/testes). Na tela a seleção vive no browser (Alpine) e abre o modal direto. */
    public array $selectedOrderIds = [];

    public int $perPage = self::PER_PAGE;

    public bool $showSettings = false;

    public bool $allowFutureCompletion = false;

    public bool $selfAssignEnabled = false;

    public array $selfAssignTypes = [];

    protected $listeners = ['closureOrdersDispatched' => 'afterDispatch'];

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
        if (in_array($name, ['types', 'status', 'filterCompanyId', 'cityId', 'filterAssigneeId', 'onlyUndispatched', 'competence'], true)
            || str_starts_with($name, 'types.')) {
            $this->resetPage();
        }
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array((int) $this->perPage, self::PER_PAGE_OPTIONS, true) ? (int) $this->perPage : self::PER_PAGE;
        $this->resetPage();
    }

    public function toggleType(string $type): void
    {
        $type = ClosureMetaClassifier::letter($type);

        if (!$type) {
            return;
        }

        $this->types = in_array($type, $this->types, true)
            ? array_values(array_diff($this->types, [$type]))
            : array_values(array_merge($this->types, [$type]));
        $this->resetPage();
    }

    /** Cards-resumo clicáveis: all | P | M | F | undispatched. */
    public function setCard(string $card): void
    {
        $this->types            = in_array($card, ClosureMetaClassifier::TYPES, true) ? [$card] : [];
        $this->onlyUndispatched = $card === 'undispatched';
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($field !== 'competence') {
            return;
        }

        if ($this->sortField === 'competence') {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = 'competence';
            $this->sortDir   = 'asc';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'types', 'status', 'filterCompanyId', 'cityId', 'filterAssigneeId', 'onlyUndispatched', 'competence']);
        $this->resetPage();
    }

    public function toggleOrder(int $orderId): void
    {
        $selected = collect($this->selectedOrderIds)->map(fn ($id) => (int) $id);

        $this->selectedOrderIds = $selected->contains($orderId)
            ? $selected->reject(fn (int $id) => $id === $orderId)->values()->all()
            : $selected->push($orderId)->unique()->values()->all();
    }

    public function toggleVisibleOrders(): void
    {
        $visible  = collect($this->listQuery()->pageIds($this->filters(), $this->perPage, $this->page));
        $selected = collect($this->selectedOrderIds)->map(fn ($id) => (int) $id);

        $this->selectedOrderIds = $visible->every(fn (int $id) => $selected->contains($id))
            ? $selected->reject(fn (int $id) => $visible->contains($id))->values()->all()
            : $selected->merge($visible)->unique()->values()->all();
    }

    public function clearSelection(): void
    {
        $this->selectedOrderIds = [];
    }

    /** Depois do despacho, só as Ordens recusadas continuam selecionadas. */
    public function afterDispatch(array $refusedOrderIds = []): void
    {
        $this->selectedOrderIds = array_values(array_map('intval', $refusedOrderIds));
    }

    public function selectAllFiltered(): void
    {
        $this->selectedOrderIds = $this->listQuery()->filteredIds($this->filters(), self::SELECT_ALL_LIMIT);
    }

    /**
     * Chamada pelo cliente (Alpine) em "selecionar todas filtradas": devolve os dados mínimos das Ordens que passam
     * nos filtros atuais (id, Ordem, Nota, tipo, competência), sem alterar estado nem re-renderizar. A seleção vive
     * no browser; esses dados alimentam o resumo do modal sem ida ao servidor na abertura.
     *
     * @return array<int, array{id: int, ordem: ?string, note: ?string, type: ?string, comp: string}>
     */
    public function filteredOrderBriefs(): array
    {
        $this->authorizeDispatch();
        $this->skipRender();

        $query = $this->listQuery();

        return $query->briefs($query->filteredIds($this->filters(), self::SELECT_ALL_LIMIT));
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

    public function render()
    {
        $query    = $this->listQuery();
        $overview = $query->overview($this->filters(), $this->perPage, $this->page);

        return view('livewire.closure.dispatch.index', [
            'counts'       => $overview['counts'],
            'competences'  => $overview['competences'],
            'orders'       => $overview['orders'],
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
            'competence'       => $this->competence ?: null,
            'sort'             => $this->sortField,
            'dir'              => $this->sortDir,
        ];
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
}
