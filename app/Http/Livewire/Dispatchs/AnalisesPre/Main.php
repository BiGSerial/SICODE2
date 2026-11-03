<?php

namespace App\Http\Livewire\Dispatchs\AnalisesPre;

use App\Custom\RuleBuilder;
use App\Exports\DispatchDesenhoMain;
use App\Http\Livewire\Concerns\WithListFilters;
use App\Models\{Bancoupdate, Company, Note, Notetimeline, Production, Service, User};
use App\Models\City;
use App\Services\Design\BlockEvaluator;
use App\Services\Dispatch\PreAnalysisListRowPresenter;
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Livewire\{Component, WithPagination};

/**
 * Despacho -> Lista para Análises Pré (lista operacional, padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md).
 * A Parceira (`PartnerMain`) herda tudo e só troca a exportação.
 */
class Main extends Component
{
    use WithListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $note;

    public $last_update;

    public $advanceSearch;

    public $multiSearch = [];

    public $selectall = false;

    public $selected = [];

    public $company_l;

    public $company_s;

    public $user_l;

    public $user_s;

    public $type;

    public $additionalData = [];

    public $notes;

    public $enter_dd;

    public $search_user;

    public $not_assigned = false;

    protected $listeners = [
        'refresh_dispatch'  => '$refresh',
        'refresh_list'      => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
        'confirm_dispatch'  => 'confirmed_att',
    ];

    protected $queryString = [
        'search'       => ['except' => '', 'as' => 'buscar'],
        'page'         => ['except' => 1, 'as' => 'p'],
        'perPage'      => ['as' => 'pp'],
        'not_assigned' => ['except' => false, 'as' => 'livres'],
        'filters'      => ['except' => [], 'as' => 'f'],
    ];

    /** @var array<string, array> Memo de `rowDispatchState()` (zerado a cada render). */
    private array $dispatchStateMemo = [];

    private ?LengthAwarePaginator $listsMemo = null;

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('pre-analysis-dispatch:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
    }

    // ---------- Filtros (WithListFilters): Rubrica, Região, Regional e Município, como na tela anterior ----------

    protected function listFilterDefinitions(): array
    {
        return [
            'rubrica'  => ['label' => 'Rubrica'],
            'region'   => ['label' => 'Região'],
            'regional' => ['label' => 'Regional', 'depends' => ['region']],
            'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    protected function listFilterOptions(): array
    {
        return [
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => Note::query()
                ->whereNotNull('rubrica')->where('rubrica', '!=', '')
                ->distinct()->orderBy('rubrica')->pluck('rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
        ] + $this->locationFilterOptions();
    }

    /** Opções de Região / Regional / Município (com os valores-pai para a cascata). */
    protected function locationFilterOptions(): array
    {
        $cities = $this->rememberFilterOptions('cities', fn () => City::query()
            ->whereNotNull('rdMunicipio')
            ->orderBy('municipio')
            ->get(['rdMunicipio', 'municipio', 'regiao', 'baseConstrucao'])
            ->map(fn ($c) => [
                'v' => (string) $c->rdMunicipio,
                'l' => (string) ($c->municipio ?: $c->rdMunicipio),
                'p' => ['region' => (string) $c->regiao, 'regional' => (string) $c->baseConstrucao],
            ])->unique('v')->values()->all());

        return [
            'region' => collect($cities)->pluck('p.region')->filter()->unique()->sort()->values()
                ->map(fn ($r) => ['v' => $r, 'l' => $r])->all(),
            'regional' => collect($cities)->map(fn ($c) => $c['p'])->filter(fn ($p) => $p['regional'] !== '')
                ->unique('regional')->sortBy('regional')->values()
                ->map(fn ($p) => ['v' => $p['regional'], 'l' => $p['regional'], 'p' => ['region' => $p['region']]])->all(),
            'city' => $cities,
        ];
    }

    protected function listFilterCacheScope(): string
    {
        return 'pre-analysis-dispatch';
    }

    /** "Limpar tudo": filtros, busca (simples e em massa) e atalho. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->filters       = [];
        $this->search        = '';
        $this->multiSearch   = [];
        $this->advanceSearch = '';
        $this->not_assigned  = false;
        $this->afterListFiltersChanged();
    }

    public function clearSelection(): void
    {
        $this->selected  = [];
        $this->selectall = false;
    }

    public function updatedSearch()
    {
        $this->multiSearch = [];
        $this->gotoPage(1);
    }

    public function updatedPerPage()
    {
        $this->gotoPage(1);
    }

    public function export_excel()
    {
        $query = $this->lists;

        if (count($this->selected)) {
            $query->whereIn('id', $this->selected);
        }

        return (new DispatchDesenhoMain($query->get(), $this->service->uuid))->download(date('YmdHis-') . 'exportNotesDesenho.xlsx');
    }

    public function updatedCompanyS()
    {

        $this->user_s = '';
    }

    /** Marca/desmarca as linhas da página que podem ser atribuídas. */
    public function setSelectAll()
    {
        $items       = $this->paginatedLists()->items();
        $selectedSet = array_fill_keys(array_map('strval', $this->selected), true);

        foreach ($items as $note) {
            $id = (string) $note->id;

            if ($this->selectall) {
                if ($this->canDispatchNote($note)) {
                    $selectedSet[$id] = true;
                }
            } else {
                unset($selectedSet[$id]);
            }
        }

        $this->selected = array_map(strval(...), array_keys($selectedSet));
    }

    /** "Selecionar todas" fica marcado quando todas as linhas atribuíveis da página estão selecionadas. */
    public function checkAllSelect($items): bool
    {
        $this->recomputeSelectAllFor($items instanceof LengthAwarePaginator ? $items->items() : $items);

        return $this->selectall;
    }

    protected function recomputeSelectAllFor(iterable $items): void
    {
        $eligible = [];

        foreach ($items as $note) {
            if ($this->canDispatchNote($note)) {
                $eligible[] = (string) $note->id;
            }
        }

        $this->selectall = $eligible !== [] && array_diff($eligible, array_map('strval', $this->selected)) === [];
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function get_single_note($note)
    {
        $this->selected = [$note];

        $this->go_att_mass();
    }

    public function go_att_mass()
    {

        $this->clean();

        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para despacho!',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->emitTo('dispatchs.shared.dispatch-modal', 'openForNotes', array_values($this->selected));
    }

    public function confirm_att()
    {
        if ($this->type === '2') {

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
            'title'         => 'Confirmar Despachar',
            'msg'           => "Você está prestes a Despachar {$this->notes->count()} nota(s) para {$para}",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Despache!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_dispatch',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

        ]);
    }

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

    public function confirmed_att()
    {

        $erros = [];

        if ($this->type == '2') {

            foreach ($this->notes as $key => $note) {

                if (!$erro = Production::where('note_id', $note->id)->Where('service_id', $this->service->uuid)->Where('confirmed', false)->first()) {
                    $production = Production::create([
                        'note_id'     => $note->id,
                        'service_id'  => $this->service->uuid,
                        'user_id'     => $this->user_s,
                        'company_id'  => $this->company_s,
                        'dispatch_by' => Auth()->User()->id,
                        'att_by'      => Auth()->User()->id,
                        'dt_note'     => $note->dt_status,
                        'status_note' => $note->nstats,
                        'centroTrab'  => $note->centerjob,
                        'dispatch_at' => date('Y-m-d H:i:s'),
                        'att_at'      => date('Y-m-d H:i:s'),
                        'status'      => 2,
                    ]);

                    $user = Auth()->User()->name;

                    $user_info = $this->dispatchRecipientInfo();

                    if ($production) {
                        Notetimeline::Create([
                            'note_id'       => $production->note_id,
                            'service_id'    => $production->service_id,
                            'user_id'       => Auth()->User()->id,
                            'info'          => "Usuário {$user} {$user_info}",
                            'status'        => 2,
                            'production_id' => $production->id,
                        ]);
                    }
                } else {
                    $erros[] = $erro;
                }
            }

        } else {

            foreach ($this->notes as $key => $note) {

                if (!$erro = Production::where('note_id', $note->id)->Where('service_id', $this->service->uuid)->Where('confirmed', false)->first()) {
                    $production = Production::create([
                        'note_id'     => $note->id,
                        'service_id'  => $this->service->uuid,
                        'company_id'  => $this->company_s,
                        'dispatch_by' => Auth()->User()->id,
                        'dt_note'     => $note->dt_status,
                        'status_note' => $note->nstats,
                        'centroTrab'  => $note->centerjob,
                        'dispatch_at' => date('Y-m-d H:i:s'),
                        'status'      => 1,
                    ]);

                    $user = Auth()->User()->name;

                    $user_info = $this->dispatchRecipientInfo();

                    if ($production) {
                        Notetimeline::Create([
                            'note_id'       => $production->note_id,
                            'service_id'    => $production->service_id,
                            'user_id'       => Auth()->User()->id,
                            'info'          => "Usuário {$user} {$user_info}",
                            'status'        => 1,
                            'production_id' => $production->id,
                        ]);
                    }
                } else {
                    $erros[] = $erro;
                }

            }
        }

        if (count($erros)) {

            $info = '<br>';

            foreach ($erros as $err) {
                $info .= $this->productionAssignmentLabel($err) . '<br>';
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Notas Despachadas com sucesso parcial!',
                'msg'      => "Foram Despachadas com sucesso, porém, algumas ja se enconram em controle: {$info}",
                'timer'    => 2500,
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Notas Despachadas com sucesso!',
                'timer'    => 2500,
            ]);
        }

        $this->closeall();
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
        $this->search         = '';
        $this->gotoPage(1);

        $this->emit('refresh_dispatch');
    }

    public function clean()
    {

        $this->company_s      = '';
        $this->enter_dd       = '';
        $this->user_s         = '';
        $this->type           = '';
        $this->additionalData = [];
        $this->multiSearch    = [];
        $this->advanceSearch  = '';
        $this->search         = '';
    }

    public function buscarMulti()
    {

        if ($this->advanceSearch) {

            $this->gotoPage(1);

            $this->search = '';

            $this->multiSearch = explode("\n", $this->advanceSearch);

            if (!count($this->multiSearch)) {
                $this->multiSearch = explode(' ', $this->advanceSearch);
            }

            if (!count($this->multiSearch)) {
                $this->multiSearch = explode(',', $this->advanceSearch);
            }

            if (!count($this->multiSearch)) {
                $this->multiSearch = explode(';', $this->advanceSearch);
            }

            $this->multiSearch = array_map('trim', $this->multiSearch);
        }

        if (count($this->multiSearch)) {
            $this->closeall();
        }
    }

    public function filterStatus()
    {
        if ($this->not_assigned) {
            $this->not_assigned = false;
        } else {
            $this->not_assigned = true;
        }

    }

    /** Consulta única da lista, do Excel e da exportação da Parceira: o mesmo recorte nas três. */
    public function getListsProperty(): Builder
    {
        $query = Note::query()->excludeCanceledFullDone();

        SicodeRules::applyContractDispatchMainVisibility(
            $query,
            Auth()->User(),
            $this->service->uuid,
            fn ($statusQuery) => RuleBuilder::applyRules($statusQuery, $this->service->Status)
        );

        if (count($this->multiSearch)) {
            $query->whereIn('note', $this->multiSearch);
        }

        if ($this->not_assigned) {
            $query->where(function ($q) {
                $q->doesntHave('Productions')
                    ->orWhereDoesntHave('Productions', function ($subquery) {
                        $subquery->where('service_id', $this->service->uuid)
                            ->where('confirmed', false);
                    });
            });
        }

        $query->when($this->search, function ($q, $s) {
            return $q->where(function ($query) use ($s) {
                $query->where('note', 'like', '%' . $s . '%')
                    ->orWhere('material', 'like', '%' . $s . '%')
                    ->orWhere('numPedido', 'like', '%' . $s . '%')
                    ->orWhere('group4', 'like', '%' . $s . '%')
                    ->orWhere('group5', 'like', '%' . $s . '%');
            });
        });

        if ($rubricas = $this->filterValues('rubrica')) {
            $query->whereIn('rubrica', $rubricas);
        }

        if ($codes = $this->locationCodes()) {
            $query->whereIn('nexp', $codes);
        }

        return $query->orderBy('type_note', 'DESC')
            ->orderBy('dt_created');
    }

    /**
     * Códigos de município (`notes.nexp`) dos filtros Região / Regional / Município. Região casa com `regiao` ou
     * `baseConstrucao`, Regional com `baseConstrucao`, Município com cidade, município ou código (regra da tela anterior).
     */
    private function locationCodes(): ?array
    {
        $regions   = $this->filterValues('region');
        $regionals = $this->filterValues('regional');
        $cities    = $this->filterValues('city');

        if (!$regions && !$regionals && !$cities) {
            return null;
        }

        $mapped = City::query()
            ->when($regions, fn ($q) => $q->where(fn ($w) => $w->whereIn('regiao', $regions)->orWhereIn('baseConstrucao', $regions)))
            ->when($regionals, fn ($q) => $q->whereIn('baseConstrucao', $regionals))
            ->when($cities, fn ($q) => $q->where(fn ($w) => $w->whereIn('cidade', $cities)->orWhereIn('municipio', $cities)->orWhereIn('rdMunicipio', $cities)))
            ->pluck('rdMunicipio')
            ->filter(fn ($v) => filled($v))
            ->map(fn ($v) => trim((string) $v));

        $numeric = collect($cities)->filter(fn ($v) => preg_match('/^\d+$/', $v) === 1);

        return $numeric->merge($mapped)->unique()->values()->all();
    }

    /**
     * Página atual, com as relações que a view usa (sem consulta por linha). Uma paginação só por requisição.
     * Parte de um builder novo: `$this->lists` é cacheado pelo Livewire na requisição e o `paginate()` o alteraria.
     */
    public function paginatedLists(): LengthAwarePaginator
    {
        return $this->listsMemo ??= $this->getListsProperty()
            ->with(['Productions.User:id,name', 'Productions.Company:id,name'])
            ->paginate($this->perPage);
    }

    public function needBlock(Note $note): array
    {
        return $this->rowDispatchState($note)['eval'];
    }

    /**
     * Avaliação de bloqueio + pilha da empresa de uma linha, feita UMA vez por requisição (a tela, o "selecionar todas"
     * e a verificação de cada ação liam isso várias vezes por linha, com consulta em cada uma).
     *
     * @return array{eval: array, stack: ?Production, canDispatch: bool}
     */
    public function rowDispatchState(Note $note): array
    {
        return $this->dispatchStateMemo[$note->id] ??= (function () use ($note) {
            $eval  = app(BlockEvaluator::class)->evaluate($note, $this->service);
            $stack = SicodeRules::openCompanyStackProductionFor($note, Auth()->User(), $this->service->uuid);

            return [
                'eval'        => $eval,
                'stack'       => $stack,
                'canDispatch' => !$eval['block'] || (bool) ($eval['command'] ?? false) || (bool) $stack,
            ];
        })();
    }

    private function canDispatchNote(Note $note): bool
    {
        return $this->rowDispatchState($note)['canDispatch'];
    }

    private function dispatchRecipientInfo(): string
    {
        if (trim((string) $this->user_s)) {
            $userName = User::find($this->user_s)?->name ?? 'Desconhecido';

            return "Atribuiu a NOTA/OV para: {$userName}";
        }

        $companyName = Company::find($this->company_s)?->name ?? 'Desconhecido';

        return "Despachou a NOTA/OV para: {$companyName}";
    }

    private function productionAssignmentLabel(Production $production): string
    {
        $production->loadMissing(['Note', 'User', 'Company']);

        $note = $production->Note?->note ?? $production->note_id;

        if ($production->User) {
            return "{$note} => {$production->User->name}";
        }

        if ($production->Company) {
            return "{$note} => {$production->Company->name} (sem usuário atribuído)";
        }

        return "{$note} => Desconhecido";
    }

    /** Nome da view; a Parceira usa a mesma (as duas telas são idênticas). */
    protected function viewName(): string
    {
        return 'livewire.dispatchs.analises-pre.main';
    }

    public function render()
    {
        $this->dispatchStateMemo = [];
        $this->listsMemo         = null;

        $lists = $this->paginatedLists();
        $this->recomputeSelectAllFor($lists->items());

        $presenter = app(PreAnalysisListRowPresenter::class);
        $legal     = $presenter->legalLinkedNoteIds($lists->pluck('id')->all());
        $rows      = $lists->getCollection()->map(fn (Note $note) => $presenter->dispatch($note, $this->rowDispatchState($note), isset($legal[$note->id])));

        return view($this->viewName(), [
            'lists'  => $lists,
            'rows'   => $rows,
            'update' => Cache::remember('pre-analysis-dispatch:bancoupdate', 60, fn () => Bancoupdate::orderByDesc('created_at')->first()),
        ]);
    }
}
