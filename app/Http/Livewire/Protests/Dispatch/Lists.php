<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Enum\ProtestJobPriority;
use App\Enum\ProtestJobStatus;
use App\Enum\ProtestType;
use App\Helpers\TextFormatter;
use App\Jobs\Protests\ProtestExportListJob;
use App\Models\MedProtest;
use App\Models\Protest;
use App\Models\ProtestJob;
use App\Traits\{AppliesQueryFilters, WildcardFormmater};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
    public ?string $statusCardFilter = null;

    public $showDetails = false;
    public $selected = null;

    // Variáveis de seleção (Filtros)
    public $selectedProtestType = "";
    public $selectedTipoNota = "";

    // NOTA: As variáveis públicas $tipoNotas e $aProtestTypes foram removidas
    // para evitar o problema de desaparecimento no Livewire 2.
    // Elas agora são carregadas via Computed Properties abaixo.

    public $filtersState = [];

    public bool $showOnlyBtzero = false;
    public bool $hideBtzero = true;

    public ?int $autoDemandMedId = null;

    private $filter_group = 'protests';

    private $filter;

    protected $queryString = [
        'type'    => ['except' => '', 'as' => 'tipo'],
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refreshComponent'      => '$refresh',
        'refresh_list'    => '$refresh',
        'filters.updated' => 'onFiltersUpdated',
        'filters.applied' => 'onFiltersUpdated',
        'createAutoDemandJob'   => 'createAutoDemandJob',
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

    public function setStatusCardFilter(?string $filter = null): void
    {
        if ($this->statusCardFilter === $filter) {
            $this->statusCardFilter = null;
        } else {
            $this->statusCardFilter = $filter;
        }

        $this->resetPage();
    }

    public function getDueTodayCountProperty(): int
    {
        $today = Carbon::today();

        $query = MedProtest::query()
            ->where('statusSist', 'MEDA')
            ->whereDoesntHave('ProtestJobs');

        $this->applyBtzeroVisibilityFilter($query);
        $this->applyMedDeadlineCondition($query, $today, '=');

        return $query->count();
    }

    public function getOverdueCountProperty(): int
    {
        $today = Carbon::today();

        $query = MedProtest::query()
            ->where('statusSist', 'MEDA')
            ->whereDoesntHave('ProtestJobs');

        $this->applyBtzeroVisibilityFilter($query);
        $this->applyMedDeadlineCondition($query, $today, '<');

        return $query->count();
    }

    protected function applyMedDeadlineCondition(Builder $query, Carbon $date, string $operator = '='): void
    {
        $query->where(function ($w) use ($date, $operator) {
            $w->where(function ($branch) use ($date, $operator) {
                $branch->whereHas('protest', function ($p) use ($date, $operator) {
                    $p->where('tipoNota', 'NA')
                        ->whereNotNull('dtConclusaoDesej')
                        ->whereDate('dtConclusaoDesej', $operator, $date);
                });
            })->orWhere(function ($branch) use ($date, $operator) {
                $branch->whereNotNull('dtFimMedidaDesej')
                    ->whereDate('dtFimMedidaDesej', $operator, $date)
                    ->whereHas('protest', function ($p) {
                        $p->where(function ($type) {
                            $type->where('tipoNota', '!=', 'NA')
                                ->orWhereNull('tipoNota');
                        });
                    });
            });
        });
    }

    protected function applyBtzeroVisibilityFilter(Builder $query, bool $includeNullWhenHiding = true): void
    {
        if ($this->showOnlyBtzero) {
            $query->where('protest_type', ProtestType::BTZERO->value);
            return;
        }

        if ($this->hideBtzero) {
            $query->where(function (Builder $sub) use ($includeNullWhenHiding) {
                $sub->where('protest_type', '!=', ProtestType::BTZERO->value);

                if ($includeNullWhenHiding) {
                    $sub->orWhereNull('protest_type');
                }
            });
        }
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
        $query = MedProtest::select('protest_type')
            ->distinct()
            ->where('statusSist', 'MEDA')
            ->orderBy('protest_type', 'ASC');

        $this->applyBtzeroVisibilityFilter($query, includeNullWhenHiding: false);

        return $query->get();
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

    public function confirmAutoDemand(int $medProtestId): void
    {
        $med = MedProtest::with('Protest:id,nota')->find($medProtestId);

        if (!$med) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'error',
                'menssage' => 'Medida não encontrada para criar auto demanda.',
            ]);
            return;
        }

        $this->autoDemandMedId = $med->id;

        $nota = $med->Protest?->nota ?? 'Desconhecido';


        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Criar auto demanda para <strong>#' . ($nota) . '</strong>?',
            'msg'           => 'Deseja gerar uma atividade automática para a medida <strong>#' . ($med->med_id ?? $med->id) . '</strong> ?',
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, criar',
            'btnCanceltxt'  => 'Cancelar',
            'action'        => 'createAutoDemandJob',
            'cancel_titulo' => 'Cancelado',
            'cancel_msg'    => 'Nenhuma atividade foi criada.',
        ]);
    }

    public function createAutoDemandJob(): void
    {
        if (!$this->autoDemandMedId) {
            return;
        }

        $med = MedProtest::with('protest')->find($this->autoDemandMedId);

        if (!$med) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'error',
                'menssage' => 'Não foi possível localizar a medida selecionada.',
            ]);
            $this->resetAutoDemandTarget();
            return;
        }

        $hasOpenJob = $med->ProtestJobs()
            ->open()
            ->exists();

        if ($hasOpenJob) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'warning',
                'menssage' => 'Já existe uma atividade aberta para esta medida.',
            ]);
            $this->resetAutoDemandTarget();
            return;
        }

        $userId = auth()->user()->id;

        ProtestJob::create([
            'protest_id'     => $med->protest_id,
            'med_protest_id' => $med->id,
            'created_by'     => $userId,
            'owner_id'       => $userId,
            'status'         => ProtestJobStatus::OPENED->value,
            'priority'       => ProtestJobPriority::NORMAL->value,
            'is_advance'     => false,
            'need_evidence'  => false,
            'notes'          => 'Auto demanda gerada a partir da fila do despachante.',
            'sent_at'        => now(),
            'sla_due_at'    => now()->addDays(1),
            'auto'           => true,
        ]);

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Atividade automática criada e atribuída para você.',
        ]);

        $this->resetAutoDemandTarget();
        $this->emitSelf('refreshComponent');
    }

    protected function resetAutoDemandTarget(): void
    {
        $this->autoDemandMedId = null;
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

        $query->whereHas('medProtests', function ($q) {
            $q->where('statusSist', 'MEDA')
                ->whereDoesntHave('ProtestJobs');
        });

        if ($this->showOnlyBtzero) {
            $query->whereHas('medProtests', function ($q) {
                $q->where('statusSist', 'MEDA')
                    ->where('protest_type', ProtestType::BTZERO->value);
            });
        } elseif ($this->hideBtzero) {
            $query->whereDoesntHave('medProtests', function ($q) {
                $q->where('statusSist', 'MEDA')
                    ->where('protest_type', ProtestType::BTZERO->value);
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
            $selectedType = $this->selectedProtestType;

            $query->whereHas('medProtests', function ($q) use ($selectedType) {
                $q->where('statusSist', 'MEDA')
                    ->where('protest_type', $selectedType);
            });
        });

        if (isset($this->filter['city'])) {
            $query->whereIn('cidade', $this->filter['city']);
        }

        if ($this->statusCardFilter) {
            $today = Carbon::today();

            if ($this->statusCardFilter === 'due_today') {
                $query->whereHas('medProtests', function ($med) use ($today) {
                    $med->where('statusSist', 'MEDA')
                        ->whereDoesntHave('ProtestJobs');
                    $this->applyMedDeadlineCondition($med, $today, '=');
                });
            } elseif ($this->statusCardFilter === 'overdue') {
                $query->whereHas('medProtests', function ($med) use ($today) {
                    $med->where('statusSist', 'MEDA')
                        ->whereDoesntHave('ProtestJobs');
                    $this->applyMedDeadlineCondition($med, $today, '<');
                });
            }
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
            'dueTodayCount' => $this->dueTodayCount,
            'overdueCount' => $this->overdueCount,
        ]);
    }

    protected function isSearching(): bool
    {
        return filled($this->search) || !empty($this->multisearch);
    }
}
