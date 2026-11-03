<?php

namespace App\Http\Livewire\Dispatchs\Publication;

use App\Exports\Dispatchs\PublicationExportControl;
use App\Helpers\TextFormatter;
use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Models\{Analise, City, Company, Note, Production, Service, User, Wpa};
use App\Services\Publication\PublicationListRowPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\{Component, WithPagination};

/**
 * Despacho → Controle de Publicação. Lista de controle (docs/PADRAO_LISTAS_OPERACIONAIS.md §10): acompanha o que já foi despachado.
 * `PartnerStack` (parceira) herda tudo e só muda a exportação (fila) e a view.
 */
class Stack extends Component
{
    use WithPagination;
    use WithNoteListFilters;
    use TextFormatter;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $last_update;

    public $search;

    public $perPage = 100;

    public $advanceSearch;

    public $multiSearch = [];

    public $selectAll;

    public $selected = [];

    /** Tipo de nota: '1' Nota, '2' OV, '' ambos. */
    public $note_type = '';

    public $force = true;

    public $forcar = false;

    public $production;

    public $productions;

    protected $queryString = [
        'search'    => ['except' => '', 'as' => 'buscar'],
        'page'      => ['except' => 1, 'as' => 'p'],
        'perPage'   => ['as' => 'pp'],
        'note_type' => ['except' => '', 'as' => 'tipo'],
        'filters'   => ['except' => [], 'as' => 'f'],
    ];

    protected $listeners = [
        'refresh_list'         => '$refresh',
        'refresh_dispatch'     => '$refresh',
        'confirm_remove_att'   => 'remove_att',
        'getCopy'              => 'copy',
        'confirm_des_att_mass' => 'confirm_des_att_mass',
        'filterUser'           => 'filterUser',
        'closeall'             => 'closeall',
    ];

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;
    }

    /** Clique numa pessoa da Ocupação (menu lateral): filtra o controle por ela e reflete no filtro "Usuário". */
    public function filterUser($user_id)
    {
        $this->filters['user'] = [(string) $user_id];
        $this->afterListFiltersChanged();
    }

    public function updatedNoteType(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->multiSearch = [];
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
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

    public function clearAllCriteria(): void
    {
        $this->filters       = [];
        $this->search        = '';
        $this->multiSearch   = [];
        $this->advanceSearch = '';
        $this->note_type     = '';
        $this->afterListFiltersChanged();
    }

    // ---------- Seleção ----------

    public function setSelectAll()
    {
        $ids = $this->pageState()['lists']->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($this->selectAll) {
            $this->selected = array_values(array_unique(array_merge(array_map('intval', $this->selected), $ids)));
        } else {
            $this->selected = array_values(array_diff(array_map('intval', $this->selected), $ids));
        }
    }

    public function checkAllSelect($items)
    {
        $items = $items instanceof \Illuminate\Contracts\Pagination\Paginator ? $items->items() : $items;
        $ids   = collect($items)->pluck('id')->map(fn ($id) => (int) $id)->all();

        return $this->selectAll = $ids !== [] && array_diff($ids, array_map('intval', $this->selected)) === [];
    }

    // ---------- Exportação ----------

    public function export_excel()
    {
        if (!count($this->selected)) {
            return (new PublicationExportControl($this->exports))->download(date('YmdHis-') . 'PublicationControlExport.xlsx');
        }

        return (new PublicationExportControl($this->exports->whereIn('productions.id', $this->selected)))->download(date('YmdHis-') . 'PublicationControlExportSelected.xlsx');
    }

    // ---------- Ações em massa e de linha ----------

    public function go_priority_mass()
    {
        if (count($this->selected)) {
            $this->emit('setPriority', $this->selected);
        }
    }

    public function go_des_priority_mass()
    {
        if (count($this->selected)) {
            $this->emit('removePriority', $this->selected);
        }
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function get_single_note($prod, $force = false)
    {
        $this->force    = $force;
        $this->selected = [$prod];

        $this->go_att_mass();
    }

    public function go_att_mass()
    {
        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para atribuição!',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->emitTo('dispatchs.shared.dispatch-modal', 'openForProductions', array_values($this->selected));
    }

    public function go_des_att_mass()
    {
        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para desatribuição!',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->productions = Production::with('Note')->find($this->selected);

        $notes_not_valids = 0;

        if ($this->productions) {
            foreach ($this->productions as $production) {
                if (($production->status > 2 && !$this->forcar) || $production->completed) {
                    $notes_not_valids++;
                }
            }
        }

        if ($notes_not_valids > 0) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Confirmar Desatribuição Parcial',
                'msg'           => "{$notes_not_valids} das Das {$this->productions->count()} selecionadas, não atende(m) o critério para Desatribuição. Deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Desatribua!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_des_att_mass',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nota foi Desatribuída.',
            ]);
        } else {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Confirmar Desatribuição em Massa',
                'msg'           => "{$this->productions->count()} NOTAS/OVs estão prontas para serem desatribuídas. Deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Desatribua!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_des_att_mass',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nota foi Desatribuída.',
            ]);
        }
    }

    public function confirm_des_att_mass()
    {
        abort_unless(!auth()->user()?->usesContractDispatch(), 403);
        $erros = 0;
        $total = 0;

        if ($this->productions) {
            foreach ($this->productions as $production) {
                if (($production->status <= 2 || $this->forcar) && !$production->completed) {
                    $total++;

                    if ($analise = Analise::Where('production_id', $production->id)->first()) {
                        $analise->delete();
                    }

                    if ($wpa = Wpa::Where('production_id', $production->id)->get()->last()) {
                        $wpa->update(['production_id' => null]);
                    }

                    if (!$production->delete()) {
                        $erros++;
                    }
                }
            }

            if ($erros) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => "{$erros} de {$total} não foram desatribuídos.",
                ]);
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => "{$total} Notas/Ovs Desatribídas com sucesso",
                    'timer'    => 2500,
                ]);
            }
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhum registro de desatribuição. Repita o procedimento.',
                'timer'    => 2500,
            ]);
        }
    }

    public function to_remove_add($id)
    {
        $this->production = Production::with('User')->find($id);

        if ($this->production) {
            $name = $this->production->User ? $this->production->User->name : 'Desconhecido';

            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Confirmar Desatribuição',
                'msg'           => "Você está prestes a Desatribuir a produção para {$name}. Deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Remova!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_remove_att',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',
            ]);
        }
    }

    public function remove_att()
    {
        abort_unless(!auth()->user()?->usesContractDispatch(), 403);

        if ($this->production->update(['user_id' => '', 'status' => 1, 'completed' => false])) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Produção foi desatribuída com sucesso',
                'timer'    => 2500,
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ocorreu algum problema ao tentar remover a produção',
                'timer'    => 6000,
            ]);
        }

        $this->closeall();
    }

    // ---------- Filtros ----------

    protected function listFilterDefinitions(): array
    {
        $defs = [];

        if (!Auth()->User()->usesContractDispatch()) {
            $defs['company'] = ['label' => 'Empreiteira'];
        }

        return $defs + [
            'user'     => ['label' => 'Usuário'],
            'status'   => ['label' => 'Status'],
            'rubrica'  => ['label' => 'Rubrica'],
            'region'   => ['label' => 'Região'],
            'regional' => ['label' => 'Regional', 'depends' => ['region']],
            'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    protected function listFilterCacheScope(): string
    {
        $user = Auth()->User();

        return static::class . $this->service->uuid . ($user->usesContractDispatch() ? md5(json_encode(\App\Support\SicodeRules::visibleCompanyIdsFor($user))) : 'all');
    }

    protected function listFilterOptions(): array
    {
        $user    = Auth()->User();
        $visible = $user->usesContractDispatch() ? \App\Support\SicodeRules::visibleCompanyIdsFor($user) : null;
        $uuid    = $this->service->uuid;
        $open    = fn () => Production::query()
            ->where('service_id', $uuid)
            ->where('confirmed', false)
            ->when($visible, fn ($q) => $q->whereIn('company_id', $visible));

        // Quem tem Production na atividade (sem cache: a Ocupação filtra por gente que acabou de receber).
        $users    = $open()->whereNotNull('user_id')->distinct()->pluck('user_id');
        $statuses = $open()->distinct()->orderBy('status')->pluck('status');

        return [
            'company' => $this->rememberFilterOptions('company', fn () => Company::query()
                ->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['v' => (string) $c->id, 'l' => (string) $c->name])->all()),
            'user' => User::withTrashed()->whereIn('id', $users)->orderBy('name')->get(['id', 'name', 'deleted_at'])
                ->map(fn ($u) => ['v' => (string) $u->id, 'l' => $u->name . ($u->deleted_at ? ' (inativo)' : '')])->all(),
            'status'  => $statuses->map(fn ($s) => ['v' => (string) $s, 'l' => \App\Custom\Notestatus::status($s)->status])->values()->all(),
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => DB::table('productions')
                ->join('notes', 'notes.id', '=', 'productions.note_id')
                ->where('productions.service_id', $uuid)->where('productions.confirmed', false)
                ->whereNotNull('notes.rubrica')->where('notes.rubrica', '!=', '')
                ->distinct()->orderBy('notes.rubrica')->pluck('notes.rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
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

    // ---------- Consultas ----------

    /**
     * Consulta única da lista, do Excel e dos cartões de status: o mesmo recorte nos três (antes eram três cópias, com regras
     * diferentes). `$withStatus = false` ignora o filtro de status, para os cartões contarem todos os status.
     */
    private function controlQuery(bool $withStatus = true): Builder
    {
        $user    = Auth()->User();
        $company = $this->filterValues('company');
        $users   = $this->filterValues('user');
        $status  = $this->filterValues('status');
        $rubrica = $this->filterValues('rubrica');
        $base    = $this->base;
        $term    = $this->search;
        $multi   = $this->multiSearch;

        return Production::query()
            ->where('productions.confirmed', false)
            ->where('productions.service_id', $this->service->uuid)
            ->when($user->usesContractDispatch(), fn ($q) => $q->whereIn('productions.company_id', \App\Support\SicodeRules::visibleCompanyIdsFor($user)))
            ->when($company, fn ($q) => $q->whereRelation('Note.WorkForm', fn ($w) => $w->whereIn('company_id', $company)))
            ->when($users, fn ($q) => $q->whereIn('productions.user_id', $users))
            ->when($withStatus && $status, fn ($q) => $q->whereIn('productions.status', $status))
            ->when($rubrica, fn ($q) => $q->whereHas('Note', fn ($n) => $n->whereIn('rubrica', $rubrica)))
            ->when($base, fn ($q) => $q->whereHas('Note', fn ($n) => $n->where(fn ($w) => $w->whereIn('nexp', $base)->orWhereNull('nexp')->orWhere('nexp', ''))))
            ->when($multi, fn ($q) => $q->whereHas('Note', fn ($n) => $n->whereIn('note', $multi)->orWhereRelation('Orders', fn ($o) => $o->whereIn('ordem', $multi))))
            ->when(!$multi && $term, fn ($q) => $q->whereHas('Note', fn ($n) => $n->where(function ($w) use ($term) {
                $like = '%' . $term . '%';
                $w->where('note', 'like', $like)
                    ->orWhere('group2', 'like', $like)->orWhere('group3', 'like', $like)->orWhere('group4', 'like', $like)->orWhere('group5', 'like', $like)
                    ->orWhere('numPedido', 'like', $like)->orWhere('material', 'like', $like)->orWhere('lexp', 'like', $like)
                    ->orWhere('rubrica', 'like', $like)->orWhere('centerjob', 'like', $like);
            })))
            ->when($this->note_type, fn ($q) => $q->whereRelation('Note', 'type_note', $this->note_type));
    }

    public function getListsProperty()
    {
        return $this->controlQuery()
            ->with([
                'Note.WorkForm:id,note_id,company_id,informed_at,created_at,rejected,canceled',
                'Note.WorkForm.Orders',
                'Note.RamalForm:id,note_id',
                'Note.RamalForm.Orders',
                'Company:id,name,deleted_at',
                'User',
            ])
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->select(
                'productions.*',
                'notes.dt_created as note_dt_created',
                DB::raw("
                    CASE
                        WHEN notes.type_note = 2 THEN DATE_ADD(CURDATE(), INTERVAL notes.days_left DAY)
                        WHEN notes.type_note = 1 THEN STR_TO_DATE(CONCAT('28/', SUBSTRING(notes.mesalization, 2, 2), '/', SUBSTRING(notes.mesalization, 5)), '%d/%m/%Y')
                        ELSE NULL
                    END as prazo_final
                ")
            )
            ->orderBy('priority', 'DESC')
            ->orderBy('notes.is45', 'DESC')
            ->orderBy('d5', 'DESC')
            ->orderByRaw('
                (exists (select 1 from ramal_reports where ramal_reports.note_id = productions.note_id)
                and not exists (select 1 from work_reports where work_reports.note_id = productions.note_id)) desc
            ')
            ->orderBy('prazo_final', 'ASC');
    }

    /** Consulta do Excel (e do Excel da parceira, que roda em fila): o mesmo recorte da lista, com os filtros ativos. */
    public function getExportsProperty()
    {
        return $this->controlQuery()
            ->with(['Note.WorkForm.Orders', 'Note.RamalForm', 'Company', 'User'])
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->select('productions.*', 'notes.dt_created as note_dt_created')
            ->orderBy('priority', 'DESC')
            ->orderBy('d5', 'DESC');
    }

    /** Contagem por status (cartões do painel), no mesmo recorte da lista mas sem o filtro de status. */
    public function getStatusCountsProperty(): array
    {
        return $this->controlQuery(false)
            ->reorder()
            ->selectRaw('productions.status as status, count(*) as total')
            ->groupBy('productions.status')
            ->pluck('total', 'status')
            ->all();
    }

    /** Página paginada e apresentada, calculada uma vez por requisição. */
    private ?array $pageMemo = null;

    /** @return array{lists: \Illuminate\Contracts\Pagination\LengthAwarePaginator, rows: \Illuminate\Support\Collection} */
    private function pageState(): array
    {
        return $this->pageMemo ??= (function () {
            $lists     = $this->getListsProperty()->paginate($this->perPage);
            $presenter = app(PublicationListRowPresenter::class);

            return [
                'lists' => $lists,
                'rows'  => $lists->getCollection()->map(fn (Production $p) => $presenter->presentControl($p))->values(),
            ];
        })();
    }

    // ---------- Busca em massa / fechar ----------

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->selected      = [];
        $this->advanceSearch = '';
        $this->gotoPage(1);

        $this->emitSelf('refresh_list');
    }

    public function clean()
    {
        $this->multiSearch = [];
    }

    public function buscarMulti()
    {
        $this->gotoPage(1);

        $this->search      = '';
        $this->multiSearch = array_values($this->formatTextToArray((string) $this->advanceSearch));

        $this->clearSelection();
        $this->dispatchBrowserEvent('hideModal');
    }

    /** Notas da página com demanda jurídica vinculada: 2 consultas no total, em vez de 2 por linha. */
    private function legalLinkedNoteIds(array $noteIds): array
    {
        if (!$noteIds) {
            return [];
        }

        $instructions = DB::table('legal_demand_note_instructions')->whereIn('note_id', $noteIds)->where('active', true)->pluck('note_id');
        $links        = DB::table('legal_demand_note_activity_links')->whereIn('note_id', $noteIds)->whereNull('unlinked_at')->pluck('note_id');

        return array_fill_keys($instructions->merge($links)->unique()->all(), true);
    }

    protected function viewName(): string
    {
        return 'livewire.dispatchs.publication.stack';
    }

    /** Rota da lista operacional da atividade (botão do hero). */
    protected function mainRouteName(): string
    {
        return 'dispatch.main';
    }

    public function render()
    {
        ['lists' => $lists, 'rows' => $rows] = $this->pageState();

        return view($this->viewName(), [
            'lists'        => $lists,
            'rows'         => $rows,
            'legalNoteIds' => $this->legalLinkedNoteIds($rows->pluck('noteId')->unique()->all()),
            'statusCounts' => $this->statusCounts,
            'mainRoute'    => route($this->mainRouteName(), ['service' => $this->service->uuid]),
        ]);
    }
}
