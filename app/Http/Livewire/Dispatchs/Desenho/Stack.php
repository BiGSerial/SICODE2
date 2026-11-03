<?php

namespace App\Http\Livewire\Dispatchs\Desenho;

use App\Exports\DispatchDesenhoStack;
use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Http\Livewire\Services\Concerns\BuildsLegalNoteTags;
use App\Models\{Analise, Company, Note, Notetimeline, Production, Service, User, Wpa};
use App\Models\City;
use App\Services\Dispatchs\DesignListRowPresenter;
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\{Cache, DB};
use Livewire\{Component, WithPagination};

class Stack extends Component
{
    use BuildsLegalNoteTags;
    use WithNoteListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // VAr System
    public $service;

    public $last_update;

    public $search;

    public $perPage = 100;

    public $advanceSearch;

    public $multiSearch = [];

    public $notes;

    public $selectAll;

    public $selected = [];

    public $company_s;

    public $user_s;

    public $type = '2';

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
        'confirm_remove_att'   => 'remove_att',
        'confirm_dispatch'     => 'confirmed_att',
        'getCopy'              => 'copy',
        'confirm_des_att_mass' => 'confirm_des_att_mass',
        'filterUser'           => 'filterUser',
        'closeall'             => 'closeall',
    ];

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('desenho-dispatch:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
    }

    /** Clique numa pessoa da Ocupação (menu lateral): filtra o controle por ela e reflete no filtro "Usuário". */
    public function filterUser($user_id)
    {
        $this->filters['user'] = [(string) $user_id];
        $this->afterListFiltersChanged();
    }

    // ---------- Filtros (WithNoteListFilters) ----------

    protected function listFilterDefinitions(): array
    {
        $defs = [];

        if (!Auth()->User()->usesContractDispatch()) {
            $defs['company'] = ['label' => 'Empresa'];
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

        return static::class . $this->service->uuid . ($user->usesContractDispatch() ? md5(json_encode(SicodeRules::visibleCompanyIdsFor($user))) : 'all');
    }

    protected function listFilterOptions(): array
    {
        $user      = Auth()->User();
        $visible   = $user->usesContractDispatch() ? SicodeRules::visibleCompanyIdsFor($user) : null;
        $uuid      = $this->service->uuid;
        $inControl = fn () => Production::query()
            ->where('service_id', $uuid)
            ->where('confirmed', false)
            ->when($visible, fn ($q) => $q->whereIn('company_id', $visible));

        // Quem tem Production na atividade (sem cache: a Ocupação filtra por gente que acabou de receber).
        $users    = $inControl()->whereNotNull('user_id')->distinct()->pluck('user_id');
        $statuses = $inControl()->distinct()->orderBy('status')->pluck('status');

        return [
            'company' => $this->rememberFilterOptions('company', fn () => Company::query()
                ->whereHas('toUsers', fn ($q) => $q->whereRelation('ToServices', fn ($s) => $s->where('service_id', $uuid)->where('service', true)))
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

    public function updatedNoteType(): void
    {
        $this->selected  = [];
        $this->selectAll = false;
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

    /** "Limpar filtros": filtros, busca (simples e em massa) e tipo. Não mexe na quantidade por página. */
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
        if (!$this->lists) {
            return;
        }

        $selectedSet = array_fill_keys(array_map('intval', $this->selected), true);

        foreach ($this->lists->items() as $production) {
            if ($this->selectAll) {
                $selectedSet[(int) $production->id] = true;
            } else {
                unset($selectedSet[(int) $production->id]);
            }
        }

        $this->selected = array_map('intval', array_keys($selectedSet));
    }

    protected function recomputeSelectAllFor(array $items): void
    {
        $page = array_map(fn ($production) => (int) $production->id, $items);

        $this->selectAll = $page !== [] && array_diff($page, array_map('intval', $this->selected)) === [];
    }

    // ---------- Ações ----------

    public function export_excel()
    {
        $query = $this->exports->with(['Note', 'User', 'Company']);

        if (count($this->selected)) {
            $query->whereIn('productions.id', $this->selected);
        }

        $notes = $query->get()->sortBy('Note.days_left');

        return (new DispatchDesenhoStack($notes))->download(date('YmdHis-') . 'exportNotesDesenho.xlsx');
    }

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
        $this->clean();

        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para atribuição!',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->productions = Production::find($this->selected);

        $this->notes = Note::whereHas('Productions', function ($query) {
            return $query->whereIn('id', $this->selected);
        })->get();

        if ($this->notes->count()) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'add_mass_notes',
            ]);
        }
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

            return;
        }
    }

    public function confirm_att()
    {
        if ($this->type == '2') {

            if (!$this->user_s) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Nenhum usuário foi selecionado para despacho individual!',
                    'timer'    => 2500,
                ]);

                return;
            }

            $para = User::find($this->user_s)->name . ' da ' . (Company::find($this->company_s))->name;
        } else {

            if (!$this->company_s) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Nenhuma empresa foi selecionada para despacho!',
                    'timer'    => 2500,
                ]);

                return;
            }

            $para = (Company::find($this->company_s))->name;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Confirmar Atribuir',
            'msg'           => "Você está prestes a Atribuir {$this->notes->count()} nota(s) para {$para}",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Despache!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_dispatch',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

        ]);
    }

    public function confirmed_att()
    {
        $this->force = true;

        if ($this->type == '2' || $this->force) {

            foreach ($this->notes as $key => $note) {

                $production = $this->productions->where('note_id', $note->id)->first();

                if ($production) {

                    if ($production->update([
                        'user_id'    => $this->user_s,
                        'company_id' => $this->company_s,
                        'att_by'     => Auth()->User()->id,
                        'att_at'     => date('Y-m-d H:i:s'),
                        'status'     => $this->user_s ? 2 : 1,
                        'completed'  => false,
                        'block'      => false,
                    ])) {

                        if (trim($this->user_s)) {
                            $user_info = 'Atribuiu a NOTA/OV para: ' . User::find($this->user_s) ? (User::find($this->user_s))->name : 'Desconhecido';
                        } else {
                            $user_info = 'Despachou a NOTA/OV para:' . Company::find($this->company_s) ? (Company::find($this->company_s))->name : 'Desconhecido';
                        }

                        Notetimeline::Create([
                            'note_id'       => $production->note_id,
                            'service_id'    => $production->service_id,
                            'user_id'       => Auth()->User()->id,
                            'info'          => "{$user_info}",
                            'status'        => $this->user_s ? 2 : 1,
                            'production_id' => $production->id,
                        ]);
                    } else {
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'error',
                            'title'    => 'Erro ao atribuir as notas!',
                            'timer'    => 2500,
                        ]);

                        return;
                    }

                } else {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'error',
                        'title'    => 'Erro ao atribuir as notas!',
                        'timer'    => 2500,
                    ]);

                    return;
                }
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Notas Despachadas com sucesso!',
                'timer'    => 2500,
            ]);

        }

        $this->closeall();
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

            $this->closeall();

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ocorreu algum problema ao tentar remover a produção',
                'timer'    => 6000,
            ]);

            $this->closeall();
        }
    }

    // ---------- Consulta ----------

    /**
     * Consulta única da lista, do Excel e dos cartões de status: o mesmo recorte nos três (antes eram três cópias, com
     * regras diferentes). `$withStatus = false` ignora o filtro de status, para os cartões contarem todos os status.
     */
    protected function controlQuery(bool $withStatus = true): Builder
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
            ->when($user->usesContractDispatch(), fn ($q) => $q->whereIn('productions.company_id', SicodeRules::visibleCompanyIdsFor($user)))
            ->when($company, fn ($q) => $q->whereIn('productions.company_id', $company))
            ->when($users, fn ($q) => $q->whereIn('productions.user_id', $users))
            ->when($withStatus && $status, fn ($q) => $q->whereIn('productions.status', $status))
            ->when($rubrica, fn ($q) => $q->whereHas('Note', fn ($n) => $n->whereIn('rubrica', $rubrica)))
            ->when($base, fn ($q) => $q->whereHas('Note', fn ($n) => $n->where(fn ($w) => $w->whereIn('nexp', $base)->orWhereNull('nexp')->orWhere('nexp', ''))))
            ->when($multi, fn ($q) => $q->whereHas('Note', fn ($n) => $n->whereIn('note', $multi)))
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
            ->with(['Note', 'User', 'Company'])
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->orderBy('notes.is45', 'DESC')
            ->orderBy('productions.priority', 'DESC')
            ->orderBy('productions.d5', 'DESC')
            ->orderBy('notes.type_note', 'DESC')
            ->orderBy('notes.days_left', 'ASC')
            ->orderBy('notes.dt_status', 'ASC')
            ->select('productions.*', 'notes.dt_created as note_dt_created', 'notes.dt_status as note_dt_status', 'notes.days_left')
            ->paginate($this->perPage);
    }

    /** Builder do Excel (também lido pelo Excel da parceira): mesmo recorte da lista, sem paginação. */
    public function getExportsProperty()
    {
        return $this->controlQuery()
            ->with(['Note'])
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->orderBy('notes.is45', 'DESC')
            ->orderBy('productions.priority', 'DESC')
            ->orderBy('notes.type_note', 'DESC')
            ->orderBy('notes.days_left', 'asc')
            ->select('productions.*', 'notes.dt_created as note_dt_created');
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

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->company_s     = '';
        $this->selected      = [];
        $this->user_s        = '';
        $this->type          = '';
        $this->advanceSearch = '';
        $this->gotoPage(1);

        $this->emitSelf('refresh_list');
    }

    public function clean()
    {
        $this->company_s   = '';
        $this->user_s      = '';
        $this->type        = '';
        $this->multiSearch = [];
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->search = '';
            $this->resetPage();

            $this->multiSearch = collect(preg_split('/[\s,;\n\r\t]+/', (string) $this->advanceSearch))
                ->map(fn ($term) => trim((string) $term))
                ->filter()
                ->unique()
                ->values()
                ->all();
        } else {
            $this->multiSearch = [];
        }

        if (count($this->multiSearch)) {
            $this->selected  = [];
            $this->selectAll = false;
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    protected function viewName(): string
    {
        return 'livewire.dispatchs.desenho.stack';
    }

    public function render()
    {
        $lists = $this->lists;

        $this->recomputeSelectAllFor($lists->items());

        // Empresas e usuários do modal "Atribuir em massa": só quando o modal tem notas para atribuir.
        $companies = collect();
        $users     = collect();

        if ($this->notes && $this->notes->count()) {
            $companies = Company::whereHas('toUsers', function ($query) {
                $query->whereRelation('ToServices', function ($q) {
                    $q->where('service_id', $this->service->uuid)
                        ->where('service', true);
                });
            })->orderBy('name', 'ASC')->get();

            $users = User::whereRelation('ToServices', function ($q) {
                $q->where('service_id', $this->service->uuid)
                    ->where('service', true);
            })
                ->where(function ($q) {
                    $q->whereRelation('Company', 'company_id', $this->company_s)
                        ->orWhere('company_id', $this->company_s);
                })
                ->orderBy('name', 'ASC')->get();
        }

        $presenter = app(DesignListRowPresenter::class);

        return view($this->viewName(), [
            'lists'             => $lists,
            'rows'              => $lists->getCollection()->map(fn (Production $production) => $presenter->control($production)),
            'statusCounts'      => $this->statusCounts,
            'legalTagsByNoteId' => $this->buildLegalTagsByNoteIds($lists->pluck('note_id')->all()),
            'companies'         => $companies,
            'users'             => $users,
        ]);
    }
}
