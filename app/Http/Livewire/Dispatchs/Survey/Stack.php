<?php

namespace App\Http\Livewire\Dispatchs\Survey;

use App\Custom\Notestatus;
use App\Helpers\TextFormatter;
use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Jobs\Dispatchs\ExportDispatchSurveyJob;
use App\Models\{City, Production, Service, User};
use App\Services\Survey\SurveyListRowPresenter;
use App\Support\SicodeRules;
use App\Traits\WildcardFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\{Component, WithPagination};

/**
 * Controle do Levantamento (lista de acompanhamento do que já foi despachado). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10.
 * A parceira (`PartnerStack`) é esta mesma tela com outra view de entrada.
 */
class Stack extends Component
{
    use Concerns\LoadsLegalNoteLinks;
    use TextFormatter;
    use WildcardFormatter;
    use WithNoteListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 50;

    public $search = '';

    public $advancedSearch;

    public $multiSearch = [];

    public $note_type = '';

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
        'getCopy'      => 'copy',
        'filterUser'   => 'filterUser',
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->with('Status')->firstOrFail();
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

    // ---------- Busca e atalhos ----------

    public function updatedSearch(): void
    {
        if (!trim((string) $this->search)) {
            $this->resetPage();

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

        $this->multiSearch = array_values($this->formatTextToArray($this->advancedSearch));

        if (count($this->multiSearch) > 0) {
            $this->search         = '';
            $this->advancedSearch = null;
            $this->resetPage();
            $this->dispatchBrowserEvent('hideModal');
        }
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

    // ---------- Filtros (WithListFilters) ----------

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

    protected function listFilterCacheScope(): string
    {
        return static::class . $this->service->uuid;
    }

    protected function listFilterOptions(): array
    {
        $uuid = $this->service->uuid;

        // Quem tem atividade em aberto (sem cache: a Ocupação filtra por gente que acabou de receber).
        $users = Production::query()
            ->where('service_id', $uuid)->where('completed', false)
            ->whereNotNull('user_id')
            ->distinct()->pluck('user_id');

        $statuses = Production::query()
            ->where('service_id', $uuid)->where('completed', false)
            ->distinct()->orderBy('status')->pluck('status');

        return [
            'user' => User::withTrashed()->whereIn('id', $users)->orderBy('name')->get(['id', 'name', 'deleted_at'])
                ->map(fn ($u) => ['v' => (string) $u->id, 'l' => $u->name . ($u->deleted_at ? ' (inativo)' : '')])->all(),
            'status' => $statuses->map(fn ($s) => ['v' => (string) $s, 'l' => Notestatus::status($s)->status])->values()->all(),
        ] + $this->locationFilterOptions();
    }

    /** Códigos de município (`notes.nexp`) dos filtros Região/Regional/Município; vazio sem filtro de localidade. */
    public function getBaseProperty(): array
    {
        $region = $this->filterValues('region');
        $local  = $this->filterValues('regional');
        $city   = $this->filterValues('city');

        if (!$region && !$local && !$city) {
            return [];
        }

        return City::query()
            ->when($region, fn ($q) => $q->whereIn('regiao', $region))
            ->when($local, fn ($q) => $q->whereIn('baseConstrucao', $local))
            ->when($city, fn ($q) => $q->whereIn('rdMunicipio', $city))
            ->pluck('rdMunicipio')->filter()->unique()->values()->all();
    }

    // ---------- Exportação ----------

    public function exportToExcel()
    {
        ExportDispatchSurveyJob::dispatch([
            'service_uuid' => $this->service->uuid,
            'search'       => $this->search,
            'multiSearch'  => $this->multiSearch,
            'note_type'    => $this->note_type,
            'user_fs'      => $this->filterValues('user'),
        ], auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'info',
            'title' => 'Exportação iniciada!',
            'text'  => 'Você será notificado quando o arquivo estiver pronto.',
        ]);
    }

    // ---------- Consulta única (lista, cartões de status) ----------

    /**
     * Consulta única da lista e dos cartões de status: o mesmo recorte nos dois. `$withStatus = false` ignora o filtro de
     * status, para os cartões contarem todos os status.
     */
    private function controlQuery(bool $withStatus = true): Builder
    {
        $user   = Auth()->User();
        $users  = $this->filterValues('user');
        $status = $this->filterValues('status');
        $base   = $this->base;
        $multi  = $this->multiSearch;
        $term   = $this->search;

        return Production::query()
            ->where('productions.service_id', $this->service->uuid)
            ->where('productions.completed', false)
            ->when($user?->usesContractDispatch(), function ($q) use ($user) {
                $companyIds = SicodeRules::visibleCompanyIdsFor($user);

                return count($companyIds)
                    ? $q->whereIn('productions.company_id', $companyIds)
                    : $q->whereRaw('0 = 1');
            })
            ->leftJoin('notes as n', 'productions.note_id', '=', 'n.id')
            ->when(!$multi && trim((string) $term), function ($q) use ($term) {
                $q->where(function ($q) use ($term) {
                    $search = $this->formatWithWildcard($term);
                    $q->where('n.note', $search->type, $search->search)
                        ->orWhere('n.rubrica', $search->type, $search->search)
                        ->orWhere('n.lexp', $search->type, $search->search)
                        ->orWhere('productions.odi', $search->type, $search->search)
                        ->orWhere('productions.odd', $search->type, $search->search)
                        ->orWhere('productions.ods', $search->type, $search->search)
                        ->orWhereHas('user', fn ($u) => $u->where('name', $search->type, $search->search))
                        ->orWhereHas('note.orders', fn ($o) => $o->where('ordem', $search->type, $search->search));
                });
            })
            ->when($multi, function ($q) use ($multi) {
                $q->where(function ($q) use ($multi) {
                    $q->whereHas('note', fn ($n) => $n->whereIn('note', $multi)->orWhereIn('rubrica', $multi)->orWhereIn('lexp', $multi))
                        ->orWhereHas('user', fn ($u) => $u->whereIn('name', $multi))
                        ->orWhereHas('note.orders', fn ($o) => $o->whereIn('ordem', $multi));
                });
            })
            ->when($base, fn ($q) => $q->whereIn('n.nexp', $base))
            ->when($users, fn ($q) => $q->whereIn('productions.user_id', $users))
            ->when($withStatus && $status, fn ($q) => $q->whereIn('productions.status', $status))
            ->when($this->note_type, fn ($q) => $q->where('n.type_note', $this->note_type));
    }

    public function getListsProperty()
    {
        return $this->controlQuery()
            ->addSelect('productions.*')
            ->addSelect(DB::raw('n.dt_created as dt_created'))
            ->with([
                'wpas:id,production_id,dd,execstats,ststusexec,completed_at',
                'company:id,name',
                'user:id,name,company_id,deleted_at',
                'user.Company:id,name',
                'dispatcher:id,name,company_id,deleted_at',
                'dispatcher.Company:id,name',
                'note:id,note,dt_created,nstats,dt_status,rubrica,postes,lexp,type_note,mesalization,days_left,group2',
            ])
            ->orderBy('priority', 'desc')
            ->orderBy('d5', 'desc')
            ->orderBy('dt_created', 'asc')
            ->orderBy('att_at', 'asc')
            ->orderBy('productions.id', 'asc')
            ->paginate($this->perPage);
    }

    /** Contagem por status (cartões do painel), no mesmo recorte da lista mas sem o filtro de status. */
    public function getStatusCountsProperty(): array
    {
        return $this->controlQuery(false)
            ->reorder()
            ->selectRaw('productions.status as status, count(*) as total')
            ->groupBy('productions.status')
            ->orderBy('productions.status')
            ->pluck('total', 'status')
            ->all();
    }

    /** View de entrada (a parceira troca só esta). */
    protected function viewName(): string
    {
        return 'livewire.dispatchs.survey.stack';
    }

    public function render()
    {
        $lists     = $this->lists;
        $presenter = app(SurveyListRowPresenter::class);

        return view($this->viewName(), [
            'lists'        => $lists,
            'rows'         => $lists->getCollection()->map(fn (Production $item) => $presenter->presentControl($item)),
            'legalNoteIds' => $this->legalLinkedNoteIds($lists->pluck('note_id')->all()),
            'statusCounts' => $this->statusCounts,
        ]);
    }
}
