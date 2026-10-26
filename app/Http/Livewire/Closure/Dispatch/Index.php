<?php

namespace App\Http\Livewire\Closure\Dispatch;

use App\Enum\ClosureOperationalStatus;
use App\Http\Livewire\Closure\Concerns\WithBulkSearch;
use App\Services\Closure\{ClosureMetaClassifier, ClosureSettings};
use App\Services\Closure\Queries\ClosureDispatchListQuery;
use Livewire\{Component, WithPagination};

class Index extends Component
{
    use WithBulkSearch;
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

    /** @var array<int, string> Regiões (cities.regiao) */
    public array $regions = [];

    /** @var array<int, string> Regionais (cities.regional) */
    public array $regionals = [];

    /** @var array<int, string> Municípios (cities.rdMunicipio = notes.nexp) */
    public array $cityCodes = [];

    /** @var array<int, string> Status usuário (2 primeiros tokens de orders.statusUser) */
    public array $statusUsers = [];

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
        'search'           => ['except' => ''],
        'types'            => ['except' => []],
        'status'           => ['except' => null],
        'regions'          => ['except' => []],
        'regionals'        => ['except' => []],
        'cityCodes'        => ['except' => [], 'as' => 'cities'],
        'statusUsers'      => ['except' => [], 'as' => 'su'],
        'filterCompanyId'  => ['except' => null, 'as' => 'company'],
        'competence'       => ['except' => null],
        'onlyUndispatched' => ['except' => false, 'as' => 'undispatched'],
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
        if (in_array($name, ['types', 'status', 'filterCompanyId', 'filterAssigneeId', 'onlyUndispatched', 'competence', 'regions', 'regionals', 'cityCodes', 'statusUsers'], true)
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
        $this->reset(['search', 'types', 'status', 'filterCompanyId', 'regions', 'regionals', 'cityCodes', 'statusUsers', 'filterAssigneeId', 'onlyUndispatched', 'competence', 'bulkTerms', 'bulkText']);
        $this->resetPage();
    }

    /**
     * Aplica de uma vez os 4 multi-dropdowns (o cliente mantém a seleção pendente e só envia ao fechar o menu).
     * Município e Regional que não pertencem às Regiões/Regionais escolhidas são descartados (cascata).
     */
    public function applyMultiFilters(array $regions, array $regionals, array $cityCodes, array $statusUsers): void
    {
        $clean = fn (array $values) => array_values(array_unique(array_filter(array_map('strval', array_filter($values, 'is_scalar')), fn ($v) => $v !== '')));

        $this->regions     = $clean($regions);
        $this->regionals   = $clean($regionals);
        $this->cityCodes   = $clean($cityCodes);
        $this->statusUsers = $clean($statusUsers);
        $this->pruneCascade();
        $this->resetPage();
    }

    /** Remove um filtro individual (chip da barra "Filtros ativos"). */
    public function removeFilter(string $kind, string $value = ''): void
    {
        $lists = ['region' => 'regions', 'regional' => 'regionals', 'city' => 'cityCodes', 'su' => 'statusUsers', 'type' => 'types'];

        if (isset($lists[$kind])) {
            $this->{$lists[$kind]} = array_values(array_diff($this->{$lists[$kind]}, [$value]));
        } else {
            match ($kind) {
                'search'       => $this->search = '',
                'bulk'         => $this->clearBulkSearch(),
                'status'       => $this->status           = null,
                'company'      => $this->filterCompanyId  = null,
                'competence'   => $this->competence       = null,
                'assignee'     => $this->filterAssigneeId = null,
                'undispatched' => $this->onlyUndispatched = false,
                default        => null,
            };
        }

        $this->pruneCascade();
        $this->resetPage();
    }

    private function pruneCascade(): void
    {
        if (!$this->regions && !$this->regionals) {
            return;
        }

        $options = $this->listQuery()->filterOptions();
        $inScope = fn (array $o) => (!$this->regions || in_array($o['g'], $this->regions, true));

        $this->regionals = array_values(array_filter($this->regionals, fn ($r) => collect($options['regionals'])->contains(fn ($o) => $o['v'] === $r && $inScope($o))));
        $this->cityCodes = array_values(array_filter($this->cityCodes, fn ($c) => collect($options['cities'])->contains(
            fn ($o) => $o['v'] === $c && $inScope($o) && (!$this->regionals || in_array($o['r'], $this->regionals, true))
        )));
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
            'counts'         => $overview['counts'],
            'competences'    => $overview['competences'],
            'orders'         => $overview['orders'],
            'depositSignals' => app(\App\Services\Closure\ClosurePartnerSignalService::class)->activeByState(collect($overview['orders']->items())->pluck('state_id')),
            'filterOptions'  => $query->filterOptions(),
            'chips'          => $this->activeChips($query),
            'responsibles'   => $query->assignees(),
            'statuses'       => ClosureOperationalStatus::cases(),
        ]);
    }

    /** @return array<int, array{kind: string, value: string, label: string}> chips removíveis da barra "Filtros ativos" */
    private function activeChips(ClosureDispatchListQuery $query): array
    {
        $options = $query->filterOptions();
        $cityMap = collect($options['cities'])->pluck('l', 'v');
        $chips   = [];
        $add     = function (string $kind, string $value, string $label) use (&$chips) {
            $chips[] = ['kind' => $kind, 'value' => $value, 'label' => $label];
        };

        if (trim($this->search) !== '') {
            $add('search', '', 'Busca: ' . trim($this->search));
        }

        if ($this->bulkTerms !== []) {
            $add('bulk', '', 'Busca em massa: ' . count($this->bulkTerms) . ' termo(s)');
        }

        foreach ($this->types as $type) {
            $add('type', $type, 'Tipo: ' . ClosureMetaClassifier::label($type));
        }

        foreach ($this->regions as $v) {
            $add('region', $v, 'Região: ' . $v);
        }

        foreach ($this->regionals as $v) {
            $add('regional', $v, 'Regional: ' . $v);
        }

        foreach ($this->cityCodes as $v) {
            $add('city', $v, 'Município: ' . ($cityMap[$v] ?? $v));
        }

        foreach ($this->statusUsers as $v) {
            $add('su', $v, 'Status usuário: ' . $v);
        }

        if ($this->status) {
            $add('status', '', 'Status: ' . ($this->status === ClosureDispatchListQuery::NO_STATE ? 'Não despachada' : ClosureOperationalStatus::labelFor($this->status)));
        }

        if ($this->filterCompanyId) {
            $add('company', '', 'Empresa: ' . ($this->companies->firstWhere('id', $this->filterCompanyId)?->name ?? '—'));
        }

        if ($this->competence) {
            $add('competence', '', 'Competência: ' . ClosureMetaClassifier::competenceLabel($this->competence));
        }

        if ($this->filterAssigneeId) {
            $add('assignee', '', 'Responsável: ' . ($query->assignees()->firstWhere('id', $this->filterAssigneeId)?->name ?? '—'));
        }

        if ($this->onlyUndispatched) {
            $add('undispatched', '', 'Só não despachadas');
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
        return [
            'search'           => $this->search,
            'bulk'             => $this->bulkTerms,
            'types'            => $this->types,
            'status'           => $this->status,
            'companyId'        => $this->filterCompanyId,
            'regions'          => $this->regions,
            'regionals'        => $this->regionals,
            'cityCodes'        => $this->cityCodes,
            'statusUsers'      => $this->statusUsers,
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
