<?php

namespace App\Http\Livewire\Dispatchs\AnalisesPre;

use App\Exports\DispatchDesenhoStack;
use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Models\{Analise, City, Company, Note, Notetimeline, Production, Service, User, Wpa};
use App\Services\Dispatch\PreAnalysisListRowPresenter;
use App\Services\Production\ProductionActions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\{Cache, DB};
use Livewire\{Component, WithPagination};

/**
 * Despacho -> Controle de Análises Pré (lista de controle, padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md §10).
 * A Parceira (`PartnerStack`) herda tudo e só troca a exportação.
 */
class Stack extends Component
{
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

    public $note;

    public $notes;

    public $enter_dd;

    public $selectAll = false;

    public $selected = [];

    public $company_s;

    public $user_s;

    public $type = '2';

    public $additionalData = [];

    public $note_type = '';

    public $force = true;

    public $forcar = false;

    public $production;

    public $productions;

    /** Production alvo de uma confirmação pendente (Re-atribuir / Remover despacho). */
    public ?int $pendingProductionId = null;

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
        'confirm_reatt'        => 'confirmReattribute',
        'confirm_delete'       => 'confirmRemove',
        'closeall'             => 'closeall',
    ];

    private ?LengthAwarePaginator $listsMemo = null;

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('pre-analysis-dispatch:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
    }

    /** Clique numa pessoa da Ocupação (menu lateral): filtra o controle por ela e reflete no filtro "Usuário". */
    public function filterUser($user_id)
    {
        $this->filters['user'] = [(string) $user_id];
        $this->afterListFiltersChanged();
    }

    private function productionInControl(int $id): Production
    {
        // Só age em Production visível neste controle (atividade, empresa e filtros de contrato).
        return $this->controlQuery(false)->with(['Note:id,note', 'User:id,name'])->findOrFail($id);
    }

    public function askReattribute(int $id): void
    {
        $production = $this->productionInControl($id);
        abort_unless(app(ProductionActions::class)->canReattribute($production), 403);

        $this->pendingProductionId = $id;
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Re-atribuir',
            'msg'           => "Você deseja reatribuir a Nota/Ov <strong>{$production->Note->note}</strong> para <strong>{$production->User?->name}</strong>?",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Re-atribua!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_reatt',
            'chave'         => hash('sha512', (string) $id),
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Nota/OV foi reatribuída.',
        ]);
    }

    public function confirmReattribute($chave): void
    {
        $id = (int) $this->pendingProductionId;

        if (!$id || !hash_equals(hash('sha512', (string) $id), (string) $chave)) {
            return;
        }

        $this->pendingProductionId = null;
        $production                = $this->productionInControl($id);
        abort_unless(app(ProductionActions::class)->canReattribute($production), 403);

        $ok = app(ProductionActions::class)->reattribute($production);

        $this->dispatchBrowserEvent('swal', $ok
            ? ['position' => 'center', 'icon' => 'success', 'title' => 'Nota/Ov Re-atribuída com sucesso!', 'timer' => 2500]
            : ['position' => 'center', 'icon' => 'error', 'title' => 'Ocorreu um erro ao tentar re-atribuir a nota/ov.', 'timer' => 5000]);
    }

    public function askRemove(int $id): void
    {
        $production = $this->productionInControl($id);
        abort_unless(app(ProductionActions::class)->canRemove($production), 403);

        $this->pendingProductionId = $id;
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'REMOVER DESPACHO',
            'msg'           => "Você está prestes a remover {$production->Note->note} da produção. Esteja ciente ao fazer isso de forma inadequada poderá prejudicar a medição do usuário ou empresa.\nLembrando que a exclusão também removerá do LOG do BI.\nDeseja Continuar?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Remova!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_delete',
            'chave'         => hash('sha512', (string) $id),
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum despacho foi removido.',
        ]);
    }

    public function confirmRemove($chave): void
    {
        $id = (int) $this->pendingProductionId;

        if (!$id || !hash_equals(hash('sha512', (string) $id), (string) $chave)) {
            return;
        }

        $this->pendingProductionId = null;
        $production                = $this->productionInControl($id);
        abort_unless(app(ProductionActions::class)->canRemove($production), 403);

        $ok = app(ProductionActions::class)->remove($production);

        $this->dispatchBrowserEvent('swal', $ok
            ? ['position' => 'center', 'icon' => 'success', 'title' => 'Produção removida com sucesso', 'timer' => 2500]
            : ['position' => 'center', 'icon' => 'error', 'title' => 'Ocorreu algum problema ao tentar remover a produção', 'timer' => 6000]);
    }

    public function updatedNoteType(): void
    {
        $this->selected  = [];
        $this->selectAll = false;
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

    public function clearSelection(): void
    {
        $this->selected  = [];
        $this->selectAll = false;
    }

    public function setSelectAll()
    {
        $selectedSet = array_fill_keys(array_map('intval', $this->selected), true);

        foreach ($this->paginatedLists()->items() as $production) {
            if ($this->selectAll) {
                $selectedSet[(int) $production->id] = true;
            } else {
                unset($selectedSet[(int) $production->id]);
            }
        }

        $this->selected = array_map('intval', array_keys($selectedSet));
    }

    public function checkAllSelect($items)
    {
        $this->recomputeSelectAllFor($items instanceof LengthAwarePaginator ? $items->items() : $items);

        return $this->selectAll;
    }

    protected function recomputeSelectAllFor(iterable $items): void
    {
        $page = [];

        foreach ($items as $production) {
            $page[] = (int) $production->id;
        }

        $this->selectAll = $page !== [] && array_diff($page, array_map('intval', $this->selected)) === [];
    }

    public function export_excel()
    {
        if (!count($this->selected)) {
            return (new DispatchDesenhoStack($this->exports->get()))->download(date('YmdHis-') . 'exportNotesDesenho.xlsx');
        } else {
            $notes = Production::WhereIn('id', $this->selected)->With('Note', 'User', 'Company')->get()->sortBy('Note.dt_created');

            return (new DispatchDesenhoStack($notes))->download(date('YmdHis-') . 'exportNotesDesenho.xlsx');
        }
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

                // $production = Production::create([
                //     'note_id' => $note->id,
                //     'service_id' => $this->service->uuid,
                //     'user_id' => $this->user_s,
                //     'company_id' => $this->company_s,
                //     'dispatch_by' => Auth()->User()->id,
                //     'att_by' => Auth()->User()->id,
                //     'dt_note' => $note->dt_status,
                //     'status_note' => $note->nstats,
                //     'dispatch_at' => date('Y-m-d H:i:s'),
                //     'att_at' => date('Y-m-d H:i:s'),
                //     'status' => 2,
                // ]);

                $production = $this->productions->where('note_id', $note->id)->first();

                if ($production) {

                    // $update = Production::find($production->id);

                    // dd($update);

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

                        // Wpa::create([
                        //     'production_id' => $production->id,
                        //     'note_id' => $note->id,
                        //     'dd' => $this->additionalData[$key]
                        // ]);
                    } else {

                        // dd($production);

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'error',
                            'title'    => 'Erro ao atribuir as notas!',
                            'timer'    => 2500,
                        ]);

                        return;
                    }

                } else {
                    // dd($production, $note->note);

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

    /**
     * Inserir as DDs ás notas em massa
     *
     * @return void
     */
    public function add_dd()
    {
        if (!trim($this->enter_dd)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma empresa foi selecionada para despacho!',
                'timer'    => 5000,
            ]);

            return;
        }

        $linhas = explode("\n", trim($this->enter_dd));

        if ($linhas && count($linhas)) {

            foreach ($linhas as $linha) {

                if ($linha) {

                    $coluna = explode("\t", $linha);

                    if (preg_match('/^[0-9]+$/', $coluna[0]) && preg_match('/^[0-9]+$/', $coluna[1])) {

                        $index = $this->notes->search(function ($note) use ($coluna) {
                            return $note->note == $coluna[0];
                        });

                        if ($index !== false) {
                            $this->additionalData[$index] = $coluna[1];
                        }
                    }

                }
            }

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

    // ---------- Filtros (WithListFilters) ----------

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

        return static::class . $this->service->uuid . ($user->usesContractDispatch() ? md5(json_encode(\App\Support\SicodeRules::visibleCompanyIdsFor($user))) : 'all');
    }

    protected function listFilterOptions(): array
    {
        $user     = Auth()->User();
        $contract = $user->usesContractDispatch();
        $visible  = $contract ? \App\Support\SicodeRules::visibleCompanyIdsFor($user) : null;
        $uuid     = $this->service->uuid;

        // Quem tem Production na atividade (sem cache: a Ocupação filtra por gente que acabou de receber).
        $users = Production::query()
            ->where('service_id', $uuid)
            ->where('confirmed', false)
            ->when($visible, fn ($q) => $q->whereIn('company_id', $visible))
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $statuses = Production::query()
            ->where('service_id', $uuid)
            ->where('confirmed', false)
            ->when($visible, fn ($q) => $q->whereIn('company_id', $visible))
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

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

    /**
     * Consulta única da lista, do Excel e dos cartões de status: o mesmo recorte nos três (antes eram três cópias, com
     * regras diferentes). `$withStatus = false` ignora o filtro de status, para os cartões contarem todos os status.
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

    /** Página atual (a view e as ações de seleção leem a mesma; uma paginação por requisição). */
    public function paginatedLists(): LengthAwarePaginator
    {
        return $this->listsMemo ??= $this->controlQuery()
            ->with(['Note', 'User', 'Company'])
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->select('productions.*', 'notes.dt_created as note_dt_created')
            ->orderBy('productions.priority', 'DESC')
            ->orderBy('productions.d5', 'DESC')
            ->orderBy('notes.type_note', 'DESC')
            ->orderBy('notes.dt_created', 'asc')
            ->paginate($this->perPage);
    }

    public function getListsProperty()
    {
        return $this->paginatedLists();
    }

    /** Builder do Excel (e da exportação da Parceira): sem paginação, no mesmo recorte da lista. */
    public function getExportsProperty()
    {
        return $this->controlQuery()
            ->with(['Note'])
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->select('productions.*', 'notes.dt_created as note_dt_created')
            ->orderBy('productions.priority', 'DESC')
            ->orderBy('notes.type_note', 'DESC')
            ->orderBy('notes.dt_created', 'asc');
    }

    /** Contagem por status (cartões do painel), no mesmo recorte da lista mas sem o filtro de status. */
    public function getStatusCountsProperty(): array
    {
        return $this->controlQuery(false)
            ->selectRaw('productions.status as status, count(*) as total')
            ->groupBy('productions.status')
            ->pluck('total', 'status')
            ->all();
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->company_s      = '';
        $this->selected       = [];
        $this->user_s         = '';
        $this->type           = '';
        $this->additionalData = [];
        $this->advanceSearch  = '';
        $this->notes          = null;
        $this->gotoPage(1);

        $this->emitSelf('refresh_list');
    }

    public function clean()
    {
        $this->company_s      = '';
        $this->enter_dd       = '';
        $this->user_s         = '';
        $this->type           = '';
        $this->additionalData = [];
        $this->multiSearch    = [];
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->search = '';
            $this->gotoPage(1);

            $this->multiSearch = array_values(array_filter(array_map('trim', preg_split('/[\s,;]+/', $this->advanceSearch)), 'strlen'));
        } else {
            $this->multiSearch = [];
        }

        if (count($this->multiSearch)) {
            $this->selected  = [];
            $this->selectAll = false;
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    /** Nome da view; a Parceira usa a mesma (as duas telas são idênticas). */
    protected function viewName(): string
    {
        return 'livewire.dispatchs.analises-pre.stack';
    }

    public function render()
    {
        $this->listsMemo = null;

        $lists = $this->paginatedLists();
        $this->recomputeSelectAllFor($lists->items());

        $presenter = app(PreAnalysisListRowPresenter::class);
        $legal     = $presenter->legalLinkedNoteIds($lists->pluck('note_id')->all());
        $rows      = $lists->getCollection()->map(fn (Production $p) => $presenter->control($p, isset($legal[$p->note_id])));

        // O modal de atribuição só precisa de empresas e usuários enquanto há notas escolhidas.
        $dispatching = $this->notes && $this->notes->count();

        return view($this->viewName(), [
            'lists'        => $lists,
            'rows'         => $rows,
            'statusCounts' => $this->statusCounts,
            'companies'    => $dispatching ? $this->companiesOfService() : collect(),
            'users'        => $dispatching ? $this->usersOfCompany() : collect(),
        ]);
    }

    private function companiesOfService()
    {
        return Company::whereHas('toUsers', function ($query) {
            $query->whereRelation('ToServices', function ($q) {
                $q->where('service_id', $this->service->uuid)
                    ->where('service', true);
            });
        })
            ->orderBy('name', 'ASC')
            ->get();
    }

    private function usersOfCompany()
    {
        return User::whereRelation('ToServices', function ($q) {
            $q->where('service_id', $this->service->uuid)
                ->where('service', true);
        })
            ->where(function ($q) {
                $q->whereRelation('Company', 'company_id', $this->company_s)
                    ->orWhere('company_id', $this->company_s);
            })
            ->orderBy('name', 'ASC')
            ->get();
    }
}
