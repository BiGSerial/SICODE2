<?php

namespace App\Http\Livewire\Protests\Services;

use App\Exports\Protests\OpenProtestJobsExport;
use App\Models\ProtestJob;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Accompany extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    /** Filtros */
    public int $perPage = 50;
    public string $search = '';
    public array $selectedUserId = [];
    public array $selectedCodf = [];
    public bool $onlySelectedUser = false;
    public string $histogramSource = 'desired';
    public ?int $histogramYear = null;
    public ?int $histogramMonth = null;

    protected $queryString = [
        'page'           => ['except' => 1],
        'perPage'        => ['except' => 50],
        'search'         => ['except' => ''],
        'selectedUserId' => ['except' => []],
        'selectedCodf'   => ['except' => []],
        'onlySelectedUser' => ['except' => false],
        'histogramSource' => ['except' => 'desired'],
        'histogramYear' => ['except' => null],
        'histogramMonth' => ['except' => null],
    ];

    public array $codfOptions = [];

    public function mount(): void
    {
        $this->histogramYear = $this->histogramYear ?: (int) now()->year;
        $this->selectedUserId = collect((array) $this->selectedUserId)->filter()->values()->all();
        $this->selectedCodf = collect((array) $this->selectedCodf)->filter()->values()->all();
        $this->loadCodfOptions();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedUserId($value): void
    {
        $this->selectedUserId = collect((array) $value)->filter()->values()->all();
        if (empty($this->selectedUserId)) {
            $this->onlySelectedUser = false;
        }

        $this->resetPage();
    }

    public function updatingOnlySelectedUser(): void
    {
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
        $this->histogramMonth = null;
        $this->resetPage();
    }

    public function updatedHistogramYear(): void
    {
        $this->histogramMonth = null;
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

    /**
     * Usuários sob a hierarquia do usuário logado (closure table user_closure)
     */
    protected function availableUsersQuery()
    {
        $viewer = auth()->user();

        if (!$viewer) {
            return User::query()->whereRaw('1 = 0');
        }

        return $viewer
            ->descendantsQuery(
                includeSelf: true,
                includeDelegations: true,
                includeDelegatesTreesForPrincipal: true
            )
            ->orderBy('users.name')
            ->distinct();
    }

    /**
     * Retorna IDs do usuário selecionado + descendentes diretos/indiretos.
     */
    protected function descendantsOf(string $userId): array
    {
        $user = User::find($userId);

        if (!$user) {
            return [];
        }

        return $user
            ->descendantsQuery(
                includeSelf: true,
                includeDelegations: true,
                includeDelegatesTreesForPrincipal: true
            )
            ->pluck('users.id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Accessor Livewire: $this->availableUsers
     */
    public function getAvailableUsersProperty()
    {
        return $this->availableUsersQuery()->get();
    }

    /**
     * Query base: jobs em aberto da equipe (subordinados + opcionalmente o próprio)
     */
    protected function baseQuery(bool $ignoreHistogram = false)
    {
        // IDs da galera sob a hierarquia + opcionalmente o próprio gestor
        $subordinatesIds = $this->availableUsers
            ->pluck('id')
            ->push(auth()->id())
            ->unique()
            ->values()
            ->all();

        return ProtestJob::query()
            ->where(function ($q) {
                $q->whereRelation('medProtest', 'statusSist', 'MEDA')
                  ->orWhere(fn ($qq) => $qq->open());
            })
            ->where(function ($q) {
                $q->whereNull('confirmed')
                    ->orWhere('confirmed', false);
            })
            ->whereIn('owner_id', $subordinatesIds)
            ->with([
                'protest.Notes',
                'medProtest' => function ($q) {
                    $q->with([
                        'Notes',
                        'Comments' => function ($cq) {
                            $cq->latest();
                        },
                    ]);
                },
                'Comments' => function ($q) {
                    $q->latest();
                },
                'creator:id,name',
                'owner:id,name,email',
            ])
            ->when(!empty($this->selectedUserId), function ($q) {
                $teamIds = [];
                foreach ($this->selectedUserId as $selectedUserId) {
                    $teamIds = array_merge(
                        $teamIds,
                        $this->onlySelectedUser ? [$selectedUserId] : $this->descendantsOf($selectedUserId)
                    );
                }
                $teamIds = array_values(array_unique($teamIds));

                $q->whereIn('owner_id', $teamIds);
            })
            ->when(!empty($this->selectedCodf), function ($q) {
                $q->whereHas('protest', function ($sub) {
                    $sub->whereIn('codecodf', $this->selectedCodf);
                });
            })
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';

                $q->where(function ($qq) use ($term) {
                    $qq->where('id', 'like', $term)
                        ->orWhere('notes', 'like', $term)
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
            })
            ->when(!$ignoreHistogram && $this->histogramMonth && $this->histogramYear, function ($q) {
                if ($this->histogramSource === 'sla') {
                    $q->whereYear('sla_due_at', (int) $this->histogramYear)
                        ->whereMonth('sla_due_at', (int) $this->histogramMonth);
                } else {
                    $q->where(function ($dateScope) {
                        $dateScope
                            ->whereHas('protest', function ($na) {
                                $na->where('tipoNota', 'NA')
                                    ->whereYear('dtConclusaoDesej', (int) $this->histogramYear)
                                    ->whereMonth('dtConclusaoDesej', (int) $this->histogramMonth);
                            })
                            ->orWhereHas('medProtest', function ($med) {
                                $med->where(function ($noteType) {
                                    $noteType->whereHas('protest', function ($p) {
                                        $p->where('tipoNota', '!=', 'NA')->orWhereNull('tipoNota');
                                    });
                                })->whereYear('dtFimMedidaDesej', (int) $this->histogramYear)
                                  ->whereMonth('dtFimMedidaDesej', (int) $this->histogramMonth);
                            });
                    });
                }
            })
            ->orderByDesc('priority')
            ->orderBy('sla_due_at')
            ->orderByDesc('sent_at');
    }

    /** Lista paginada */
    public function getListProperty()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'selectedUserId', 'selectedCodf', 'perPage', 'onlySelectedUser']);
        $this->resetPage();
    }

    protected function loadCodfOptions(): void
    {
        $subordinatesIds = $this->availableUsers
            ->pluck('id')
            ->push(auth()->id())
            ->unique()
            ->values()
            ->all();

        $this->codfOptions = ProtestJob::query()
            ->where(function ($q) {
                $q->whereRelation('medProtest', 'statusSist', 'MEDA')
                    ->orWhere(fn ($qq) => $qq->open());
            })
            ->whereIn('owner_id', $subordinatesIds)
            ->whereHas('protest', function ($q) {
                $q->whereNotNull('codecodf')
                    ->where('codecodf', '!=', '');
            })
            ->with('protest:id,codecodf')
            ->get()
            ->pluck('protest.codecodf')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function exportToExcel()
    {
        $file = 'protestos_servicos_acompanhamento_' . now()->format('YmdHis') . '.xlsx';

        return Excel::download(
            new OpenProtestJobsExport(clone $this->baseQuery(), includeOwner: true),
            $file
        );
    }

    public function getHistogramDataProperty(): array
    {
        $jobs = (clone $this->baseQuery(ignoreHistogram: true))->get();
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
                $bucketDate = $job->sla_due_at;
            } else {
                $bucketDate = $job->protest?->tipoNota === 'NA'
                    ? $job->protest?->dtConclusaoDesej
                    : $job->medProtest?->dtFimMedidaDesej;
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
        return view('livewire.protests.services.accompany', [
            'list'           => $this->list,
            'availableUsers' => $this->availableUsers,
            'codfOptions'    => $this->codfOptions,
            'histogramData' => $this->histogramData,
        ]);
    }
}
