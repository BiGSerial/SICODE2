<?php

namespace App\Http\Livewire\Dispatchs\Supervision;

use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Http\Livewire\Services\Concerns\BuildsLegalNoteTags;
use App\Models\{City, Production, Service, User};
use App\Services\Supervision\{SupervisionControlQuery, SupervisionControlRowPresenter};
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\{LengthAwarePaginator, Paginator};
use Livewire\{Component, WithPagination};

/**
 * Controle de Fiscalização (lista de acompanhamento do que já foi despachado). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10.
 * A parceira (`PartnerStack`) usa a mesma consulta e o mesmo desenho; muda só a visão (rota da lista operacional).
 */
class Stack extends Component
{
    use WithPagination;
    use WithNoteListFilters;
    use BuildsLegalNoteTags;
    use \App\Helpers\TextFormatter;

    protected $paginationTheme = 'bootstrap';

    /** Uuid da atividade. */
    public $service;

    public $perPage = 50;

    public $search = '';

    public $advancedSearch;

    public $multiSearch = [];

    public $note_type = '';

    /** Controle da parceira: aponta para a lista da parceira. */
    protected bool $partner = false;

    private ?Service $serviceModelMemo = null;

    /** @var array<int, int>|null Contagem por status desta requisição (cartões e total da página leem a mesma). */
    private ?array $statusCountsMemo = null;

    protected $queryString = [
        'search'    => ['except' => '', 'as' => 'buscar'],
        'page'      => ['except' => 1, 'as' => 'p'],
        'perPage'   => ['as' => 'pp'],
        'note_type' => ['except' => '', 'as' => 'tipo'],
        'filters'   => ['except' => [], 'as' => 'f'],
    ];

    protected $listeners = [
        'resetFilters' => 'clearAllCriteria',
        'refresh_list' => '$refresh',
        'filterUser'   => 'filterUser',
        'getCopy'      => 'copy',
    ];

    public function mount($service)
    {
        $this->service = $service;
    }

    public function getServiceModelProperty(): Service
    {
        return $this->serviceModelMemo ??= Service::where('uuid', $this->service)->firstOrFail();
    }

    /** Clique numa pessoa da Ocupação (menu lateral): filtra o controle por ela e reflete no filtro "Usuário". */
    public function filterUser($user_id)
    {
        $this->filters['user'] = [(string) $user_id];
        $this->afterListFiltersChanged();
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    // ---------- Filtros ----------

    protected function listFilterDefinitions(): array
    {
        return [
            'user'     => ['label' => 'Usuário'],
            'status'   => ['label' => 'Status'],
            'region'   => ['label' => 'Região'],
            'regional' => ['label' => 'Regional', 'depends' => ['region']],
            'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    /** As opções de localidade não dependem da atividade: o controle e o da parceira dividem o cache. */
    protected function listFilterCacheScope(): string
    {
        return 'supervision-control';
    }

    protected function listFilterOptions(): array
    {
        $contract = Auth()->User()->usesContractDispatch();
        $visible  = $contract ? SicodeRules::visibleCompanyIdsFor(Auth()->User()) : null;

        // Quem tem atividade em aberto (sem cache: a Ocupação filtra por gente que acabou de receber).
        $open = Production::query()
            ->where('service_id', $this->service)
            ->where('completed', false)
            ->when($visible !== null, fn ($q) => count($visible) ? $q->whereIn('company_id', $visible) : $q->whereRaw('0 = 1'));

        $users    = (clone $open)->whereNotNull('user_id')->distinct()->pluck('user_id');
        $statuses = (clone $open)->distinct()->orderBy('status')->pluck('status');

        return [
            'user' => User::withTrashed()->whereIn('id', $users)->orderBy('name')->get(['id', 'name', 'deleted_at'])
                ->map(fn ($u) => ['v' => (string) $u->id, 'l' => $u->name . ($u->deleted_at ? ' (inativo)' : '')])->all(),
            'status' => $statuses->map(fn ($s) => ['v' => (string) $s, 'l' => \App\Custom\Notestatus::status($s)->status])->values()->all(),
        ] + $this->locationFilterOptions();
    }

    /** Códigos de município (`notes.nexp`) dos filtros Região/Regional/Município; `null` sem filtro de localidade. */
    protected function locationCityCodes(): ?array
    {
        $region   = $this->filterValues('region');
        $regional = $this->filterValues('regional');
        $city     = $this->filterValues('city');

        if (!$region && !$regional && !$city) {
            return null;
        }

        return City::query()
            ->when($region, fn ($q) => $q->whereIn('regiao', $region))
            ->when($regional, fn ($q) => $q->whereIn('baseConstrucao', $regional))
            ->when($city, fn ($q) => $q->whereIn('rdMunicipio', $city))
            ->pluck('rdMunicipio')->filter()->unique()->values()->all();
    }

    /** Cartão de status do painel: liga/desliga o status no filtro "Status". */
    public function toggleStatus(int $status): void
    {
        $current = $this->filterValues('status');
        $key     = (string) $status;

        $this->filters['status'] = in_array($key, $current, true)
            ? array_values(array_diff($current, [$key]))
            : [...$current, $key];

        if (!$this->filters['status']) {
            unset($this->filters['status']);
        }

        $this->afterListFiltersChanged();
    }

    /** "Limpar filtros": filtros, busca (simples e em massa) e tipo. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->filters        = [];
        $this->search         = '';
        $this->multiSearch    = [];
        $this->advancedSearch = null;
        $this->note_type      = '';
        $this->afterListFiltersChanged();
    }

    public function updatedSearch()
    {
        if (!trim((string) $this->search)) {
            return;
        }

        $this->multiSearch    = [];
        $this->advancedSearch = null;
        $this->resetPage();
    }

    public function updatedNoteType(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function buscarMulti()
    {
        if (!trim((string) $this->advancedSearch)) {
            return;
        }

        $this->resetPage();
        $this->multiSearch = $this->formatTextToArray($this->advancedSearch);

        if (count($this->multiSearch) > 0) {
            $this->search         = null;
            $this->advancedSearch = null;

            $this->dispatchBrowserEvent('hideModal');
        }
    }

    // ---------- Exportação ----------

    /** Excel do que a tela mostra: os mesmos critérios (busca, tipo, usuário, status e localidade), todas as páginas. */
    public function exportToExcel()
    {
        \App\Jobs\Dispatchs\ExportDispatchSupervisionJob::dispatch($this->criteria(), (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'info',
            'title' => 'Exportação iniciada!',
            'text'  => 'Você será notificado quando o arquivo estiver pronto.',
        ]);
    }

    // ---------- Consulta ----------

    /** Critérios da tela (os mesmos que o Excel recebe): a consulta mora em `SupervisionControlQuery`. */
    private function criteria(): array
    {
        return [
            'service'     => $this->service,
            'search'      => $this->search,
            'multiSearch' => $this->multiSearch,
            'note_type'   => $this->note_type,
            'users'       => $this->filterValues('user'),
            'status'      => $this->filterValues('status'),
            'cityCodes'   => $this->locationCityCodes(),
        ];
    }

    /** Recorte único da lista e dos cartões de status. `$withStatus = false` ignora o filtro de status, para os cartões contarem todos. */
    private function controlQuery(bool $withStatus = true): Builder
    {
        return app(SupervisionControlQuery::class)->base($this->criteria(), auth()->user(), $withStatus);
    }

    public function getListsProperty()
    {
        $query = app(SupervisionControlQuery::class)->list($this->criteria(), auth()->user());

        // O total sai da contagem por status (já feita para os cartões, no mesmo recorte): evita repetir a consulta pesada só para contar.
        $status = $this->filterValues('status');
        $counts = $status ? array_intersect_key($this->statusCounts, array_flip($status)) : $this->statusCounts;
        $total  = (int) array_sum($counts);
        $page   = Paginator::resolveCurrentPage('page');

        return new LengthAwarePaginator(
            $total ? $query->forPage($page, (int) $this->perPage)->get() : $query->getModel()->newCollection(),
            $total,
            (int) $this->perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page']
        );
    }

    /** Contagem por status (cartões do painel), no mesmo recorte da lista mas sem o filtro de status. */
    public function getStatusCountsProperty(): array
    {
        return $this->statusCountsMemo ??= $this->controlQuery(false)
            ->reorder()
            ->selectRaw('productions.status as status, count(*) as total')
            ->groupBy('productions.status')
            ->pluck('total', 'status')
            ->all();
    }

    protected function viewName(): string
    {
        return 'livewire.dispatchs.supervision.stack';
    }

    public function render()
    {
        $lists     = $this->lists;
        $legal     = $this->buildLegalTagsByNoteIds($lists->pluck('note_id')->all());
        $presenter = app(SupervisionControlRowPresenter::class);

        return view($this->viewName(), [
            'lists'        => $lists,
            'rows'         => $lists->getCollection()->map(fn (Production $item) => $presenter->present($item, $legal)),
            'statusCounts' => $this->statusCounts,
            'serviceModel' => $this->serviceModel,
            'partner'      => $this->partner,
        ]);
    }
}
