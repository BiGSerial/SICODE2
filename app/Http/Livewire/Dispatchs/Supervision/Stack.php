<?php

namespace App\Http\Livewire\Dispatchs\Supervision;

use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Http\Livewire\Services\Concerns\BuildsLegalNoteTags;
use App\Models\{City, Production, Service, User, WorkReportFlowProduction};
use App\Services\Supervision\SupervisionControlRowPresenter;
use App\Support\SicodeRules;
use App\Traits\WildcardFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\{LengthAwarePaginator, Paginator};
use Illuminate\Support\Facades\DB;
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
    use WildcardFormatter;

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

    public function exportToExcel()
    {
        \App\Jobs\Dispatchs\ExportDispatchSupervisionJob::dispatch([
            'service_id'  => $this->service,
            'search'      => $this->search,
            'multiSearch' => $this->multiSearch,
            'note_type'   => $this->note_type,
            'user_fs'     => $this->filterValues('user'),
        ], auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'info',
            'title' => 'Exportação iniciada!',
            'text'  => 'Você será notificado quando o arquivo estiver pronto.',
        ]);
    }

    // ---------- Consulta ----------

    /**
     * Recorte único da lista e dos cartões de status (mesmos joins e filtros nos dois). `$withStatus = false` ignora o filtro
     * de status, para os cartões contarem todos os status. Sem colunas calculadas: quem lista acrescenta as suas.
     */
    private function controlQuery(bool $withStatus = true): Builder
    {
        $dispatchableWorkReportIds = app(\App\Services\Supervision\WorkReportSupervisionCandidateQuery::class)
            ->dispatchableWorkReportIdsSubquery();

        $user   = auth()->user();
        $users  = $this->filterValues('user');
        $status = $this->filterValues('status');
        $codes  = $this->locationCityCodes();

        return Production::Query()
            ->where('service_id', $this->service)
            ->where('completed', false)
            ->when($user?->usesContractDispatch(), function ($q) use ($user) {
                $companyIds = SicodeRules::visibleCompanyIdsFor($user);

                return count($companyIds)
                    ? $q->whereIn('productions.company_id', $companyIds)
                    : $q->whereRaw('0 = 1');
            })
            ->leftJoin('notes as n', 'productions.note_id', '=', 'n.id')
            ->leftJoin('work_report_flow_productions as wrfp', function ($join) {
                $join->on('wrfp.production_id', '=', 'productions.id')
                    ->where('wrfp.stage', WorkReportFlowProduction::STAGE_FISCALIZATION)
                    ->where('wrfp.is_current', true)
                    ->whereRaw("wrfp.id = (
                        SELECT MAX(wrfp_latest.id)
                        FROM work_report_flow_productions wrfp_latest
                        WHERE wrfp_latest.production_id = productions.id
                          AND wrfp_latest.stage = 'fiscalization'
                          AND wrfp_latest.is_current = 1
                    )");
            })
            ->leftJoin('work_reports as wr', function ($join) {
                $join->on('wr.id', '=', 'wrfp.work_report_id')
                    ->where('wr.canceled', false);
            })
            ->leftJoin('adsforms as af', 'wr.id', '=', 'af.work_report_id')
            ->where(function ($query) use ($dispatchableWorkReportIds) {
                $query->whereNull('wrfp.work_report_id')
                    ->orWhereIn('wrfp.work_report_id', $dispatchableWorkReportIds);
            })
            ->when(trim((string) $this->search), function ($q) {
                $q->where(function ($q) {
                    $search = $this->formatWithWildcard($this->search);
                    $q->where('n.note', $search->type, $search->search)
                        ->orWhere('n.rubrica', $search->type, $search->search)
                        ->orWhere('n.lexp', $search->type, $search->search)
                        ->orWhere('productions.odi', $search->type, $search->search)
                        ->orWhere('productions.odd', $search->type, $search->search)
                        ->orWhere('productions.ods', $search->type, $search->search)
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', $search->type, $search->search);
                        })
                        ->orWhereHas('note.orders', function ($q) use ($search) {
                            $q->where('ordem', $search->type, $search->search);
                        });
                });
            })
            ->when(count($this->multiSearch) > 0, function ($q) {
                $q->where(function ($q) {
                    $q->whereHas('note', function ($query) {
                        $query->whereIn('note', $this->multiSearch)
                              ->orWhere('rubrica', $this->multiSearch)
                              ->orWhere('lexp', $this->multiSearch);
                    })
                    ->orWhereHas('user', function ($q) {
                        $q->whereIn('name', $this->multiSearch);
                    })
                    ->orWhereHas('note.orders', function ($q) {
                        $q->whereIn('ordem', $this->multiSearch);
                    });
                });
            })
            ->when($codes !== null, fn ($q) => $q->whereIn('nexp', $codes))
            ->when($withStatus && $status, fn ($q) => $q->whereIn('productions.status', $status))
            ->when($this->note_type, fn ($q) => $q->where('n.type_note', $this->note_type))
            ->when($users, fn ($q) => $q->whereIn('productions.user_id', $users));
    }

    public function getListsProperty()
    {
        $query = $this->controlQuery()
            ->addSelect('productions.*')
            ->addSelect(DB::raw("
                CASE
                    WHEN wr.informed_at IS NOT NULL THEN wr.informed_at
                    WHEN EXISTS (
                        SELECT 1 FROM partials p
                        WHERE p.note_id = n.id
                    ) THEN (
                        SELECT p.created_at
                        FROM partials p
                        WHERE p.note_id = n.id
                        ORDER BY p.created_at DESC
                        LIMIT 1
                    )
                    ELSE NULL
                END AS dt_inform
            "))
            ->addSelect(DB::raw('af.created_at AS dt_ads'))
            ->addSelect(DB::raw('af.amount AS ads_amount'))
            ->addSelect(DB::raw('af.tacit AS ads_tacit'))
            ->addSelect(DB::raw('af.tacit_delivered_at AS ads_tacit_delivered_at'))
            ->with([
                'wpas:id,production_id,dd,execstats,ststusexec,completed_at',
                'company:id,name',
                'user:id,name,company_id,deleted_at',
                'user.Company:id,name',
                'dispatcher:id,name,company_id,deleted_at',
                'dispatcher.Company:id,name',
                'note:id,note,nstats,dt_status,rubrica,postes,lexp,type_note,mesalization,days_left,group2',
                'note.Adsform:id,work_report_id,note_id,amount,tacit,tacit_delivered_at,created_at',
                'note.workform:id,company_id,note_id,informed_at,rejected',
                'note.workform.adsform:id,work_report_id,amount,tacit,tacit_delivered_at,created_at',
                'WorkReportFlowProductions' => fn ($q) => $q
                    ->where('stage', WorkReportFlowProduction::STAGE_FISCALIZATION)
                    ->where('is_current', true)
                    ->orderByDesc('id'),
            ])
            ->orderBy('priority', 'desc')
            ->orderBy('d5', 'desc')
            ->orderBy('partial', 'desc')
            ->orderByRaw('CASE WHEN dt_ads IS NULL THEN 0 ELSE 1 END DESC')
            ->orderBy('dt_inform', 'asc')
            ->orderBy('dt_ads', 'asc')
            ->orderBy('att_at', 'asc')
            ->orderBy('id', 'asc');

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
