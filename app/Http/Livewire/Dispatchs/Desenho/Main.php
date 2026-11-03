<?php

namespace App\Http\Livewire\Dispatchs\Desenho;

use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Http\Livewire\Services\Concerns\BuildsLegalNoteTags;
use App\Jobs\Dispatchs\ExportDispatchDrawingMainJob;
use App\Models\{Bancoupdate, Note, Service};
use App\Services\Design\BlockEvaluator;
use App\Services\Dispatch\PartnerStackQuery;
use App\Services\Dispatchs\{DesignDispatchMainQueryService, DesignListRowPresenter};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use BuildsLegalNoteTags;
    use WithNoteListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $last_update;

    public $advanceSearch;

    public $multiSearch = [];

    public bool $bulkSearchAnyStatus = false;

    public $selectall = false;

    public $selected = [];

    public $note_type = '';

    public $not_assigned = false;

    // TODO: 27 Dias Status Temporário - Remover no Futuro
    public $only_27 = false;

    /** @var array<int, array> Memo de `rowDispatchState()` (zerado a cada render). */
    private array $dispatchStateMemo = [];

    /** @var array<int, \App\Models\Production>|null Pilha aberta da empresa por nota, lida numa consulta só por página. */
    private ?array $stackMemo = null;

    private ?LengthAwarePaginator $pageMemo = null;

    protected $listeners = [
        'refresh_dispatch' => '$refresh',
        'refresh_list'     => '$refresh',
        'getCopy'          => 'copy',
    ];

    protected $queryString = [
        'search'       => ['except' => '', 'as' => 'busca'],
        'note_type'    => ['except' => '', 'as' => 'tipo'],
        'page'         => ['except' => 1, 'as' => 'p'],
        'perPage'      => ['as' => 'pp'],
        'not_assigned' => ['except' => false, 'as' => 'livres'],
        'only_27'      => ['except' => false, 'as' => 'd27'],
        'multiSearch',
        'filters' => ['except' => [], 'as' => 'f'],
    ];

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('desenho-dispatch:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
    }

    // ---------- Filtros (WithNoteListFilters) ----------

    protected function listFilterDefinitions(): array
    {
        return [
            'group1'   => ['label' => 'Grupo1'],
            'group2'   => ['label' => 'Grupo2'],
            'group5'   => ['label' => 'Grupo5'],
            'rubrica'  => ['label' => 'Rubrica'],
            'region'   => ['label' => 'Região'],
            'regional' => ['label' => 'Regional', 'depends' => ['region']],
            'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    protected function listFilterOptions(): array
    {
        $distinct = fn (string $column) => $this->rememberFilterOptions($column, fn () => Note::query()
            ->whereNotNull($column)->where($column, '!=', '')
            ->distinct()->orderBy($column)->pluck($column)
            ->map(fn ($value) => ['v' => (string) $value, 'l' => (string) $value])->all());

        return [
            'group1'  => $distinct('group1'),
            'group2'  => $distinct('group2'),
            'group5'  => $distinct('group5'),
            'rubrica' => $distinct('rubrica'),
        ] + $this->locationFilterOptions();
    }

    /** "Limpar tudo": filtros, busca (simples e em massa), tipo e atalhos. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->filters             = [];
        $this->search              = '';
        $this->multiSearch         = [];
        $this->advanceSearch       = '';
        $this->bulkSearchAnyStatus = false;
        $this->not_assigned        = false;
        $this->only_27             = false;
        $this->note_type           = '';
        $this->afterListFiltersChanged();
    }

    // ---------- Atalhos e busca ----------

    public function updatedSearch()
    {
        $this->multiSearch = [];
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedNoteType()
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function filterStatus()
    {
        $this->not_assigned = !$this->not_assigned;
        $this->clearSelection();
        $this->resetPage();
    }

    public function toggle27()
    {
        $this->only_27 = !$this->only_27;
        $this->clearSelection();
        $this->resetPage();
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->resetPage();
            $this->search = '';

            $this->multiSearch = collect(preg_split('/[\s,;\n\r\t]+/', (string) $this->advanceSearch))
                ->map(fn ($term) => trim((string) $term))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (count($this->multiSearch)) {
            $this->selected  = [];
            $this->selectall = false;
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    public function clean()
    {
        $this->multiSearch         = [];
        $this->bulkSearchAnyStatus = false;
        $this->advanceSearch       = '';
        $this->search              = '';
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->selected      = [];
        $this->advanceSearch = '';
        $this->search        = '';
        $this->resetPage();

        $this->emit('refresh_dispatch');
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    // ---------- Ações ----------

    public function export_excel()
    {
        ExportDispatchDrawingMainJob::dispatch(
            array_merge($this->listQueryParams(), [
                'service_uuid' => $this->service->uuid,
                'selected'     => array_values($this->selected),
            ]),
            Auth()->User()->id
        );

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Exportação enviada para a fila.',
            'msg'      => 'Você será notificado quando o arquivo estiver pronto.',
            'timer'    => 3500,
        ]);
    }

    public function check_mmgd(Note $note)
    {
        $note->mmgd = !$note->mmgd;
        $note->save();
    }

    public function check_is45(Note $note)
    {
        $note->is45 = !$note->is45;
        $note->save();
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

    // ---------- Seleção ----------

    /** Marca/desmarca as linhas elegíveis (que podem ser despachadas) da página atual. */
    public function setSelectAll()
    {
        $selectedSet = array_fill_keys(array_map('strval', $this->selected), true);

        foreach ($this->listPage()->items() as $note) {
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

    /** Marca o "selecionar todos" quando todas as linhas elegíveis da página estão marcadas. */
    protected function recomputeSelectAllFor(array $items): void
    {
        $eligible = [];

        foreach ($items as $note) {
            if ($this->canDispatchNote($note)) {
                $eligible[] = (string) $note->id;
            }
        }

        $this->selectall = $eligible !== [] && array_diff($eligible, array_map('strval', $this->selected)) === [];
    }

    // ---------- Consulta ----------

    /** Builder da lista (também lido pelo Excel da parceira): o paginador da tela vem de `listPage()`. */
    public function getListsProperty()
    {
        return app(DesignDispatchMainQueryService::class)
            ->build($this->service, Auth()->User(), $this->listQueryParams());
    }

    private function listQueryParams(): array
    {
        return [
            'search'              => $this->search,
            'multiSearch'         => $this->multiSearch,
            'note_type'           => $this->note_type,
            'not_assigned'        => $this->not_assigned,
            'only_27'             => $this->only_27,
            'bulkSearchAnyStatus' => $this->bulkSearchAnyStatus,
            'filters'             => $this->filters,
        ];
    }

    /** Página atual, consultada uma vez por requisição. */
    private function listPage(): LengthAwarePaginator
    {
        if ($this->pageMemo === null) {
            $this->pageMemo = $this->lists->paginate($this->perPage);
            $this->loadStackMemo($this->pageMemo->pluck('id')->all());
        }

        return $this->pageMemo;
    }

    // ---------- Bloqueio por linha ----------

    public function needBlock(Note $note): array
    {
        return $this->rowDispatchState($note)['eval'];
    }

    /**
     * Avaliação de bloqueio + pilha da empresa de uma linha, feita UMA vez por requisição
     * (a tela, o "selecionar todos" e a verificação do mestre liam isso 3x por linha).
     *
     * @return array{eval: array, stack: ?\App\Models\Production, canDispatch: bool}
     */
    public function rowDispatchState(Note $note): array
    {
        return $this->dispatchStateMemo[$note->id] ??= (function () use ($note) {
            $eval  = app(BlockEvaluator::class)->evaluate($note, $this->service);
            $stack = $this->stackMemo[$note->id] ?? null;

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

    /**
     * Atividade aberta na pilha da empresa (parceira) de cada nota da página, numa consulta só.
     * Mesma regra de `SicodeRules::openCompanyStackProductionFor` (a mais recente por nota).
     */
    private function loadStackMemo(array $noteIds): void
    {
        $this->stackMemo = [];
        $user            = Auth()->User();

        if (!$noteIds || !$user->usesContractDispatch()) {
            return;
        }

        $productions = app(PartnerStackQuery::class)->productions($this->service, $user)
            ->whereIn('note_id', $noteIds)
            ->with(['User', 'Company'])
            ->orderByDesc('id')
            ->get();

        foreach ($productions as $production) {
            $this->stackMemo[$production->note_id] ??= $production;
        }
    }

    protected function viewName(): string
    {
        return 'livewire.dispatchs.desenho.main';
    }

    public function render()
    {
        $this->dispatchStateMemo = [];
        $this->pageMemo          = null;
        $this->stackMemo         = null;

        $lists = $this->listPage();
        $this->recomputeSelectAllFor($lists->items());

        $presenter = app(DesignListRowPresenter::class);
        $rows      = $lists->getCollection()->map(fn (Note $note) => $presenter->main($note, $this->rowDispatchState($note)));

        return view($this->viewName(), [
            'lists'             => $lists,
            'rows'              => $rows,
            'legalTagsByNoteId' => $this->buildLegalTagsByNoteIds($lists->pluck('id')->all()),
            'update'            => Cache::remember('desenho-dispatch:bancoupdate', 60, fn () => Bancoupdate::orderByDesc('created_at')->first()),
        ]);
    }
}
