<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Enum\ProtestJobStatus;
use App\Enum\ProtestJobPriority;
use App\Jobs\Protests\ExportMonitoringProtestJobsJob;
use App\Models\Protest;
use App\Models\ProtestJob;
use App\Models\User;
use Illuminate\Support\Carbon;
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
    public $userViewer        = null;
    public bool $onlySelectedUser = false;

    /** Filtro por tipo de nota (NA / OU / PR) */
    public ?string $typeNote  = null;
    public ?string $protestType = null;

    /** Filtro por SLA (overdue / dueSoon / within) */
    public ?string $slaFilter = null;
    public ?string $jobStatusFilter = null;
    public ?string $priorityFilter = null;
    public ?string $sapStatusFilter = null;
    public ?string $ownerScope = null; // assigned | unassigned
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
    public ?int $histogramYear = null;
    public ?int $histogramMonth = null;

    protected $queryString = [
        'perPage'    => ['except' => 50],
        'search'     => ['except' => ''],
        'userViewer' => ['except' => null],
        'onlySelectedUser' => ['except' => false],
        'typeNote'   => ['except' => null],
        'protestType' => ['except' => null],
        'slaFilter'  => ['except' => null],
        'jobStatusFilter' => ['except' => null],
        'priorityFilter' => ['except' => null],
        'sapStatusFilter' => ['except' => null],
        'ownerScope' => ['except' => null],
        'sortBy' => ['except' => 'sla_due_at'],
        'sortDirection' => ['except' => 'asc'],
        'deadlineCardFilter' => ['except' => null],
        'histogramSource' => ['except' => 'desired'],
        'histogramYear' => ['except' => null],
        'histogramMonth' => ['except' => null],
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

        $this->histogramYear = $this->histogramYear ?: (int) now()->year;

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
        $this->typeNote = $value ?: null;
        $this->resetPage();
    }

    public function updatedProtestType($value): void
    {
        $this->protestType = $value ?: null;
        $this->resetPage();
    }

    public function updatedUserViewer($value): void
    {
        if (empty($value)) {
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
        $this->jobStatusFilter = $value ?: null;
        $this->resetPage();
    }

    public function updatedPriorityFilter($value): void
    {
        $this->priorityFilter = $value ?: null;
        $this->resetPage();
    }

    public function updatedSapStatusFilter($value): void
    {
        $this->sapStatusFilter = $value ?: null;
        $this->resetPage();
    }

    public function updatedOwnerScope($value): void
    {
        $this->ownerScope = $value ?: null;
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

        $this->histogramMonth = null;
        $this->resetPage();
    }

    public function updatedHistogramYear(): void
    {
        $this->histogramMonth = null;
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

    public function goTo($protestNote)
    {
        return redirect()->route('protests.dispatch.view', [
            'protest' => $protestNote,
        ]);
    }

    /** Ajusta o filtro por tipo de nota */
    public function setTypeNote(?string $type = null): void
    {
        $this->typeNote = $type ?: null;
        $this->resetPage();
    }

    /** Clicar no card de SLA (total/overdue/dueSoon/within) */
    public function setSlaFilter(?string $mode = null): void
    {
        $this->slaFilter = $mode ?: null;
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

    /** Query base dos jobs */
    protected function baseQuery(bool $ignoreDeadlineFilter = false, bool $ignoreHistogramFilter = false)
    {
        $query = ProtestJob::query()
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
            ->where('confirmed', '!=', true)
            ->orderBy('id');

        if ($this->showOnlyBtzero) {
            $query->whereHas('medProtest', function ($q) {
                $q->identifiedAsBtzero();
            });
        } elseif ($this->hideBtzero) {
            $query->where(function ($sub) {
                $sub->whereNull('med_protest_id')
                    ->orWhereHas('medProtest', function ($q) {
                        $q->notIdentifiedAsBtzero();
                    });
            });
        }

        // Filtro por responsável / hierarquia
        $query->when($this->userViewer, function ($q) {
            $user = User::find($this->userViewer);

            if (!$user) {
                return;
            }

            $ownerIds = $this->onlySelectedUser
                ? [$user->id]
                : $user->descendantsQuery(true, true, true)->pluck('users.id')->toArray();

            $onlySelectedUser = $this->onlySelectedUser;

            $q->where(function ($qq) use ($ownerIds, $onlySelectedUser) {
                $qq->whereIn('owner_id', $ownerIds);

                if (!$onlySelectedUser) {
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
        $query->when($this->typeNote, function ($q) {
            $type = $this->typeNote;

            $q->whereHas('protest', function ($sub) use ($type) {
                $sub->where('tipoNota', $type);
            });
        });

        $query->when($this->protestType, function ($q) {
            $type = $this->protestType;

            $q->whereHas('protest', function ($sub) use ($type) {
                $sub->where('type', $type);
            });
        });

        // Status do job
        $query->when($this->jobStatusFilter, function ($q) {
            $q->where('status', $this->jobStatusFilter);
        });

        // Prioridade
        $query->when($this->priorityFilter, function ($q) {
            $q->where('priority', $this->priorityFilter);
        });

        // Status SAP/Medida
        $query->when($this->sapStatusFilter, function ($q) {
            $sap = mb_strtoupper((string) $this->sapStatusFilter);
            $q->whereHas('medProtest', function ($sub) use ($sap) {
                $sub->where('statusSist', $sap);
            });
        });

        // Escopo de responsável
        $query->when($this->ownerScope, function ($q) {
            if ($this->ownerScope === 'assigned') {
                $q->whereNotNull('owner_id');
            } elseif ($this->ownerScope === 'unassigned') {
                $q->whereNull('owner_id');
            }
        });

        // Filtro por SLA
        $query->when($this->slaFilter, function ($q) {
            $now = now();

            $q->whereNotNull('sla_due_at');

            if ($this->slaFilter === 'overdue') {
                $q->where('sla_due_at', '<', $now);
            } elseif ($this->slaFilter === 'dueSoon') {
                $q->whereBetween('sla_due_at', [$now, $now->clone()->addDays(3)]);
            } elseif ($this->slaFilter === 'within') {
                $q->where('sla_due_at', '>', $now->clone()->addDays(3));
            }
        });

        if (!$ignoreDeadlineFilter && $this->deadlineCardFilter) {
            $today = now()->toDateString();

            if ($this->deadlineCardFilter === 'due_today') {
                $query->where(function ($q) use ($today) {
                    $q->whereHas('protest', function ($sub) use ($today) {
                        $sub->where('tipoNota', 'NA')
                            ->whereDate('dtConclusaoDesej', $today);
                    })->orWhereHas('medProtest', function ($sub) use ($today) {
                        $sub->whereDate('dtFimMedidaDesej', $today);
                    });
                });
            } elseif ($this->deadlineCardFilter === 'overdue') {
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

        if (!$ignoreHistogramFilter && $this->histogramMonth && $this->histogramYear) {
            if ($this->histogramSource === 'sla') {
                $query->whereNull('finished_at')
                    ->whereYear('sla_due_at', (int) $this->histogramYear)
                    ->whereMonth('sla_due_at', (int) $this->histogramMonth);
            } else {
                $query->whereHas('medProtest', function ($sub) {
                    $sub->where('statusSist', 'MEDA')
                        ->whereYear('dtFimMedidaDesej', (int) $this->histogramYear)
                        ->whereMonth('dtFimMedidaDesej', (int) $this->histogramMonth);
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
            'histogramMonth',
        ]);
        $this->loadUserViewerList();
        $this->loadProtestTypeOptions();
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

    public function getHistogramDataProperty(): array
    {
        $histogramQuery = ProtestJob::query()
            ->with(['medProtest', 'protest'])
            ->where('confirmed', '!=', true);

        if ($this->showOnlyBtzero) {
            $histogramQuery->whereHas('medProtest', function ($q) {
                $q->identifiedAsBtzero();
            });
        } elseif ($this->hideBtzero) {
            $histogramQuery->where(function ($sub) {
                $sub->whereNull('med_protest_id')
                    ->orWhereHas('medProtest', function ($q) {
                        $q->notIdentifiedAsBtzero();
                    });
            });
        }

        $jobs = $histogramQuery->get();
        $monthNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        $totals = [];
        $overdueByMonth = [];
        $dueSoonByMonth = [];
        $withinByMonth = [];
        $yearsMap = [];
        $now = now();

        foreach ($jobs as $job) {
            $bucketDate = null;

            if ($this->histogramSource === 'sla') {
                if (!$job->finished_at && $job->sla_due_at) {
                    $bucketDate = $job->sla_due_at;
                }
            } else {
                if (mb_strtoupper((string) ($job->medProtest?->statusSist ?? '')) === 'MEDA') {
                    $bucketDate = $job->medProtest?->dtFimMedidaDesej;
                }
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
