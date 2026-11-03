<?php

namespace App\Http\Livewire\Dispatchs\Reverse;

use App\Custom\RuleBuilder;
use App\Exports\Services\analisesExport;
use App\Helpers\TextFormatter;
use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Models\{City, Note, Service};
use App\Services\Design\BlockEvaluator;
use App\Services\Reverse\ReverseListRowPresenter;
use App\Support\SicodeRules;
use App\Traits\WildcardFormmater;
use Illuminate\Support\Facades\Cache;
use Livewire\{Component, WithPagination};

/**
 * Despacho · Fluxo Reverso: lista operacional (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md).
 * A lista da parceira (`PartnerMain`) herda daqui; só muda o perfil e a exportação.
 */
class Main extends Component
{
    use WithPagination;
    use WithNoteListFilters;
    use WildcardFormmater;
    use TextFormatter;

    protected $paginationTheme = 'bootstrap';

    /** `dispatch` (Despacho) ou `partner` (parceira): só muda o texto do cabeçalho. */
    protected string $profile = 'dispatch';

    public $service;

    public $perPage = 100;

    public $search;

    public $last_update;

    public $advanceSearch;

    public $multiSearch = [];

    public $selectall = false;

    public $selected = [];

    public $not_assigned = false;

    public $mmgd = false;

    /** @var array<string, array> Memo de `rowDispatchState()` (zerado a cada render). */
    private array $dispatchStateMemo = [];

    protected $listeners = [
        'refresh_dispatch' => '$refresh',
        'refresh_list'     => '$refresh',
        'getCopy'          => 'copy',
    ];

    protected $queryString = [
        'search'       => ['except' => '', 'as' => 'buscar'],
        'page'         => ['except' => 1, 'as' => 'p'],
        'perPage'      => ['except' => 100, 'as' => 'pp'],
        'not_assigned' => ['except' => false, 'as' => 'livres'],
        'mmgd'         => ['except' => false, 'as' => 'semmmgd'],
        'filters'      => ['except' => [], 'as' => 'f'],
    ];

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('reverse-dispatch:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
    }

    // ---------- Filtros (WithNoteListFilters) ----------

    protected function listFilterDefinitions(): array
    {
        return [
            'material' => ['label' => 'Material'],
            'rubrica'  => ['label' => 'Rubrica'],
            'region'   => ['label' => 'Região'],
            'regional' => ['label' => 'Regional', 'depends' => ['region']],
            'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    protected function listFilterOptions(): array
    {
        return [
            'material' => $this->rememberFilterOptions('material', fn () => Note::query()
                ->whereNotNull('material')->where('material', '!=', '')
                ->distinct()->orderBy('material')->pluck('material')
                ->map(fn ($m) => ['v' => (string) $m, 'l' => (string) $m])->all()),
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => Note::query()
                ->whereNotNull('rubrica')->where('rubrica', '!=', '')
                ->distinct()->orderBy('rubrica')->pluck('rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
        ] + $this->locationFilterOptions();
    }

    /** As opções de Material/Rubrica varrem a tabela de notas inteira: ficam 30 minutos em cache. */
    protected function listFilterCacheTtl(): int
    {
        return 1800;
    }

    /** "Limpar tudo": filtros, busca (simples e em massa) e atalhos. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->filters       = [];
        $this->search        = '';
        $this->multiSearch   = [];
        $this->advanceSearch = '';
        $this->not_assigned  = false;
        $this->mmgd          = false;
        $this->afterListFiltersChanged();
    }

    // ---------- Estado da tela ----------

    public function updatedSearch()
    {
        $this->multiSearch = [];
        $this->gotoPage(1);
    }

    public function updatedPerPage()
    {
        $this->gotoPage(1);
    }

    public function filterMMGD()
    {
        $this->mmgd = !$this->mmgd;
        $this->gotoPage(1);
    }

    public function filterStatus()
    {
        $this->not_assigned = !$this->not_assigned;
        $this->gotoPage(1);
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function needBlock(Note $note): array
    {
        return $this->rowDispatchState($note)['eval'];
    }

    /**
     * Avaliação de bloqueio + pilha da empresa de uma linha, feita UMA vez por requisição
     * (antes a tela, o "selecionar todos" e a verificação da página liam isso 3x por linha).
     *
     * @return array{eval: array, stack: ?\App\Models\Production, canDispatch: bool}
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

    // ---------- Seleção e despacho ----------

    /** Marca/desmarca as linhas despacháveis da página atual, preservando o que já estava marcado em outras páginas. */
    public function updatedSelectall($val)
    {
        $pageIds = collect($this->lists->paginate($this->perPage)->items())
            ->filter(fn (Note $note) => $this->canDispatchNote($note))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $current = array_map('strval', $this->selected);

        $this->selected = $val
            ? array_values(array_unique([...$current, ...$pageIds]))
            : array_values(array_diff($current, $pageIds));
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

    public function export_excel()
    {
        $query = $this->lists->with('Productions.Company');

        if (count($this->selected)) {
            $query->whereIn('id', $this->selected);
        }

        return (new analisesExport($query, $this->service->uuid))->download(date('YmdHis-') . 'exportAnalises.xlsx');
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->selected      = [];
        $this->selectall     = false;
        $this->advanceSearch = '';
        $this->search        = '';
        $this->gotoPage(1);

        $this->emit('refresh_dispatch');
    }

    public function clean()
    {
        $this->advanceSearch = '';
        $this->multiSearch   = [];
        $this->search        = '';
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->multiSearch = array_values($this->formatTextToArray($this->advanceSearch));
            $this->search      = '';
            $this->gotoPage(1);
        }

        if (count($this->multiSearch)) {
            $this->advanceSearch = '';
            $this->closeall();
        }
    }

    // ---------- Consulta ----------

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

    /** Consulta única da lista, do Excel e do "selecionar todos". */
    public function getListsProperty()
    {
        $query  = Note::query()->excludeCanceledFullDone();
        $search = trim((string) $this->search);
        $base   = $this->base;

        if (!$search && !count($this->multiSearch)) {
            SicodeRules::applyContractDispatchMainVisibility(
                $query,
                Auth()->User(),
                $this->service->uuid,
                fn ($statusQuery) => RuleBuilder::applyRules($statusQuery, $this->service->Status)
            );
        } else {
            $query->whereIn('nstats', [2, 21, 4]);

            SicodeRules::applyContractDispatchMainVisibility(
                $query,
                Auth()->User(),
                $this->service->uuid,
                fn ($statusQuery) => null
            );
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

        $query->when($search, function ($q, $s) {
            $search = $this->formatWithWildcard($s);

            return $q->where(function ($query) use ($search) {
                $query->where('note', $search->type, $search->search)
                    ->orWhere('material', $search->type, $search->search)
                    ->orWhere('numPedido', $search->type, $search->search)
                    ->orWhere('group2', $search->type, $search->search)
                    ->orWhere('group4', $search->type, $search->search)
                    ->orWhere('group5', $search->type, $search->search);
            });
        });

        $query->when($this->multiSearch, function ($q, $s) {
            return $q->where(function ($query) use ($s) {
                $query->whereIn('note', $s)
                    ->orWhereIn('material', $s)
                    ->orWhereIn('numPedido', $s)
                    ->orWhereIn('group2', $s)
                    ->orWhereIn('group4', $s)
                    ->orWhereIn('group5', $s);
            });
        });

        if ($this->mmgd) {
            $query->where('material', 'not like', '%MMGD%');
        }

        $query->when($this->filterValues('rubrica'), fn ($q, $v) => $q->whereIn('rubrica', $v))
            ->when($this->filterValues('material'), fn ($q, $v) => $q->whereIn('material', $v))
            ->when($base, fn ($q, $codes) => $q->whereIn('nexp', $codes));

        // Filtro de localidade sem nenhum município correspondente não pode liberar tudo.
        if (($this->filterValues('region') || $this->filterValues('regional') || $this->filterValues('city')) && !$base) {
            $query->whereRaw('0 = 1');
        }

        return $query->with(['Productions.User', 'Productions.Company'])
            ->orderBy('type_note', 'DESC')
            ->orderBy('days_left');
    }

    public function render()
    {
        $this->dispatchStateMemo = [];

        $lists     = $this->lists->paginate($this->perPage);
        $presenter = app(ReverseListRowPresenter::class);
        $rows      = collect($lists->items())->map(fn (Note $note) => $presenter->dispatchRow($note, $this->rowDispatchState($note)));

        $dispatchable = $rows->where('canDispatch', true)->pluck('key')->all();

        $this->selectall = $dispatchable !== [] && empty(array_diff($dispatchable, array_map('strval', $this->selected)));

        return view('livewire.dispatchs.reverse.main', [
            'lists'        => $lists,
            'rows'         => $rows,
            'legalNoteIds' => $presenter->legalLinkedNoteIds($rows->pluck('noteId')->all()),
            'profile'      => $this->profile,
            'update'       => $this->last_update,
        ]);
    }
}
