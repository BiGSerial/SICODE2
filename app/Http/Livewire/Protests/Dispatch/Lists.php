<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Enum\ProtestJobPriority;
use App\Enum\ProtestJobStatus;
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
    public string $sortBy = 'vencimento';
    public string $sortDirection = 'asc';
    public string $histogramSource = 'desired';
    public ?int $histogramYear = null;
    public ?int $histogramMonth = null;
    public ?string $cityFilter = null;
    public ?string $selectedCodf = null;

    public $showDetails = false;
    public $selected = null;

    // Variáveis de seleção (Filtros)
    public $selectedProtestType = "";
    public $selectedTipoNota = "";
    public array $cityOptions = [];
    public array $codfOptions = [];

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
        'selectedProtestType' => ['except' => '', 'as' => 'pt'],
        'selectedTipoNota' => ['except' => '', 'as' => 'tn'],
        'cityFilter' => ['except' => null, 'as' => 'city'],
        'selectedCodf' => ['except' => null, 'as' => 'codf'],
        'histogramSource' => ['except' => 'desired', 'as' => 'hsrc'],
        'histogramYear' => ['except' => null, 'as' => 'hyear'],
        'histogramMonth' => ['except' => null, 'as' => 'hmon'],
        'sortBy' => ['except' => 'vencimento', 'as' => 'sort'],
        'sortDirection' => ['except' => 'asc', 'as' => 'dir'],
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

    public function mount($showOnlyBtzero = null, $hideBtzero = null)
    {
        if (!is_null($showOnlyBtzero)) {
            $this->showOnlyBtzero = (bool) $showOnlyBtzero;
        }

        if (!is_null($hideBtzero)) {
            $this->hideBtzero = (bool) $hideBtzero;
        }

        if ($this->showOnlyBtzero) {
            $this->hideBtzero = false;
        }

        $this->histogramYear = $this->histogramYear ?: (int) now()->year;
        $this->loadCityOptions();
        $this->loadCodfOptions();
    }

    protected function loadCityOptions(): void
    {
        $this->cityOptions = Protest::query()
            ->select('cidade')
            ->whereNotNull('cidade')
            ->distinct()
            ->orderBy('cidade')
            ->pluck('cidade')
            ->filter()
            ->values()
            ->toArray();
    }

    protected function loadCodfOptions(): void
    {
        $query = Protest::query()
            ->select('codecodf')
            ->whereNotNull('codecodf')
            ->where('codecodf', '!=', '')
            ->whereHas('medProtests', function ($q) {
                $q->where('statusSist', 'MEDA');
                $this->applyNoValidJobsCondition($q);

                if ($this->showOnlyBtzero) {
                    $q->identifiedAsBtzero();
                } elseif ($this->hideBtzero) {
                    $q->notIdentifiedAsBtzero();
                }
            })
            ->distinct()
            ->orderBy('codecodf');

        $this->codfOptions = $query
            ->pluck('codecodf')
            ->filter()
            ->values()
            ->toArray();
    }

    public function updatedSelectedTipoNota($value): void
    {
        $this->selectedTipoNota = $value ?: "";
        $this->resetPage();
    }

    public function updatedSelectedProtestType($value): void
    {
        $this->selectedProtestType = $value ?: "";
        $this->resetPage();
    }

    public function updatedCityFilter($value): void
    {
        $this->cityFilter = $value ?: null;
        $this->resetPage();
    }

    public function updatedSelectedCodf($value): void
    {
        $this->selectedCodf = $value ?: null;
        $this->resetPage();
    }

    public function updatedHistogramSource($value): void
    {
        if (!in_array($value, ['desired', 'sla'], true)) {
            $this->histogramSource = 'desired';
        }
        $this->histogramMonth = null;
        $this->resetPage();
    }

    public function updatedHistogramYear(): void
    {
        $this->histogramMonth = null;
        $this->resetPage();
    }

    public function sortByColumn(string $column): void
    {
        $allowed = [
            'med_id',
            'nota',
            'tipo_nota',
            'cod',
            'codf',
            'tipo_reclamacao',
            'tx_cod_medida',
            'causa_raiz',
            'origem',
            'municipio',
            'abertura_nota',
            'abertura_medida',
            'vencimento',
        ];

        if (!in_array($column, $allowed, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function setHistogramBucket(?int $month = null): void
    {
        if (!$month || $month < 1 || $month > 12) {
            return;
        }

        $this->histogramMonth = $this->histogramMonth === $month ? null : $month;
        $this->resetPage();
    }

    public function clearHistogramFilter(): void
    {
        $this->histogramMonth = null;
        $this->resetPage();
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
            ->where('statusSist', 'MEDA');

        $this->applyNoValidJobsCondition($query);

        $this->applyBtzeroVisibilityFilter($query);
        $this->applyMedDeadlineCondition($query, $today, '=');

        return $query->count();
    }

    public function getOverdueCountProperty(): int
    {
        $today = Carbon::today();

        $query = MedProtest::query()
            ->where('statusSist', 'MEDA');

        $this->applyNoValidJobsCondition($query);

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
        unset($includeNullWhenHiding);

        if ($this->showOnlyBtzero) {
            $query->identifiedAsBtzero();
            return;
        }

        if ($this->hideBtzero) {
            $query->notIdentifiedAsBtzero();
        }
    }

    /**
     * Considera "em aberto" quando a medida NÃO possui ProtestJob válido.
     * Válido = qualquer status diferente de "canceled" (inclusive NULL).
     * Assim, medida sem job ou com somente jobs cancelados volta para aberto.
     */
    protected function applyNoValidJobsCondition(Builder $query): void
    {
        $query->whereDoesntHave('ProtestJobs', function (Builder $jobQuery) {
            $jobQuery->where(function (Builder $statusQuery) {
                $statusQuery->whereNull('status')
                    ->orWhere('status', '!=', ProtestJobStatus::CANCELED->value);
            });
        });
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
            'showOnlyBtzero' => $this->showOnlyBtzero,
            'hideBtzero' => $this->hideBtzero,
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

    protected function openListsQuery(bool $ignoreStatusCard = false, bool $ignoreHistogram = false): Builder
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
                    $q->where('statusSist', 'MEDA')
                        ->whereDoesntHave('ProtestJobs', function ($jobQuery) {
                            $jobQuery->where(function ($statusQuery) {
                                $statusQuery->whereNull('status')
                                    ->orWhere('status', '!=', ProtestJobStatus::CANCELED->value);
                            });
                        })
                        ->when($this->showOnlyBtzero, function ($typeQuery) {
                            $typeQuery->identifiedAsBtzero();
                        }, function ($typeQuery) {
                            if ($this->hideBtzero) {
                                $typeQuery->notIdentifiedAsBtzero();
                            }
                        })
                        ->orderByDesc('dtCriacaoMedida')
                        ->with(['ProtestJobs' => fn ($job) => $job->orderByDesc('created_at')]);
                },
                'Notes',
            ]);

        $query->whereHas('medProtests', function ($q) {
            $q->where('statusSist', 'MEDA')
                ->whereDoesntHave('ProtestJobs', function ($jobQuery) {
                    $jobQuery->where(function ($statusQuery) {
                        $statusQuery->whereNull('status')
                            ->orWhere('status', '!=', ProtestJobStatus::CANCELED->value);
                    });
                });

            if ($this->showOnlyBtzero) {
                $q->identifiedAsBtzero();
            } elseif ($this->hideBtzero) {
                $q->notIdentifiedAsBtzero();
            }
        });

        if (!$this->showOnlyBtzero && $this->hideBtzero) {
            $query->whereDoesntHave('medProtests', function ($q) {
                $q->where('statusSist', 'MEDA')
                    ->whereDoesntHave('ProtestJobs', function ($jobQuery) {
                        $jobQuery->where(function ($statusQuery) {
                            $statusQuery->whereNull('status')
                                ->orWhere('status', '!=', ProtestJobStatus::CANCELED->value);
                        });
                    })
                    ->identifiedAsBtzero();
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

        $query->when($this->cityFilter, function ($query) {
            $query->where('cidade', $this->cityFilter);
        });

        $query->when($this->selectedCodf, function ($query) {
            $query->where('codecodf', $this->selectedCodf);
        });

        if (isset($this->filter['city'])) {
            $query->whereIn('cidade', $this->filter['city']);
        }

        if (!$ignoreStatusCard && $this->statusCardFilter) {
            $today = Carbon::today();

            if ($this->statusCardFilter === 'due_today') {
                $query->whereHas('medProtests', function ($med) use ($today) {
                    $med->where('statusSist', 'MEDA')
                        ->whereDoesntHave('ProtestJobs', function ($jobQuery) {
                            $jobQuery->where(function ($statusQuery) {
                                $statusQuery->whereNull('status')
                                    ->orWhere('status', '!=', ProtestJobStatus::CANCELED->value);
                            });
                        });
                    $this->applyMedDeadlineCondition($med, $today, '=');
                });
            } elseif ($this->statusCardFilter === 'overdue') {
                $query->whereHas('medProtests', function ($med) use ($today) {
                    $med->where('statusSist', 'MEDA')
                        ->whereDoesntHave('ProtestJobs', function ($jobQuery) {
                            $jobQuery->where(function ($statusQuery) {
                                $statusQuery->whereNull('status')
                                    ->orWhere('status', '!=', ProtestJobStatus::CANCELED->value);
                            });
                        });
                    $this->applyMedDeadlineCondition($med, $today, '<');
                });
            }
        }

        if (!$ignoreHistogram && $this->histogramMonth && $this->histogramYear) {
            if ($this->histogramSource === 'sla') {
                $query->whereHas('medProtests.ProtestJobs', function ($jobQuery) {
                    $jobQuery->whereNull('finished_at')
                        ->whereYear('sla_due_at', (int) $this->histogramYear)
                        ->whereMonth('sla_due_at', (int) $this->histogramMonth)
                        ->where('confirmed', '!=', true);
                });
            } else {
                $query->where(function ($q) {
                    $q->where(function ($na) {
                        $na->where('tipoNota', 'NA')
                            ->whereYear('dtConclusaoDesej', (int) $this->histogramYear)
                            ->whereMonth('dtConclusaoDesej', (int) $this->histogramMonth);
                    })->orWhere(function ($med) {
                        $med->where(function ($type) {
                            $type->where('tipoNota', '!=', 'NA')->orWhereNull('tipoNota');
                        })->whereHas('medProtests', function ($sub) {
                            $sub->where('statusSist', 'MEDA')
                                ->whereYear('dtFimMedidaDesej', (int) $this->histogramYear)
                                ->whereMonth('dtFimMedidaDesej', (int) $this->histogramMonth);
                        });
                    });
                });
            }
        }

        if (!empty($this->filter['vencimento_from']) && !empty($this->filter['vencimento_to'])) {
            $query->havingRaw('vencimento BETWEEN ? AND ?', [
                $this->filter['vencimento_from'],
                $this->filter['vencimento_to'],
            ]);
        }

        return $query;
    }

    public function getListsProperty()
    {
        $query = $this->openListsQuery();

        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        $sort = $this->sortBy;

        $allowedRaw = ['vencimento', 'abertura'];
        $allowedColumns = ['nota', 'tipoNota', 'codecodf', 'txtGrpCodificacao', 'cidade', 'dtAberturaNota', 'id'];

        $query->reorder();
        if ($sort === 'med_id') {
            $query->orderByRaw("(SELECT mp.med_id FROM med_protests mp WHERE mp.protest_id = protests.id AND mp.statusSist='MEDA' ORDER BY mp.dtCriacaoMedida DESC LIMIT 1) {$direction}");
        } elseif ($sort === 'cod') {
            $query->orderByRaw("(SELECT mp.codMedida FROM med_protests mp WHERE mp.protest_id = protests.id AND mp.statusSist='MEDA' ORDER BY mp.dtCriacaoMedida DESC LIMIT 1) {$direction}");
        } elseif ($sort === 'tx_cod_medida') {
            $query->orderByRaw("(SELECT mp.txtCodMedida FROM med_protests mp WHERE mp.protest_id = protests.id AND mp.statusSist='MEDA' ORDER BY mp.dtCriacaoMedida DESC LIMIT 1) {$direction}");
        } elseif ($sort === 'causa_raiz') {
            $query->orderBy('descCausa', $direction);
        } elseif ($sort === 'origem') {
            $query->orderBy('descricao', $direction);
        } elseif ($sort === 'tipo_nota') {
            $query->orderBy('tipoNota', $direction);
        } elseif ($sort === 'tipo_reclamacao') {
            $query->orderBy('txtGrpCodificacao', $direction);
        } elseif ($sort === 'municipio') {
            $query->orderBy('cidade', $direction);
        } elseif ($sort === 'abertura_nota') {
            $query->orderBy('dtAberturaNota', $direction);
        } elseif ($sort === 'abertura_medida') {
            $query->orderByRaw("(SELECT mp.dtCriacaoMedida FROM med_protests mp WHERE mp.protest_id = protests.id AND mp.statusSist='MEDA' ORDER BY mp.dtCriacaoMedida DESC LIMIT 1) {$direction}");
        } elseif ($sort === 'codf') {
            $query->orderBy('codecodf', $direction);
        } elseif (in_array($sort, $allowedRaw, true)) {
            $query->orderByRaw("ISNULL({$sort}), {$sort} {$direction}");
        } elseif (in_array($sort, $allowedColumns, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderByRaw('ISNULL(vencimento), vencimento ASC');
        }

        $query->orderBy('id', 'ASC');

        return $query->paginate($this->perPage);
    }

    public function getHistogramDataProperty(): array
    {
        $query = MedProtest::query()
            ->with(['protest', 'ProtestJobs'])
            ->where('statusSist', 'MEDA');
        $this->applyNoValidJobsCondition($query);
        $this->applyBtzeroVisibilityFilter($query);
        $meds = $query->get();

        $monthNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $totals = [];
        $overdueByMonth = [];
        $dueSoonByMonth = [];
        $withinByMonth = [];
        $yearsMap = [];
        $now = now();

        foreach ($meds as $med) {
            $bucketDate = null;
            if ($this->histogramSource === 'sla') {
                $job = $med->ProtestJobs
                    ->where('confirmed', '!=', true)
                    ->first(fn ($j) => is_null($j->finished_at) && !is_null($j->sla_due_at));
                $bucketDate = $job?->sla_due_at;
            } else {
                $bucketDate = $med->protest?->tipoNota === 'NA'
                    ? $med->protest?->dtConclusaoDesej
                    : $med->dtFimMedidaDesej;
            }

            if (!$bucketDate) {
                continue;
            }

            $year = (int) $bucketDate->format('Y');
            $month = (int) $bucketDate->format('n');
            $yearsMap[$year] = true;
            $totals[$year][$month] = ($totals[$year][$month] ?? 0) + 1;

            $diff = $now->diffInDays($bucketDate, false);
            if ($diff < 0) {
                $overdueByMonth[$year][$month] = ($overdueByMonth[$year][$month] ?? 0) + 1;
            } elseif ($diff <= 3) {
                $dueSoonByMonth[$year][$month] = ($dueSoonByMonth[$year][$month] ?? 0) + 1;
            } else {
                $withinByMonth[$year][$month] = ($withinByMonth[$year][$month] ?? 0) + 1;
            }
        }

        $years = array_keys($yearsMap);
        rsort($years);
        $selectedYear = (int) ($this->histogramYear ?: now()->year);
        if (!empty($years) && !in_array($selectedYear, $years, true)) {
            $selectedYear = (int) $years[0];
            $this->histogramYear = $selectedYear;
        }

        $counts = [];
        $overdueCounts = [];
        $dueSoonCounts = [];
        $withinCounts = [];
        for ($m = 1; $m <= 12; $m++) {
            $counts[] = (int) ($totals[$selectedYear][$m] ?? 0);
            $overdueCounts[] = (int) ($overdueByMonth[$selectedYear][$m] ?? 0);
            $dueSoonCounts[] = (int) ($dueSoonByMonth[$selectedYear][$m] ?? 0);
            $withinCounts[] = (int) ($withinByMonth[$selectedYear][$m] ?? 0);
        }

        return [
            'labels' => $monthNames,
            'counts' => $counts,
            'series' => [
                'overdue' => $overdueCounts,
                'dueSoon' => $dueSoonCounts,
                'within' => $withinCounts,
            ],
            'years' => $years,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $this->histogramMonth,
            'source' => $this->histogramSource,
        ];
    }

    public function render()
    {
        return view('livewire.protests.dispatch.lists', [
            'lists' => $this->lists,
            'protest_Types' =>  $this->ProtestTypes,
            'tipoNotas' => $this->TypeNotes,
            'dueTodayCount' => $this->dueTodayCount,
            'overdueCount' => $this->overdueCount,
            'histogramData' => $this->histogramData,
            'cityOptions' => $this->cityOptions,
            'codfOptions' => $this->codfOptions,
        ]);
    }

    protected function isSearching(): bool
    {
        return filled($this->search) || !empty($this->multisearch);
    }
}
