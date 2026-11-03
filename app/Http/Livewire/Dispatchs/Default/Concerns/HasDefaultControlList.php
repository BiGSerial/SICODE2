<?php

namespace App\Http\Livewire\Dispatchs\Default\Concerns;

use App\Custom\Notestatus;
use App\Helpers\TextFormatter;
use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Models\{City, Company, Note, Notetimeline, Production, Service, User};
use App\Services\Dispatchs\DefaultListRowPresenter;
use App\Services\Production\ProductionActions;
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\{Cache, DB};

/**
 * Lista de controle da atividade padrão (`Default\Stack` e `Default\PartnerStack`): acompanha o que já foi despachado.
 * As duas telas só diferem no Excel (`export_excel`) e na variante `$partner`.
 * Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10 (uma consulta só, sem subcomponente por linha).
 */
trait HasDefaultControlList
{
    use TextFormatter;
    use WithNoteListFilters;

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

    public $production;

    public $productions;

    /** Production alvo de uma confirmação pendente (Re-atribuir / Remover despacho). */
    public ?int $pendingProductionId = null;

    /** Método (e não propriedade): o trait não redeclara `$queryString` do Livewire\Component. */
    protected function queryString(): array
    {
        return [
            'search'    => ['except' => '', 'as' => 'buscar'],
            'page'      => ['except' => 1, 'as' => 'p'],
            'perPage'   => ['as' => 'pp'],
            'note_type' => ['except' => '', 'as' => 'tipo'],
            'filters'   => ['except' => [], 'as' => 'f'],
        ];
    }

    /** Método (e não propriedade): o trait não redeclara `$listeners` do Livewire\Component. */
    protected function getListeners()
    {
        return array_merge(parent::getListeners(), [
            'refresh_list'       => '$refresh',
            'confirm_remove_att' => 'remove_att',
            'confirm_dispatch'   => 'confirmed_att',
            'getCopy'            => 'copy',
            'filterUser'         => 'filterUser',
            'confirm_reatt'      => 'confirmReattribute',
            'confirm_delete'     => 'confirmRemove',
        ]);
    }

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('default-dispatch:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
    }

    /** Clique numa pessoa da Ocupação (menu lateral): filtra o controle por ela e reflete no filtro "Usuário". */
    public function filterUser($user_id)
    {
        $this->filters['user'] = [(string) $user_id];
        $this->afterListFiltersChanged();
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

        return static::class . $this->service->uuid . ($user->usesContractDispatch() ? md5(json_encode(SicodeRules::visibleCompanyIdsFor($user))) : 'all');
    }

    protected function listFilterOptions(): array
    {
        $user    = Auth()->User();
        $visible = $user->usesContractDispatch() ? SicodeRules::visibleCompanyIdsFor($user) : null;
        $uuid    = $this->service->uuid;
        $inScope = fn () => Production::query()
            ->where('productions.service_id', $uuid)
            ->where('productions.confirmed', false)
            ->when($visible, fn ($q) => $q->whereIn('productions.company_id', $visible));

        // Quem tem Production na atividade (sem cache: a Ocupação filtra por gente que acabou de receber).
        $users    = $inScope()->whereNotNull('productions.user_id')->distinct()->pluck('productions.user_id');
        $statuses = $inScope()->distinct()->orderBy('productions.status')->pluck('productions.status');

        return [
            'company' => $this->rememberFilterOptions('company', fn () => Company::query()
                ->whereIn('id', $inScope()->whereNotNull('productions.company_id')->distinct()->pluck('productions.company_id'))
                ->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['v' => (string) $c->id, 'l' => (string) $c->name])->all()),
            'user' => User::withTrashed()->whereIn('id', $users)->orderBy('name')->get(['id', 'name', 'deleted_at'])
                ->map(fn ($u) => ['v' => (string) $u->id, 'l' => $u->name . ($u->deleted_at ? ' (inativo)' : '')])->all(),
            'status'  => $statuses->map(fn ($s) => ['v' => (string) $s, 'l' => Notestatus::status($s)->status])->values()->all(),
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => DB::table('productions')
                ->join('notes', 'notes.id', '=', 'productions.note_id')
                ->where('productions.service_id', $uuid)->where('productions.confirmed', false)
                ->whereNotNull('notes.rubrica')->where('notes.rubrica', '!=', '')
                ->distinct()->orderBy('notes.rubrica')->pluck('notes.rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
        ] + $this->locationFilterOptions();
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

    public function updatedSearch(): void
    {
        $this->multiSearch = [];
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedNoteType(): void
    {
        $this->selected  = [];
        $this->selectAll = false;
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

    // ---------- Consulta ----------

    /**
     * Consulta única da lista, do Excel e dos cartões de status: o mesmo recorte nos três.
     * `$withStatus = false` ignora o filtro de status, para os cartões contarem todos os status.
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
            ->when($user->usesContractDispatch(), fn ($q) => $q->whereIn('productions.company_id', SicodeRules::visibleCompanyIdsFor($user)))
            ->when($company, fn ($q) => $q->whereIn('productions.company_id', $company))
            ->when($users, fn ($q) => $q->whereIn('productions.user_id', $users))
            ->when($withStatus && $status, fn ($q) => $q->whereIn('productions.status', $status))
            ->when($rubrica, fn ($q) => $q->whereHas('Note', fn ($n) => $n->whereIn('rubrica', $rubrica)))
            ->when($base, fn ($q) => $q->whereHas('Note', fn ($n) => $n->where(fn ($w) => $w->whereIn('nexp', $base)->orWhereNull('nexp')->orWhere('nexp', ''))))
            ->when($multi, fn ($q) => $q->whereHas('Note', fn ($n) => $n->whereIn('note', $multi)))
            ->when(!$multi && $term, fn ($q) => $q->whereHas('Note', fn ($n) => $n->where(function ($w) use ($term) {
                $like = '%' . $term . '%';
                $w->where('note', 'like', $like)->orWhere('material', 'like', $like)->orWhere('numPedido', 'like', $like);
            })))
            ->when($this->note_type, fn ($q) => $q->whereRelation('Note', 'type_note', $this->note_type));
    }

    public function getListsProperty()
    {
        return $this->controlQuery()
            ->with(['Note', 'User', 'Company:id,name'])
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->select('productions.*', 'notes.dt_created as note_dt_created')
            ->orderBy('productions.priority', 'DESC')
            ->orderBy('notes.type_note', 'DESC')
            ->orderBy('notes.days_left', 'ASC')
            ->paginate($this->perPage);
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

    // ---------- Seleção ----------

    public function setSelectAll()
    {
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

    /** O "selecionar todos" fica marcado quando todas as linhas da página estão selecionadas. */
    public function checkAllSelect($items): bool
    {
        $selectedSet = array_fill_keys(array_map('intval', $this->selected), true);
        $count       = 0;

        foreach ($items as $production) {
            $count++;

            if (!isset($selectedSet[(int) $production->id])) {
                return $this->selectAll = false;
            }
        }

        return $this->selectAll = $count > 0;
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    // ---------- Busca em massa ----------

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->search      = '';
            $this->multiSearch = array_values(array_map('trim', $this->formatTextToArray($this->advanceSearch)));
        } else {
            $this->multiSearch = [];
        }

        if (count($this->multiSearch)) {
            $this->resetPage();
            $this->selected  = [];
            $this->selectAll = false;
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    // ---------- Ações de linha (sem subcomponente por linha) ----------

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

    // ---------- Atribuição (modal da tela) ----------

    public function get_single_note($prod)
    {
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

        foreach ($this->notes as $note) {
            $production = $this->productions->where('note_id', $note->id)->first();

            if (!$production || !$production->update([
                'user_id'    => $this->user_s,
                'company_id' => $this->company_s,
                'att_by'     => Auth()->User()->id,
                'att_at'     => date('Y-m-d H:i:s'),
                'status'     => $this->user_s ? 2 : 1,
                'completed'  => false,
                'block'      => false,
            ])) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'Erro ao atribuir as notas!',
                    'timer'    => 2500,
                ]);

                return;
            }

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
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Notas Despachadas com sucesso!',
            'timer'    => 2500,
        ]);

        $this->closeall();
    }

    public function to_remove_add($id)
    {
        $this->production = $this->productionInControl((int) $id);

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

    public function remove_att()
    {
        abort_unless(!auth()->user()?->usesContractDispatch(), 403);

        if (!$this->production) {
            return;
        }

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

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->company_s      = '';
        $this->selected       = [];
        $this->selectAll      = false;
        $this->user_s         = '';
        $this->type           = '';
        $this->additionalData = [];
        $this->advanceSearch  = '';
        $this->resetPage();

        $this->emitSelf('refresh_list');
    }

    public function clean()
    {
        $this->company_s      = '';
        $this->enter_dd       = '';
        $this->user_s         = '';
        $this->type           = '';
        $this->additionalData = [];
    }

    // ---------- Render ----------

    /** Empresas e usuários do modal de atribuição (só buscados com o modal montado). */
    private function assignModalOptions(): array
    {
        if (!$this->notes || !$this->notes->count()) {
            return ['companies' => collect(), 'users' => collect()];
        }

        $user      = Auth()->User();
        $companies = $user->usesContractDispatch()
            ? Company::whereIn('id', SicodeRules::visibleCompanyIdsFor($user))->orderBy('name')->get(['id', 'name'])
            : Company::orderBy('name')->get(['id', 'name']);

        return [
            'companies' => $companies,
            'users'     => $this->company_s ? User::where('company_id', $this->company_s)->orderBy('name')->get(['id', 'name']) : collect(),
        ];
    }

    protected function renderControlList(string $view, bool $partner)
    {
        $lists     = $this->lists;
        $presenter = app(DefaultListRowPresenter::class);
        $actions   = app(ProductionActions::class);

        // Quais ações cada linha oferece, decididas uma vez (a view só desenha).
        $rows = $lists->getCollection()->map(fn (Production $production) => $presenter->presentControl($production) + [
            'canReattribute' => $actions->canReattribute($production),
            'canRemove'      => $actions->canRemove($production),
        ]);

        $this->checkAllSelect($lists->items());

        return view($view, [
            'lists'        => $lists,
            'rows'         => $rows,
            'legalNoteIds' => $this->legalLinkedNoteIds($lists->pluck('note_id')->all()),
            'statusCounts' => $this->statusCounts,
            'partner'      => $partner,
        ] + $this->assignModalOptions());
    }
}
