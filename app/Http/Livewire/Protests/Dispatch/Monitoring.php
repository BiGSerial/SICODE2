<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Enum\ProtestJobStatus;
use App\Enum\ProtestJobPriority;
use App\Jobs\Protests\ExportMonitoringProtestJobsJob;
use App\Models\MedProtest;
use App\Models\Protest;
use App\Models\ProtestJob;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Monitoring extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public int $perPage = 50;

    /** Filtros */
    public string $search     = '';
    public string $searchName = '';
    public array $userViewer  = [];
    public bool $onlySelectedUser = false;

    /** Filtro por tipo de nota (NA / OU / PR) */
    public array $typeNote  = [];
    public array $protestType = [];

    /** Filtro por SLA (overdue / dueSoon / within) */
    public array $slaFilter = [];
    public array $jobStatusFilter = [];
    public array $priorityFilter = [];
    public array $sapStatusFilter = [];
    public array $ownerScope = []; // assigned | unassigned
    public string $sortBy = 'sla_due_at';
    public string $sortDirection = 'asc';

    /** Lista de usuários para o select */
    public $userViewerList = [];
    public array $noteTypeOptions = [];
    public array $protestTypeOptions = [];

    public bool $showOnlyBtzero = false;
    public bool $hideBtzero = true;
    public ?string $deadlineCardFilter = null;
    public string $histogramSource = 'desired';
    public string $histogramStatusScope = 'meda'; // meda | mede | both
    public ?string $histogramBucket = null; // YYYY-MM
    public ?string $histogramStackFilter = null; // overdue | due_soon | within

    protected $queryString = [
        'perPage'    => ['except' => 50],
        'search'     => ['except' => ''],
        'userViewer' => ['except' => []],
        'onlySelectedUser' => ['except' => false],
        'typeNote'   => ['except' => []],
        'protestType' => ['except' => []],
        'slaFilter'  => ['except' => []],
        'jobStatusFilter' => ['except' => []],
        'priorityFilter' => ['except' => []],
        'sapStatusFilter' => ['except' => []],
        'ownerScope' => ['except' => []],
        'sortBy' => ['except' => 'sla_due_at'],
        'sortDirection' => ['except' => 'asc'],
        'deadlineCardFilter' => ['except' => null],
        'histogramSource' => ['except' => 'desired'],
        'histogramStatusScope' => ['except' => 'meda'],
        'histogramBucket' => ['except' => null],
        'histogramStackFilter' => ['except' => null],
    ];

    protected $listeners = [
        'refresh' => '$refresh',
        'refreshComponent' => '$refresh',
    ];

    public function mount($showOnlyBtzero = null, $hideBtzero = null): void
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

        $this->userViewer = collect((array) $this->userViewer)->filter()->values()->all();
        $this->typeNote = collect((array) $this->typeNote)->filter()->values()->all();
        $this->protestType = collect((array) $this->protestType)->filter()->values()->all();
        $this->slaFilter = collect((array) $this->slaFilter)->filter()->values()->all();
        $this->jobStatusFilter = collect((array) $this->jobStatusFilter)->filter()->values()->all();
        $this->priorityFilter = collect((array) $this->priorityFilter)->filter()->values()->all();
        $this->sapStatusFilter = collect((array) $this->sapStatusFilter)->filter()->values()->all();
        $this->ownerScope = collect((array) $this->ownerScope)->filter()->values()->all();
        if (!in_array($this->histogramStatusScope, ['meda', 'mede', 'both'], true)) {
            $this->histogramStatusScope = 'meda';
        }

        $this->loadUserViewerList();
        $this->loadNoteTypeOptions();
        $this->loadProtestTypeOptions();
    }

    protected function loadUserViewerList(): void
    {
        $this->userViewerList = User::query()
            ->when($this->searchName !== '', function ($q) {
                $q->where('name', 'like', '%'.$this->searchName.'%');
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function updatedSearchName($value): void
    {
        $this->loadUserViewerList();
    }

    public function updatedTypeNote($value): void
    {
        $this->typeNote = collect((array) $value)->filter()->values()->all();
        $this->resetPage();
    }

    public function updatedProtestType($value): void
    {
        $this->protestType = collect((array) $value)->filter()->values()->all();
        $this->resetPage();
    }

    public function updatedUserViewer($value): void
    {
        $this->userViewer = collect((array) $value)->filter()->values()->all();
        if (empty($this->userViewer)) {
            $this->onlySelectedUser = false;
        }

        $this->resetPage();
    }

    public function updatedOnlySelectedUser(): void
    {
        $this->resetPage();
    }

    public function updatedJobStatusFilter($value): void
    {
        $this->jobStatusFilter = collect((array) $value)->filter()->values()->all();
        $this->resetPage();
    }

    public function updatedPriorityFilter($value): void
    {
        $this->priorityFilter = collect((array) $value)->filter()->values()->all();
        $this->resetPage();
    }

    public function updatedSapStatusFilter($value): void
    {
        $this->sapStatusFilter = collect((array) $value)->filter()->values()->all();
        $this->resetPage();
    }

    public function updatedOwnerScope($value): void
    {
        $this->ownerScope = collect((array) $value)->filter()->values()->all();
        $this->resetPage();
    }

    public function updatedSortBy($value): void
    {
        $allowed = [
            'priority',
            'dispatcher',
            'tipo_nota',
            'nota',
            'medida',
            'cod',
            'tipo_reclamacao',
            'municipio',
            'responsavel',
            'empresa',
            'abertura',
            'fim_desejado',
            'sent_at',
            'sla_due_at',
            'sap_status',
            'status',
            'created_at',
            'updated_at',
            'finished_at',
        ];
        if (!in_array($value, $allowed, true)) {
            $this->sortBy = 'sla_due_at';
        }

        $this->resetPage();
    }

    public function updatedSortDirection($value): void
    {
        $this->sortDirection = in_array($value, ['asc', 'desc'], true) ? $value : 'asc';
        $this->resetPage();
    }

    public function sortByColumn(string $column): void
    {
        $allowed = [
            'priority',
            'dispatcher',
            'tipo_nota',
            'nota',
            'medida',
            'cod',
            'tipo_reclamacao',
            'municipio',
            'responsavel',
            'empresa',
            'abertura',
            'fim_desejado',
            'sent_at',
            'sla_due_at',
            'sap_status',
            'status',
            'created_at',
            'updated_at',
            'finished_at',
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

    public function updatedHistogramSource($value): void
    {
        if (!in_array($value, ['desired', 'sla'], true)) {
            $this->histogramSource = 'desired';
        }

        $this->histogramBucket = null;
        $this->histogramStackFilter = null;
        $this->resetPage();
    }

    public function updatedHistogramStatusScope($value): void
    {
        if (!in_array($value, ['meda', 'mede', 'both'], true)) {
            $this->histogramStatusScope = 'meda';
        }

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

    protected function applyHistogramSegmentCondition($query, string $column, ?string $segment): void
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

    protected function applyHistogramStatusScope($query): void
    {
        if ($this->histogramStatusScope === 'both') {
            return;
        }

        $status = $this->histogramStatusScope === 'mede' ? 'MEDE' : 'MEDA';
        $query->whereHas('medProtest', function ($sub) use ($status) {
            $sub->where('statusSist', $status);
        });
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

    protected function loadNoteTypeOptions(): void
    {
        $this->noteTypeOptions = Protest::query()
            ->select('tipoNota')
            ->whereNotNull('tipoNota')
            ->distinct()
            ->orderBy('tipoNota')
            ->pluck('tipoNota')
            ->filter()
            ->values()
            ->toArray();
    }

    protected function loadProtestTypeOptions(): void
    {
        $this->protestTypeOptions = Protest::query()
            ->select('type')
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->filter()
            ->values()
            ->toArray();
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

    /** Ajusta o filtro por tipo de nota */
    public function setTypeNote(?string $type = null): void
    {
        $this->typeNote = $type ? [$type] : [];
        $this->resetPage();
    }

    /** Clicar no card de SLA (total/overdue/dueSoon/within) */
    public function setSlaFilter(?string $mode = null): void
    {
        $this->slaFilter = $mode ? [$mode] : [];
        $this->resetPage();
    }

    public function setDeadlineCardFilter(?string $filter = null): void
    {
        if ($this->deadlineCardFilter === $filter) {
            $this->deadlineCardFilter = null;
        } else {
            $this->deadlineCardFilter = $filter;
        }

        $this->resetPage();
    }

    protected function coreQuery(): Builder
    {
        $query = ProtestJob::query()
            ->where(function ($q) {
                $q->whereNull('confirmed')
                    ->orWhere('confirmed', false);
            });

        if ($this->showOnlyBtzero) {
            $query->whereHas('medProtest', function ($q) {
                $q->identifiedAsBtzero();
            });
        } elseif ($this->hideBtzero) {
            $query->where(function ($q) {
                $q->whereNull('med_protest_id')
                    ->orWhereHas('medProtest', function ($sub) {
                        $sub->notIdentifiedAsBtzero();
                    });
            });
        }

        return $query;
    }

    /** Query base dos jobs */
    protected function baseQuery(bool $ignoreDeadlineFilter = false, bool $ignoreHistogramFilter = false)
    {
        $query = $this->coreQuery()
            ->with([
                'medProtest',
                'medProtest.Comments' => function ($q) {
                    $q->orderByDesc('created_at'); // última mensagem primeiro
                },
                'protest',
                'owner:id,name,company_id',
                'owner.company:id,name',
                'creator:id,name',
                'closer:id,name',
            ])
            ->orderBy('id');

        // Filtro por responsável / hierarquia
        $query->when(!empty($this->userViewer), function ($q) {
            $users = User::whereIn('id', $this->userViewer)->get();
            if ($users->isEmpty()) {
                return;
            }

            $ownerIds = [];
            foreach ($users as $user) {
                if ($this->onlySelectedUser) {
                    $ownerIds[] = $user->id;
                } else {
                    $ownerIds = array_merge(
                        $ownerIds,
                        $user->descendantsQuery(true, true, true)->pluck('users.id')->toArray()
                    );
                }
            }
            $ownerIds = array_values(array_unique($ownerIds));

            $q->where(function ($qq) use ($ownerIds) {
                $qq->whereIn('owner_id', $ownerIds);

                if (!$this->onlySelectedUser) {
                    $qq->orWhereNull('owner_id');
                }
            });
        });

        // Busca geral (topo)
        $query->when($this->search, function ($q) {
            $term = '%'.$this->search.'%';

            $q->where(function ($qq) use ($term) {
                $qq->where('id', 'like', $term)
                    ->orWhereHas('protest', function ($sub) use ($term) {
                        $sub->where('nota', 'like', $term)
                            ->orWhere('cidade', 'like', $term)
                            ->orWhere('txtGrpCodificacao', 'like', $term)
                            ->orWhere('codecodf', 'like', $term);
                    })
                    ->orWhereHas('owner', function ($sub) use ($term) {
                        $sub->where('name', 'like', $term);
                    });
            });
        });

        // Filtro por tipo de nota (NA / OU / PR)
        $query->when(!empty($this->typeNote), function ($q) {
            $q->whereHas('protest', function ($sub) {
                $sub->whereIn('tipoNota', $this->typeNote);
            });
        });

        $query->when(!empty($this->protestType), function ($q) {
            $q->whereHas('protest', function ($sub) {
                $sub->whereIn('type', $this->protestType);
            });
        });

        // Status do job
        $query->when(!empty($this->jobStatusFilter), function ($q) {
            $q->whereIn('status', $this->jobStatusFilter);
        });

        // Prioridade
        $query->when(!empty($this->priorityFilter), function ($q) {
            $q->whereIn('priority', $this->priorityFilter);
        });

        // Status SAP/Medida
        $query->when(!empty($this->sapStatusFilter), function ($q) {
            $sap = collect($this->sapStatusFilter)
                ->map(fn ($item) => mb_strtoupper((string) $item))
                ->values()
                ->all();
            $q->whereHas('medProtest', function ($sub) use ($sap) {
                $sub->whereIn('statusSist', $sap);
            });
        });

        // Escopo de responsável
        $query->when(!empty($this->ownerScope), function ($q) {
            $scopes = collect($this->ownerScope)->values();
            if ($scopes->contains('assigned') && $scopes->contains('unassigned')) {
                return;
            }

            if ($scopes->contains('assigned')) {
                $q->whereNotNull('owner_id');
            } elseif ($scopes->contains('unassigned')) {
                $q->whereNull('owner_id');
            }
        });

        // Filtro por SLA
        $query->when(!empty($this->slaFilter), function ($q) {
            $now = now();

            $q->whereNotNull('sla_due_at');
            $filters = collect($this->slaFilter)->values();
            if ($filters->count() >= 3) {
                return;
            }

            $q->where(function ($slaScope) use ($now, $filters) {
                if ($filters->contains('overdue')) {
                    $slaScope->orWhere('sla_due_at', '<', $now);
                }
                if ($filters->contains('dueSoon')) {
                    $slaScope->orWhereBetween('sla_due_at', [$now, $now->clone()->addDays(3)]);
                }
                if ($filters->contains('within')) {
                    $slaScope->orWhere('sla_due_at', '>', $now->clone()->addDays(3));
                }
            });
        });

        if (!$ignoreDeadlineFilter && $this->deadlineCardFilter) {
            $today = now()->toDateString();

            if ($this->deadlineCardFilter === 'due_today') {
                $query->whereHas('medProtest', function ($sub) {
                    $sub->where('statusSist', 'MEDA');
                });
                $query->where(function ($q) use ($today) {
                    $q->whereHas('protest', function ($sub) use ($today) {
                        $sub->where('tipoNota', 'NA')
                            ->whereDate('dtConclusaoDesej', $today);
                    })->orWhereHas('medProtest', function ($sub) use ($today) {
                        $sub->whereDate('dtFimMedidaDesej', $today);
                    });
                });
            } elseif ($this->deadlineCardFilter === 'overdue') {
                $query->whereHas('medProtest', function ($sub) {
                    $sub->where('statusSist', 'MEDA');
                });
                $query->where(function ($q) use ($today) {
                    $q->whereHas('protest', function ($sub) use ($today) {
                        $sub->where('tipoNota', 'NA')
                            ->whereDate('dtConclusaoDesej', '<', $today);
                    })->orWhereHas('medProtest', function ($sub) use ($today) {
                        $sub->whereDate('dtFimMedidaDesej', '<', $today);
                    });
                });
            } elseif ($this->deadlineCardFilter === 'finished_pending') {
                $query->where('status', ProtestJobStatus::DONE->value);
            }
        }

        $hasHistogramBucket = !$ignoreHistogramFilter
            && $this->histogramBucket
            && preg_match('/^\d{4}\-\d{2}$/', (string) $this->histogramBucket);
        $selectedStack = !$ignoreHistogramFilter ? $this->normalizeHistogramSegment($this->histogramStackFilter) : null;

        $bucketYear = null;
        $bucketMonth = null;
        if ($hasHistogramBucket) {
            [$bucketYear, $bucketMonth] = explode('-', (string) $this->histogramBucket);
            $bucketYear = (int) $bucketYear;
            $bucketMonth = (int) $bucketMonth;
        }

        if ($hasHistogramBucket || $selectedStack) {
            $this->applyHistogramStatusScope($query);

            if ($this->histogramSource === 'sla') {
                $query->whereNull('finished_at')
                    ->whereNotNull('sla_due_at');

                if ($hasHistogramBucket) {
                    $query->whereYear('sla_due_at', $bucketYear)
                        ->whereMonth('sla_due_at', $bucketMonth);
                }

                $this->applyHistogramSegmentCondition($query, 'sla_due_at', $selectedStack);
            } else {
                $query->whereHas('medProtest', function ($sub) use ($hasHistogramBucket, $bucketYear, $bucketMonth, $selectedStack) {
                    $sub->where(function ($scope) use ($hasHistogramBucket, $bucketYear, $bucketMonth, $selectedStack) {
                        $scope->whereHas('protest', function ($p) use ($hasHistogramBucket, $bucketYear, $bucketMonth, $selectedStack) {
                            $p->where('tipoNota', 'NA')->whereNotNull('dtConclusaoDesej');
                            if ($hasHistogramBucket) {
                                $p->whereYear('dtConclusaoDesej', $bucketYear)
                                    ->whereMonth('dtConclusaoDesej', $bucketMonth);
                            }
                            $this->applyHistogramSegmentCondition($p, 'dtConclusaoDesej', $selectedStack);
                        })->orWhere(function ($mp) use ($hasHistogramBucket, $bucketYear, $bucketMonth, $selectedStack) {
                            $mp->whereHas('protest', function ($p) {
                                $p->where(function ($t) {
                                    $t->where('tipoNota', '!=', 'NA')->orWhereNull('tipoNota');
                                });
                            })->whereNotNull('dtFimMedidaDesej');

                            if ($hasHistogramBucket) {
                                $mp->whereYear('dtFimMedidaDesej', $bucketYear)
                                    ->whereMonth('dtFimMedidaDesej', $bucketMonth);
                            }

                            $this->applyHistogramSegmentCondition($mp, 'dtFimMedidaDesej', $selectedStack);
                        });
                    });
                });
            }
        }

        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        $sortKey = $this->sortBy;
        $query->reorder();

        switch ($sortKey) {
            case 'dispatcher':
                $query->orderBy(
                    User::query()->select('name')
                        ->whereColumn('users.id', 'protest_jobs.created_by')
                        ->limit(1),
                    $direction
                );
                break;
            case 'tipo_nota':
                $query->orderBy(
                    Protest::query()->select('tipoNota')
                        ->whereColumn('protests.id', 'protest_jobs.protest_id')
                        ->limit(1),
                    $direction
                );
                break;
            case 'nota':
                $query->orderBy(
                    Protest::query()->select('nota')
                        ->whereColumn('protests.id', 'protest_jobs.protest_id')
                        ->limit(1),
                    $direction
                );
                break;
            case 'medida':
                $query->orderBy(
                    \App\Models\MedProtest::query()->select('med_id')
                        ->whereColumn('med_protests.id', 'protest_jobs.med_protest_id')
                        ->limit(1),
                    $direction
                );
                break;
            case 'cod':
                $query->orderBy(
                    Protest::query()->select('codecodf')
                        ->whereColumn('protests.id', 'protest_jobs.protest_id')
                        ->limit(1),
                    $direction
                );
                break;
            case 'tipo_reclamacao':
                $query->orderBy(
                    Protest::query()->select('txtGrpCodificacao')
                        ->whereColumn('protests.id', 'protest_jobs.protest_id')
                        ->limit(1),
                    $direction
                );
                break;
            case 'municipio':
                $query->orderBy(
                    Protest::query()->select('cidade')
                        ->whereColumn('protests.id', 'protest_jobs.protest_id')
                        ->limit(1),
                    $direction
                );
                break;
            case 'responsavel':
                $query->orderBy(
                    User::query()->select('name')
                        ->whereColumn('users.id', 'protest_jobs.owner_id')
                        ->limit(1),
                    $direction
                );
                break;
            case 'empresa':
                $query->orderByRaw(
                    "(select c.name from companies c join users u on u.company_id = c.id where u.id = protest_jobs.owner_id limit 1) {$direction}"
                );
                break;
            case 'abertura':
                $query->orderByRaw(
                    "(case when (select p.tipoNota from protests p where p.id = protest_jobs.protest_id limit 1) = 'NA'
                        then (select p.dtAberturaNota from protests p where p.id = protest_jobs.protest_id limit 1)
                        else (select mp.dtCriacaoMedida from med_protests mp where mp.id = protest_jobs.med_protest_id limit 1)
                     end) {$direction}"
                );
                break;
            case 'fim_desejado':
                $query->orderByRaw(
                    "(case when (select p.tipoNota from protests p where p.id = protest_jobs.protest_id limit 1) = 'NA'
                        then (select p.dtConclusaoDesej from protests p where p.id = protest_jobs.protest_id limit 1)
                        else (select mp.dtFimMedidaDesej from med_protests mp where mp.id = protest_jobs.med_protest_id limit 1)
                     end) {$direction}"
                );
                break;
            case 'sap_status':
                $query->orderByRaw(
                    "(case (select mp.statusSist from med_protests mp where mp.id = protest_jobs.med_protest_id limit 1)
                        when 'MEDA' then 'ABER'
                        when 'MEDE' then 'ENC'
                        else ''
                     end) {$direction}"
                );
                break;
            default:
                $sortColumn = in_array($sortKey, ['priority', 'sent_at', 'sla_due_at', 'status', 'created_at', 'updated_at', 'finished_at'], true)
                    ? $sortKey
                    : 'sla_due_at';
                $query->orderBy($sortColumn, $direction);
                break;
        }

        $query->orderBy('id', 'asc');

        return $query;
    }

    public function getCoreTotalProperty(): int
    {
        return (clone $this->coreQuery())->count();
    }

    public function getCoreDonePendingProperty(): int
    {
        return (clone $this->coreQuery())
            ->where('status', ProtestJobStatus::DONE->value)
            ->count();
    }

    public function getFixedFiltersProperty(): array
    {
        $filters = [
            'Somente atividades em andamento (não confirmadas)',
        ];

        if ($this->showOnlyBtzero) {
            $filters[] = 'Escopo fixo: apenas BT Zero';
        } elseif ($this->hideBtzero) {
            $filters[] = 'Escopo fixo: sem BT Zero';
        }

        return $filters;
    }

    public function getVariableFiltersProperty(): array
    {
        $filters = [];

        if ($this->search !== '') {
            $filters[] = ['source' => 'Formulário', 'label' => 'Busca geral'];
        }

        if (!empty($this->userViewer)) {
            $filters[] = ['source' => 'Formulário', 'label' => 'Responsável/hierarquia'];
        }

        if ($this->onlySelectedUser) {
            $filters[] = ['source' => 'Formulário', 'label' => 'Apenas usuário selecionado'];
        }

        if (!empty($this->typeNote)) {
            $filters[] = ['source' => 'Formulário', 'label' => 'Tipo de nota'];
        }

        if (!empty($this->protestType)) {
            $filters[] = ['source' => 'Formulário', 'label' => 'Tipo de reclamação'];
        }

        if (!empty($this->jobStatusFilter)) {
            $filters[] = ['source' => 'Formulário', 'label' => 'Status do job'];
        }

        if (!empty($this->priorityFilter)) {
            $filters[] = ['source' => 'Formulário', 'label' => 'Prioridade'];
        }

        if (!empty($this->sapStatusFilter)) {
            $filters[] = ['source' => 'Formulário', 'label' => 'Status SAP'];
        }

        if (!empty($this->ownerScope)) {
            $filters[] = ['source' => 'Formulário', 'label' => 'Escopo de responsável'];
        }

        if (!empty($this->slaFilter)) {
            $filters[] = ['source' => 'Formulário', 'label' => 'Faixa SLA'];
        }

        if ($this->deadlineCardFilter) {
            $label = match ($this->deadlineCardFilter) {
                'due_today' => 'Card: Vencendo hoje (apenas MEDA)',
                'overdue' => 'Card: Vencidos (apenas MEDA)',
                'finished_pending' => 'Card: Finalizados pendentes',
                default => 'Card de prazo',
            };
            $filters[] = ['source' => 'Cards de prazo', 'label' => $label];
        }

        if ($this->histogramBucket) {
            $label = preg_match('/^\d{4}\-\d{2}$/', $this->histogramBucket)
                ? Carbon::createFromFormat('Y-m', $this->histogramBucket)->format('m/Y')
                : $this->histogramBucket;
            $filters[] = ['source' => 'Histograma', 'label' => 'Mês selecionado: '.$label];
        }

        if ($this->histogramStackFilter) {
            $segment = $this->normalizeHistogramSegment($this->histogramStackFilter);
            $segmentLabel = match ($segment) {
                'overdue' => 'Faixa: vencidos',
                'due_soon' => 'Faixa: vencendo até 3 dias',
                'within' => 'Faixa: a vencer',
                default => 'Faixa de prazo',
            };
            $filters[] = ['source' => 'Histograma', 'label' => $segmentLabel];
        }

        if ($this->histogramBucket || $this->histogramStackFilter) {
            $scopeLabel = match ($this->histogramStatusScope) {
                'mede' => 'Status medida: MEDE',
                'both' => 'Status medida: MEDA + MEDE',
                default => 'Status medida: MEDA',
            };
            $filters[] = ['source' => 'Histograma', 'label' => $scopeLabel];
        }

        return $filters;
    }

    /** Lista paginada */
    public function getListsProperty()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    /** Estatisticas para os cards (inclui mensagens e prazos desejados) */
    public function getStatsProperty(): array
    {
        $base = $this->baseQuery();
        $jobs = (clone $base)->get();
        $total = $jobs->count();

        $overdue = 0;
        $dueSoon = 0;
        $within = 0;
        $referenceDate = now();

        $currentUserId = auth()->id();
        $respondedMessages = 0; // Ultima msg nao e do despachante
        $pendingForYouMessages = 0; // Ultima msg nao e do despachante e nao e do usuario logado

        foreach ($jobs as $job) {
            $desiredDate = $this->resolveDesiredDate($job);

            if ($desiredDate) {
                $diffInDays = $referenceDate->diffInDays($desiredDate, false);

                if ($diffInDays < 0) {
                    $overdue++;
                } elseif ($diffInDays <= 3) {
                    $dueSoon++;
                } else {
                    $within++;
                }
            } else {
                $within++;
            }

            $creatorId = $job->created_by
                ?? $job->creator_id
                ?? optional($job->creator)->id;

            if (!$creatorId) {
                continue;
            }

            $lastComment = $job->medProtest?->Comments?->first();

            if (!$lastComment) {
                continue;
            }

            $authorId = $lastComment->user_id;

            if (!$authorId) {
                continue;
            }

            $isFromDispatcher  = $authorId === $creatorId;
            $isFromCurrentUser = $currentUserId && $authorId === $currentUserId;

            if (!$isFromDispatcher) {
                $respondedMessages++;

                if (!$isFromCurrentUser) {
                    $pendingForYouMessages++;
                }
            }
        }

        $pct = function ($value) use ($total) {
            return $total > 0 ? round(($value / $total) * 100) : 0;
        };

        return [
            'total'                    => $total,
            'overdue'                  => $overdue,
            'overdue_pct'              => $pct($overdue),
            'dueSoon'                  => $dueSoon,
            'dueSoon_pct'              => $pct($dueSoon),
            'within'                   => $within,
            'within_pct'               => $pct($within),
            'responded_messages'       => $respondedMessages,
            'pending_messages_for_you' => $pendingForYouMessages,
        ];
    }

    public function getDeadlineSummaryProperty(): array
    {
        $jobs = $this->baseQuery(true)->get();
        $today = now()->startOfDay();

        $dueToday = 0;
        $overdue = 0;
        $finishedPending = 0;

        foreach ($jobs as $job) {
            if ($job->status === ProtestJobStatus::DONE) {
                $finishedPending++;
            }

            $isMeda = mb_strtoupper((string) ($job->medProtest?->statusSist ?? '')) === 'MEDA';
            if (!$isMeda) {
                continue;
            }

            $desiredDate = $this->resolveDesiredDate($job);

            if (!$desiredDate) {
                continue;
            }

            $desired = $desiredDate instanceof Carbon
                ? $desiredDate->copy()->startOfDay()
                : Carbon::parse($desiredDate)->startOfDay();

            if ($desired->equalTo($today)) {
                $dueToday++;
            } elseif ($desired->lessThan($today)) {
                $overdue++;
            }
        }

        return [
            'due_today' => $dueToday,
            'overdue' => $overdue,
            'finished_pending' => $finishedPending,
        ];
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function cleanFilters(): void
    {
        $this->reset([
            'userViewer',
            'searchName',
            'search',
            'typeNote',
            'protestType',
            'slaFilter',
            'jobStatusFilter',
            'priorityFilter',
            'sapStatusFilter',
            'ownerScope',
            'sortBy',
            'sortDirection',
            'deadlineCardFilter',
            'onlySelectedUser',
            'histogramStatusScope',
            'histogramBucket',
            'histogramStackFilter',
        ]);
        $this->loadUserViewerList();
        $this->loadProtestTypeOptions();
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

    public function clearHistogramFilter(): void
    {
        $this->histogramBucket = null;
        $this->histogramStackFilter = null;
        $this->resetPage();
    }

    public function getHistogramDataProperty(): array
    {
        $jobs = $this->baseQuery(ignoreDeadlineFilter: false, ignoreHistogramFilter: true);
        $this->applyHistogramStatusScope($jobs);
        $jobs = $jobs->get();

        $totals = [];
        $overdueByMonth = [];
        $dueSoonByMonth = [];
        $withinByMonth = [];
        $now = now();

        foreach ($jobs as $job) {
            $bucketDate = null;
            $desiredDate = $this->resolveDesiredDate($job);

            if ($this->histogramSource === 'sla') {
                $bucketDate = $job->sla_due_at ?? $desiredDate;
            } else {
                $bucketDate = $desiredDate;
            }

            if (!$bucketDate) {
                continue;
            }

            $monthKey = $bucketDate->format('Y-m');
            $totals[$monthKey] = ($totals[$monthKey] ?? 0) + 1;

            $diff = $now->diffInDays($bucketDate, false);
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
        $monthTotals = [];
        $monthLabels = [];
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

    public function exportToExcel(): void
    {
        $filters = [
            'search' => $this->search,
            'userViewer' => $this->userViewer,
            'onlySelectedUser' => $this->onlySelectedUser,
            'typeNote' => $this->typeNote,
            'protestType' => $this->protestType,
            'slaFilter' => $this->slaFilter,
            'jobStatusFilter' => $this->jobStatusFilter,
            'priorityFilter' => $this->priorityFilter,
            'sapStatusFilter' => $this->sapStatusFilter,
            'ownerScope' => $this->ownerScope,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'showOnlyBtzero' => $this->showOnlyBtzero,
            'hideBtzero' => $this->hideBtzero,
            'deadlineCardFilter' => $this->deadlineCardFilter,
            'histogramBucket' => $this->histogramBucket,
            'histogramStackFilter' => $this->histogramStackFilter,
            'histogramStatusScope' => $this->histogramStatusScope,
        ];

        ExportMonitoringProtestJobsJob::dispatch($filters, (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO INICIADA',
            'text'     => 'A exportação foi iniciada, você receberá uma notificação quando estiver pronta.',
            'timer'    => 5000,
        ]);
    }

    public function render()
    {
        return view('livewire.protests.dispatch.monitoring', [
            'lists'          => $this->lists,
            'coreTotal' => $this->coreTotal,
            'coreDonePending' => $this->coreDonePending,
            'fixedFilters' => $this->fixedFilters,
            'variableFilters' => $this->variableFilters,
            'userViewerList' => $this->userViewerList,
            'noteTypeOptions' => $this->noteTypeOptions,
            'protestTypeOptions' => $this->protestTypeOptions,
            'deadlineSummary' => $this->deadlineSummary,
            'histogramData' => $this->histogramData,
            'jobStatusOptions' => collect(ProtestJobStatus::cases())
                ->map(fn($status) => ['value' => $status->value, 'label' => $status->label()])
                ->values()
                ->all(),
            'priorityOptions' => collect(ProtestJobPriority::cases())
                ->map(fn($priority) => ['value' => $priority->value, 'label' => $priority->label()])
                ->values()
                ->all(),
        ]);
    }

    protected function resolveDesiredDate($job)
    {
        if ($job->protest?->tipoNota === 'NA') {
            return $job->protest?->dtConclusaoDesej;
        }

        return $job->medProtest?->dtFimMedidaDesej;
    }
}
