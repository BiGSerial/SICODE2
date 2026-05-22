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
    public ?string $histogramBucket = null; // YYYY-MM
    public ?string $histogramStackFilter = null; // overdue | due_soon | within
    public array $cityFilter = [];
    public array $selectedCodf = [];

    public $showDetails = false;
    public $selected = null;

    // Variáveis de seleção (Filtros)
    public array $selectedProtestType = [];
    public array $selectedTipoNota = [];
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
        'selectedProtestType' => ['except' => [], 'as' => 'pt'],
        'selectedTipoNota' => ['except' => [], 'as' => 'tn'],
        'cityFilter' => ['except' => [], 'as' => 'city'],
        'selectedCodf' => ['except' => [], 'as' => 'codf'],
        'histogramSource' => ['except' => 'desired', 'as' => 'hsrc'],
        'histogramBucket' => ['except' => null, 'as' => 'hbk'],
        'histogramStackFilter' => ['except' => null, 'as' => 'hsf'],
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

        $this->selectedTipoNota = collect((array) $this->selectedTipoNota)->filter()->values()->all();
        $this->selectedProtestType = collect((array) $this->selectedProtestType)->filter()->values()->all();
        $this->cityFilter = collect((array) $this->cityFilter)->filter()->values()->all();
        $this->selectedCodf = collect((array) $this->selectedCodf)->filter()->values()->all();
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
        $this->selectedTipoNota = collect((array) $value)->filter()->values()->all();
        $this->resetPage();
    }

    public function updatedSelectedProtestType($value): void
    {
        $this->selectedProtestType = collect((array) $value)->filter()->values()->all();
        $this->resetPage();
    }

    public function updatedCityFilter($value): void
    {
        $this->cityFilter = collect((array) $value)->filter()->values()->all();
        $this->resetPage();
    }

    public function updatedSelectedCodf($value): void
    {
        $this->selectedCodf = collect((array) $value)->filter()->values()->all();
        $this->resetPage();
    }

    public function updatedHistogramSource($value): void
    {
        if (!in_array($value, ['desired', 'sla'], true)) {
            $this->histogramSource = 'desired';
        }
        $this->histogramBucket = null;
        $this->histogramStackFilter = null;
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

    public function setHistogramBucket(?string $bucket = null): void
    {
        if (!$bucket || !preg_match('/^\d{4}\-\d{2}$/', $bucket)) {
            return;
        }

        $this->histogramBucket = $this->histogramBucket === $bucket ? null : $bucket;
        $this->resetPage();
    }

    public function setHistogramStackSelection(?string $bucket = null, ?string $segment = null): void
    {
        unset($bucket);

        $normalized = $this->normalizeHistogramSegment($segment);
        if (!$normalized) {
            return;
        }

        $this->histogramStackFilter = $this->histogramStackFilter === $normalized ? null : $normalized;
        $this->resetPage();
    }

    public function clearHistogramFilter(): void
    {
        $this->histogramBucket = null;
        $this->histogramStackFilter = null;
        $this->resetPage();
    }

    protected function normalizeHistogramSegment(?string $segment): ?string
    {
        $value = strtolower(trim((string) $segment));
        $value = str_replace('-', '_', $value);

        return match ($value) {
            'overdue', 'vencido', 'vencidos' => 'overdue',
            'due_soon', 'vencendo' => 'due_soon',
            'within', 'a_vencer', 'a vencer' => 'within',
            default => null,
        };
    }

    protected function applyHistogramSegmentCondition(Builder $query, string $column, ?string $segment): void
    {
        $normalized = $this->normalizeHistogramSegment($segment);
        if (!$normalized) {
            return;
        }

        $today = now()->startOfDay();
        $dueSoonEnd = $today->copy()->addDays(3)->endOfDay();

        if ($normalized === 'overdue') {
            $query->where($column, '<', $today);
            return;
        }

        if ($normalized === 'due_soon') {
            $query->whereBetween($column, [$today, $dueSoonEnd]);
            return;
        }

        $query->where($column, '>', $dueSoonEnd);
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
     * Considera "em aberto" para esta fila quando a medida não possui
     * qualquer vínculo com ProtestJob (não despachada).
     */
    protected function applyNoValidJobsCondition(Builder $query): void
    {
        $query->whereDoesntHave('ProtestJobs');
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

    public function goTo(int $medProtestId)
    {
        $med = MedProtest::query()->select('id')->find($medProtestId);
        if (!$med) {
            return;
        }

        return redirect()->route('protests.dispatch.view', [
            'protest' => $med->id,
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
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = MedProtest::query()
            ->with([
                'Protest:id,nota,tipoNota,codecodf,txtGrpCodificacao,descCausa,descricao,cidade,dtAberturaNota,dtConclusaoDesej',
                'Protest.Notes',
                'Notes',
                'ProtestJobs' => fn ($job) => $job->orderByDesc('created_at'),
            ])
            ->where('statusSist', 'MEDA');

        $this->applyNoValidJobsCondition($query);
        $this->applyBtzeroVisibilityFilter($query);

        $query->when($this->search, function ($query) {
            $this->multisearch   = [];
            $this->advanceSearch = '';
            $this->resetPage();

            $formatted = $this->formatWithWildcard($this->search);

            $query->where(function ($q) use ($formatted) {
                $q->whereHas('Protest', function ($protestQuery) use ($formatted) {
                    $protestQuery->where('nota', $formatted->type, $formatted->search)
                        ->orWhere('txtGrpCodificacao', $formatted->type, $formatted->search)
                        ->orWhere('cidade', $formatted->type, $formatted->search)
                        ->orWhere('codecodf', $formatted->type, $formatted->search);
                })->orWhereHas('Notes', function ($noteQuery) use ($formatted) {
                    $noteQuery->where('note', $formatted->type, $formatted->search)
                        ->orWhere('material', $formatted->type, $formatted->search);
                })->orWhereHas('Protest.Notes', function ($noteQuery) use ($formatted) {
                    $noteQuery->where('note', $formatted->type, $formatted->search)
                        ->orWhere('material', $formatted->type, $formatted->search);
                });
            });
        });

        $query->when($this->multisearch, function ($query) {
            $query->where(function ($sub) {
                $sub->whereHas('Protest', function ($protestQuery) {
                    $protestQuery->whereIn('nota', $this->multisearch);
                })->orWhereHas('Notes', function ($noteQuery) {
                    $noteQuery->whereIn('note', $this->multisearch);
                })->orWhereHas('Protest.Notes', function ($noteQuery) {
                        $noteQuery->whereIn('note', $this->multisearch);
                    });
            });
        });

        $query->when(!empty($this->selectedTipoNota), function ($query) {
            $query->whereHas('Protest', function ($protestQuery) {
                $protestQuery->whereIn('tipoNota', $this->selectedTipoNota);
            });
        });

        $query->when(!empty($this->selectedProtestType), function ($query) {
            $query->whereIn('protest_type', $this->selectedProtestType);
        });

        $query->when(!empty($this->cityFilter), function ($query) {
            $query->whereHas('Protest', function ($protestQuery) {
                $protestQuery->whereIn('cidade', $this->cityFilter);
            });
        });

        $query->when(!empty($this->selectedCodf), function ($query) {
            $query->whereHas('Protest', function ($protestQuery) {
                $protestQuery->whereIn('codecodf', $this->selectedCodf);
            });
        });

        if (isset($this->filter['city'])) {
            $query->whereHas('Protest', function ($protestQuery) {
                $protestQuery->whereIn('cidade', $this->filter['city']);
            });
        }

        if (!$ignoreStatusCard && $this->statusCardFilter) {
            $today = Carbon::today();

            if ($this->statusCardFilter === 'due_today') {
                $this->applyMedDeadlineCondition($query, $today, '=');
            } elseif ($this->statusCardFilter === 'overdue') {
                $this->applyMedDeadlineCondition($query, $today, '<');
            }
        }

        $hasHistogramBucket = !$ignoreHistogram
            && $this->histogramBucket
            && preg_match('/^\d{4}\-\d{2}$/', (string) $this->histogramBucket);
        $selectedStack = !$ignoreHistogram ? $this->normalizeHistogramSegment($this->histogramStackFilter) : null;
        $bucketYear = null;
        $bucketMonth = null;
        if ($hasHistogramBucket) {
            [$bucketYear, $bucketMonth] = explode('-', (string) $this->histogramBucket);
            $bucketYear = (int) $bucketYear;
            $bucketMonth = (int) $bucketMonth;
        }

        if ($hasHistogramBucket || $selectedStack) {
            if ($this->histogramSource === 'sla') {
                $query->whereHas('ProtestJobs', function ($jobQuery) use ($hasHistogramBucket, $bucketYear, $bucketMonth) {
                    $jobQuery->whereNull('finished_at')
                        ->where('confirmed', '!=', true);

                    if ($hasHistogramBucket) {
                        $jobQuery
                            ->whereYear('sla_due_at', $bucketYear)
                            ->whereMonth('sla_due_at', $bucketMonth);
                    }

                    $this->applyHistogramSegmentCondition($jobQuery, 'sla_due_at', $this->histogramStackFilter);
                });
            } else {
                $query->where(function ($scope) use ($hasHistogramBucket, $bucketYear, $bucketMonth) {
                    $scope->whereHas('Protest', function ($p) use ($hasHistogramBucket, $bucketYear, $bucketMonth) {
                        $p->where('tipoNota', 'NA');
                        if ($hasHistogramBucket) {
                            $p->whereYear('dtConclusaoDesej', $bucketYear)
                                ->whereMonth('dtConclusaoDesej', $bucketMonth);
                        }
                        $this->applyHistogramSegmentCondition($p, 'dtConclusaoDesej', $this->histogramStackFilter);
                    })->orWhere(function ($med) use ($hasHistogramBucket, $bucketYear, $bucketMonth) {
                        $med->whereHas('Protest', function ($p) {
                            $p->where(function ($type) {
                                $type->where('tipoNota', '!=', 'NA')->orWhereNull('tipoNota');
                            });
                        });
                        if ($hasHistogramBucket) {
                            $med->whereYear('dtFimMedidaDesej', $bucketYear)
                                ->whereMonth('dtFimMedidaDesej', $bucketMonth);
                        }
                        $this->applyHistogramSegmentCondition($med, 'dtFimMedidaDesej', $this->histogramStackFilter);
                    });
                });
            }
        }

        // Sem recorte temporal nesta tela:
        // o histograma e a lista devem refletir todas as medidas em aberto.

        return $query;
    }

    public function getListsProperty()
    {
        $query = $this->openListsQuery()
            ->leftJoin('protests', 'protests.id', '=', 'med_protests.protest_id')
            ->select('med_protests.*');

        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        $sort = $this->sortBy;

        $query->reorder();

        if ($sort === 'med_id') {
            $query->orderBy('med_protests.med_id', $direction);
        } elseif ($sort === 'nota') {
            $query->orderBy('protests.nota', $direction);
        } elseif ($sort === 'tipo_nota') {
            $query->orderBy('protests.tipoNota', $direction);
        } elseif ($sort === 'cod') {
            $query->orderBy('med_protests.codMedida', $direction);
        } elseif ($sort === 'codf') {
            $query->orderBy('protests.codecodf', $direction);
        } elseif ($sort === 'tipo_reclamacao') {
            $query->orderBy('protests.txtGrpCodificacao', $direction);
        } elseif ($sort === 'tx_cod_medida') {
            $query->orderBy('med_protests.txtCodMedida', $direction);
        } elseif ($sort === 'causa_raiz') {
            $query->orderBy('protests.descCausa', $direction);
        } elseif ($sort === 'origem') {
            $query->orderBy('protests.descricao', $direction);
        } elseif ($sort === 'municipio') {
            $query->orderBy('protests.cidade', $direction);
        } elseif ($sort === 'abertura_nota') {
            $query->orderBy('protests.dtAberturaNota', $direction);
        } elseif ($sort === 'abertura_medida') {
            $query->orderBy('med_protests.dtCriacaoMedida', $direction);
        } elseif ($sort === 'vencimento') {
            $query->orderByRaw("
                CASE
                    WHEN protests.tipoNota = 'NA' THEN protests.dtConclusaoDesej
                    ELSE med_protests.dtFimMedidaDesej
                END {$direction}
            ");
        } else {
            $query->orderByRaw("
                CASE
                    WHEN protests.tipoNota = 'NA' THEN protests.dtConclusaoDesej
                    ELSE med_protests.dtFimMedidaDesej
                END ASC
            ");
        }

        $query->orderBy('med_protests.id', 'ASC');

        return $query->paginate($this->perPage);
    }

    public function getHistogramDataProperty(): array
    {
        $measures = $this->openListsQuery(ignoreStatusCard: false, ignoreHistogram: true)->get();

        $totals = [];
        $overdueByMonth = [];
        $dueSoonByMonth = [];
        $withinByMonth = [];
        $now = now();

        foreach ($measures as $med) {
            $protest = $med->Protest;
            $desiredDate = $protest->tipoNota === 'NA'
                ? $protest->dtConclusaoDesej
                : $med?->dtFimMedidaDesej;
            $bucketDate = null;
            if ($this->histogramSource === 'sla') {
                $job = $med?->ProtestJobs
                    ?->where('confirmed', '!=', true)
                    ->first(fn ($j) => is_null($j->finished_at) && !is_null($j->sla_due_at));
                // Mantém o universo igual à lista: fallback para data desejada.
                $bucketDate = $job?->sla_due_at ?? $desiredDate;
            } else {
                $bucketDate = $desiredDate;
            }

            if (!$bucketDate) {
                continue;
            }

            $normalized = Carbon::parse($bucketDate)->copy()->startOfDay();
            $monthKey = $normalized->format('Y-m');
            $totals[$monthKey] = ($totals[$monthKey] ?? 0) + 1;

            $diff = $now->diffInDays($normalized, false);
            if ($diff < 0) {
                $overdueByMonth[$monthKey] = ($overdueByMonth[$monthKey] ?? 0) + 1;
            } elseif ($diff <= 3) {
                $dueSoonByMonth[$monthKey] = ($dueSoonByMonth[$monthKey] ?? 0) + 1;
            } else {
                $withinByMonth[$monthKey] = ($withinByMonth[$monthKey] ?? 0) + 1;
            }
        }

        $monthKeys = array_keys($totals);
        sort($monthKeys);

        $overdueCounts = [];
        $dueSoonCounts = [];
        $withinCounts = [];
        $monthLabels = [];
        $monthTotals = [];
        foreach ($monthKeys as $monthKey) {
            $overdueCounts[] = (int) ($overdueByMonth[$monthKey] ?? 0);
            $dueSoonCounts[] = (int) ($dueSoonByMonth[$monthKey] ?? 0);
            $withinCounts[] = (int) ($withinByMonth[$monthKey] ?? 0);
            $monthTotals[$monthKey] = (int) ($totals[$monthKey] ?? 0);
            $monthLabels[$monthKey] = Carbon::createFromFormat('Y-m', $monthKey)->format('m/Y');
        }

        $selectedBucket = in_array((string) $this->histogramBucket, $monthKeys, true)
            ? (string) $this->histogramBucket
            : null;

        $displayMonthKeys = $monthKeys;
        $displayLabels = array_values($monthLabels);
        $displayOverdueCounts = $overdueCounts;
        $displayDueSoonCounts = $dueSoonCounts;
        $displayWithinCounts = $withinCounts;

        if ($selectedBucket) {
            $index = array_search($selectedBucket, $monthKeys, true);
            if ($index !== false) {
                $displayMonthKeys = [$selectedBucket];
                $displayLabels = [$monthLabels[$selectedBucket] ?? $selectedBucket];
                $displayOverdueCounts = [(int) ($overdueCounts[$index] ?? 0)];
                $displayDueSoonCounts = [(int) ($dueSoonCounts[$index] ?? 0)];
                $displayWithinCounts = [(int) ($withinCounts[$index] ?? 0)];
            }
        }

        return [
            'labels' => $displayLabels,
            'monthKeys' => $displayMonthKeys,
            'monthTotals' => $monthTotals,
            'monthLabels' => $monthLabels,
            'series' => [
                'overdue' => $overdueCounts,
                'dueSoon' => $dueSoonCounts,
                'within' => $withinCounts,
                'displayOverdue' => $displayOverdueCounts,
                'displayDueSoon' => $displayDueSoonCounts,
                'displayWithin' => $displayWithinCounts,
            ],
            'selectedBucket' => $selectedBucket,
            'selectedStack' => $this->normalizeHistogramSegment($this->histogramStackFilter),
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
