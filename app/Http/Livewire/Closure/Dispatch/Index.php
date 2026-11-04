<?php

namespace App\Http\Livewire\Closure\Dispatch;

use App\Http\Livewire\Closure\Concerns\{WithBulkSearch, WithClosureOrderFilters};
use App\Services\Closure\{ClosureMetaClassifier, ClosurePartnerSignalService};
use App\Services\Closure\Queries\ClosureDispatchListQuery;
use Livewire\{Component, WithPagination};

class Index extends Component
{
    use WithBulkSearch;
    use WithClosureOrderFilters;
    use WithPagination;

    public const PER_PAGE = 25;

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const SELECT_ALL_LIMIT = 1000;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    /** @var array<int, string> P/M/F */
    public array $types = [];

    public bool $onlyUndispatched = false;

    /** Atalho: só Ordens no alcance de um depósito bloqueado (centro de trabalho da 0010 coberto por um bloqueio ativo). */
    public bool $onlyBlocked = false;

    /** Ordenação: 'ordem' (padrão) ou 'competence'. */
    public string $sortField = 'ordem';

    public string $sortDir = 'asc';

    /** Seleção "servidor" (API/testes). Na tela a seleção vive no browser (Alpine) e abre o modal direto. */
    public array $selectedOrderIds = [];

    public int $perPage = self::PER_PAGE;

    protected $listeners = ['closureOrdersDispatched' => 'afterDispatch'];

    protected $queryString = [
        'search'           => ['except' => ''],
        'types'            => ['except' => []],
        'onlyUndispatched' => ['except' => false, 'as' => 'undispatched'],
        'onlyBlocked'      => ['except' => false, 'as' => 'deposito'],
        'filters'          => ['except' => [], 'as' => 'f'],
    ];

    public function mount(): void
    {
        $this->authorizeDispatch();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array((int) $this->perPage, self::PER_PAGE_OPTIONS, true) ? (int) $this->perPage : self::PER_PAGE;
        $this->resetPage();
    }

    /** Cartões-resumo: o tipo (Passivo/Meta atual/Meta futura) é exclusivo e se troca com um clique; "Não despachadas" e "Depósito" ligam/desligam à parte; "Total" limpa tudo. */
    public function toggleCard(string $card): void
    {
        if ($card === 'all') {
            $this->types            = [];
            $this->onlyUndispatched = false;
            $this->onlyBlocked      = false;
        } elseif ($card === 'undispatched') {
            $this->onlyUndispatched = !$this->onlyUndispatched;
        } elseif ($card === 'blocked') {
            $this->onlyBlocked = !$this->onlyBlocked;
        } elseif ($type = ClosureMetaClassifier::letter($card)) {
            // Tipo é escolha exclusiva: clicar em outro troca; clicar no ativo desmarca.
            $this->types = $this->types === [$type] ? [] : [$type];
        } else {
            return;
        }

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

    /** "Limpar tudo": filtros, busca, busca em massa e os recortes dos cartões. A ordenação é preferência e fica. */
    public function clearFilters(): void
    {
        $this->reset(['search', 'types', 'onlyUndispatched', 'onlyBlocked', 'bulkTerms', 'bulkText']);
        $this->clearListFilters();
    }

    /** Remove um chip da barra "Filtros ativos": critério da tela (busca, tipo, atalhos) ou filtro do trait. */
    public function removeAnyFilter(string $key): void
    {
        match (true) {
            $key === 'search'              => $this->search = '',
            $key === 'bulk'                => $this->clearBulkSearch(),
            $key === 'undispatched'        => $this->onlyUndispatched = false,
            $key === 'blocked'             => $this->onlyBlocked      = false,
            str_starts_with($key, 'type:') => $this->types            = array_values(array_diff($this->types, [substr($key, 5)])),
            default                        => $this->removeListFilter($key),
        };

        $this->resetPage();
    }

    public function hasCriteria(): bool
    {
        return $this->hasListFilters() || trim($this->search) !== '' || $this->types !== [] || $this->onlyUndispatched || $this->onlyBlocked || $this->bulkTerms !== [];
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
        $this->selectedOrderIds = $this->listQuery()->filteredIds($this->filters(), self::SELECT_ALL_LIMIT, true);
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

        return $query->briefs($query->filteredIds($this->filters(), self::SELECT_ALL_LIMIT, true));
    }

    /** Exporta a lista (com os filtros atuais) em segundo plano; avisa por notificação quando estiver pronta. */
    public function exportList(): void
    {
        $this->authorizeDispatch();

        \App\Jobs\Closure\ExportClosureListJob::dispatch(static::class, $this->filters(), (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Exportação iniciada',
            'html'     => '<p class="mb-0"><strong>Você será notificado quando o download estiver disponível.</strong></p>',
            'timer'    => 5000,
        ]);
    }

    public function render()
    {
        $query    = $this->listQuery();
        $signals  = app(ClosurePartnerSignalService::class);
        $filters  = $this->filters();
        $overview = $query->overview($filters, $this->perPage, $this->page);
        $orders   = collect($overview['orders']->items());
        $active   = $signals->activeSignals();

        return view('livewire.closure.dispatch.index', [
            'counts'         => $overview['counts'],
            'orders'         => $overview['orders'],
            'depositSignals' => $signals->activeByState($orders->pluck('state_id')),
            'activeDeposits' => $active,
            'centerSignals'  => $orders->pluck('cen_trab')->filter()->unique()->mapWithKeys(fn ($center) => [$center => $signals->signalForCenter($center)])->filter(),
            'hasCriteria'    => $this->hasCriteria(),
            'criteriaChips'  => $this->criteriaChips(),
        ]);
    }

    /** @return array<int, array{key: string, label: string, value: string}> chips dos critérios da tela (busca, tipo, atalhos); os dos filtros vêm do trait */
    private function criteriaChips(): array
    {
        $chips = [];

        if (trim($this->search) !== '') {
            $chips[] = ['key' => 'search', 'label' => 'Busca', 'value' => trim($this->search)];
        }

        if ($this->bulkTerms !== []) {
            $chips[] = ['key' => 'bulk', 'label' => 'Busca em massa', 'value' => count($this->bulkTerms) . ' termo(s)'];
        }

        foreach ($this->types as $type) {
            $chips[] = ['key' => 'type:' . $type, 'label' => 'Tipo', 'value' => ClosureMetaClassifier::label($type)];
        }

        if ($this->onlyUndispatched) {
            $chips[] = ['key' => 'undispatched', 'label' => 'Situação', 'value' => 'Só não despachadas'];
        }

        if ($this->onlyBlocked) {
            $chips[] = ['key' => 'blocked', 'label' => 'Depósito', 'value' => 'Só as afetadas por bloqueio'];
        }

        return $chips;
    }

    /** Ordens e Notas que a lista devolve com os filtros atuais (base do resumo da busca em massa). */
    protected function bulkSearchedValues(): array
    {
        $query = $this->listQuery();

        return collect($query->briefs($query->filteredIds($this->filters())))->flatMap(fn (array $brief) => [$brief['ordem'] ?? null, $brief['note'] ?? null])->filter()->all();
    }

    private function listQuery(): ClosureDispatchListQuery
    {
        return app(ClosureDispatchListQuery::class);
    }

    private function filters(): array
    {
        return $this->orderQueryFilters() + [
            'search'           => $this->search,
            'bulk'             => $this->bulkTerms,
            'types'            => $this->types,
            'onlyUndispatched' => $this->onlyUndispatched,
            'onlyBlocked'      => $this->onlyBlocked,
            'blockedCenters'   => app(ClosurePartnerSignalService::class)->activeWorkCenters(),
            'sort'             => $this->sortField,
            'dir'              => $this->sortDir,
        ];
    }

    private function authorizeDispatch(): void
    {
        abort_unless(auth()->user()?->can('closure.dispatch'), 403);
    }
}
