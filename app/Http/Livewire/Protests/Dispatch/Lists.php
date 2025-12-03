<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Helpers\TextFormatter;
use App\Jobs\Protests\ProtestExportListJob;
use App\Models\MedProtest;
use App\Models\Protest;
use App\Traits\{AppliesQueryFilters, WildcardFormmater};
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Concerns\Exportable;

class Lists extends Component
{
    use WithPagination;
    use Exportable;
    use TextFormatter;
    use WildcardFormmater;
    use AppliesQueryFilters;

    protected $paginationTheme = 'bootstrap';

    public $service;
    public $perPage = 100;
    public $search;
    public $advanceSearch;
    public $multisearch = [];
    public $type = "";

    public $showDetails = false;
    public $selected = null;

    // Variáveis de seleção (Filtros)
    public $selectedProtestType = "";
    public $selectedTipoNota = "";

    // NOTA: As variáveis públicas $tipoNotas e $aProtestTypes foram removidas
    // para evitar o problema de desaparecimento no Livewire 2.
    // Elas agora são carregadas via Computed Properties abaixo.

    public $filtersState = [];

    private $filter_group = 'protests';

    private $filter;

    protected $queryString = [
        'type'    => ['except' => '', 'as' => 'tipo'],
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list'    => '$refresh',
        'filters.updated' => 'onFiltersUpdated',
        'filters.applied' => 'onFiltersUpdated',
    ];

    public function onFiltersUpdated($payload = [])
    {
        $this->filtersState = $payload ?: [];
        $this->resetPage();
    }

    protected function filtersMap(): array
    {
        return [
            'city' => [
                'type'   => 'in',
                'column' => 'cidade',
            ],
            'type' => [
                'type'   => 'equals',
                'column' => 'tipoNota',
            ],
            'desired_between' => [
                'type'   => 'between_dates',
                'column' => 'dtConclusaoDesej',
            ],
        ];
    }

    public function mount()
    {
        // O mount fica vazio ou apenas com inicializações simples.
        // O carregamento de dados agora é feito nas Computed Properties.
    }

    /*
     * Computed Property para Tipos de Notas
     * Substitui a antiga variável pública.
     */
    public function getTypeNotesProperty()
    {
        return Protest::select('tipoNota')
            ->distinct()
            ->orderBy('tipoNota', 'ASC')
            ->get();
    }

    /*
     * Computed Property para Tipos de Protesto
     * Substitui a antiga variável pública.
     */
    public function getProtestTypesProperty()
    {
        return MedProtest::select('protest_type')
            ->distinct()
            ->orderBy('protest_type', 'ASC')
            ->get();
    }

    public function showDetails($id)
    {
        $this->selected = Protest::with(['medProtests' => fn ($q) => $q->orderBy('dtCriacaoMedida', 'DESC')->with('assignments.user')])->find($id);
        $this->showDetails = true;
    }

    public function closeDetails()
    {
        $this->showDetails = false;
        $this->selected = null;
    }

    public function goTo($protestNote)
    {
        
        return redirect()->route('protests.dispatch.view', [
            'protest' => $protestNote,
        ]);
    }

    public function exportToExcel()
    {
        $params = [
            'filtersState' => $this->filtersState,
            'search'       => $this->search,
            'multisearch'  => $this->multisearch,
        ];

        ProtestExportListJob::dispatch($params, auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO INICIADA',
            'text'     => 'A exportação foi iniciada, você receberá uma notificação quando estiver pronta.',
            'timer'    => 5000,
        ]);
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->multisearch = $this->formatTextToArray($this->advanceSearch);
            $this->search = '';
            $this->advanceSearch = '';
            $this->resetPage();
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = Protest::query()
            ->select('protests.*')
            ->selectRaw("
                CASE
                    WHEN protests.tipoNota = 'NA' THEN protests.dtConclusaoDesej
                    ELSE (
                        SELECT mp.dtFimMedidaDesej
                        FROM med_protests mp
                        WHERE mp.protest_id = protests.id
                          AND mp.statusSist = 'MEDA'
                        ORDER BY mp.dtCriacaoMedida DESC
                        LIMIT 1
                    )
                END AS vencimento,
                CASE
                    WHEN protests.tipoNota = 'NA' THEN protests.dtAberturaNota
                    ELSE (
                        SELECT mp2.dtCriacaoMedida
                        FROM med_protests mp2
                        WHERE mp2.protest_id = protests.id
                          AND mp2.statusSist = 'MEDA'
                        ORDER BY mp2.dtCriacaoMedida DESC
                        LIMIT 1
                    )
                END AS abertura
            ")
            ->with([
                'medProtests' => function ($q) {
                    $q->orderByDesc('dtCriacaoMedida')
                        ->with(['ProtestJobs' => fn ($job) => $job->orderByDesc('created_at')]);
                },
                'Notes',
            ]);

        if (!$this->isSearching()) {
            $query->whereHas('medProtests', function ($q) {
                $q->where('statusSist', 'MEDA')
                    ->whereDoesntHave('ProtestJobs');
            });
        }

        $query->when($this->search, function ($query) {
            $this->multisearch   = [];
            $this->advanceSearch = '';
            $this->resetPage();

            $formatted = $this->formatWithWildcard($this->search);

            $query->where(function ($q) use ($formatted) {
                $q->where('nota', $formatted->type, $formatted->search)
                    ->orWhere('txtGrpCodificacao', $formatted->type, $formatted->search)
                    ->orWhereHas('Notes', function ($noteQuery) use ($formatted) {
                        $noteQuery->where('note', $formatted->type, $formatted->search)
                                  ->orWhere('material', $formatted->type, $formatted->search);
                    });
            });
        });

        $query->when($this->multisearch, function ($query) {
            $query->where(function ($sub) {
                $sub->whereIn('nota', $this->multisearch)
                    ->orWhereHas('Notes', function ($noteQuery) {
                        $noteQuery->whereIn('note', $this->multisearch);
                    });
            });
        });

        $query->when($this->selectedTipoNota, function ($query) {
            $query->where('tipoNota', $this->selectedTipoNota);
        });

        $query->when($this->selectedProtestType, function ($query) {
            $query->whereHas('medProtests', function ($q) {
                $q->where('protest_type', $this->selectedProtestType);
            });
        });

        if (isset($this->filter['city'])) {
            $query->whereIn('cidade', $this->filter['city']);
        }

        if (!empty($this->filter['vencimento_from']) && !empty($this->filter['vencimento_to'])) {
            $query->havingRaw('vencimento BETWEEN ? AND ?', [
                $this->filter['vencimento_from'],
                $this->filter['vencimento_to'],
            ]);
        }

        $query->orderByRaw('ISNULL(vencimento), vencimento ASC')
              ->orderBy('id', 'ASC');

        return $query;
    }

    public function render()
    {
        return view('livewire.protests.dispatch.lists', [
            'lists' => $this->lists->paginate($this->perPage),
            // Aqui passamos as Computed Properties. O Livewire entende o snake_case.
            'protest_Types' =>  $this->ProtestTypes,
            'tipoNotas' => $this->TypeNotes,
        ]);
    }

    protected function isSearching(): bool
    {
        return filled($this->search) || !empty($this->multisearch);
    }
}
