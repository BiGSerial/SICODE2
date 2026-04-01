<?php

namespace App\Http\Livewire\Protests\Services;

use App\Models\ProtestJob;
use App\Models\User;
use App\Traits\WildcardFormmater;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class History extends Component
{
    use WildcardFormmater;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public int $perPage = 50;
    public string $search = '';
    public ?string $dt_start = null;
    public ?string $dt_end = null;
    public ?string $month = null;
    public string $histogramSource = 'measure';
    public ?int $histogramYear = null;
    public ?int $histogramMonth = null;

    protected ?Collection $authorizedUserIds = null;

    protected $listeners = [
        'refreshComponent' => '$refresh',
    ];

    protected $queryString = [
        'perPage' => ['as' => 'pagina'],
        'histogramSource' => ['except' => 'measure', 'as' => 'hsrc'],
        'histogramYear' => ['except' => null, 'as' => 'hyear'],
        'histogramMonth' => ['except' => null, 'as' => 'hmon'],
    ];

    public function mount(): void
    {
        $this->histogramYear = $this->histogramYear ?: (int) now()->year;
    }

    public function updated($propertyName): void
    {
        if (in_array($propertyName, ['perPage', 'search', 'dt_start', 'dt_end', 'month'], true)) {
            $this->resetPage();
        }
    }

    protected function baseQuery(bool $ignoreHistogram = false): Builder
    {
        $userIds = $this->authorizedUserIds();

        if ($userIds->isEmpty()) {
            return ProtestJob::query()->whereRaw('1 = 0');
        }

        $query = ProtestJob::query()
            ->with([
                'owner:id,name',
                'creator:id,name',
                'medProtest' => function ($q) {
                    $q->with([
                        'protest.notes',
                        'notes',
                    ]);
                },
            ])
            ->whereIn('owner_id', $userIds)
            ->whereNotNull('closed_at')
            ->whereHas('medProtest', function ($q) {
                $q->where('statusSist', 'MEDE');
            });

        $query->when($this->search, function ($q) {
            $term = $this->formatWithWildcard($this->search);

            $q->where(function ($sub) use ($term) {
                $sub->whereHas('medProtest.protest', function ($inner) use ($term) {
                    $inner->where('nota', $term->type, $term->search)
                        ->orWhere('txtGrpCodificacao', $term->type, $term->search);
                })
                ->orWhereHas('medProtest.protest.notes', function ($inner) use ($term) {
                    $inner->where('note', $term->type, $term->search)
                        ->orWhere('material', $term->type, $term->search);
                })
                ->orWhereHas('medProtest.notes', function ($inner) use ($term) {
                    $inner->where('note', $term->type, $term->search)
                        ->orWhere('material', $term->type, $term->search);
                });
            });
        });

        $query->when($this->dt_start, function ($q) {
            $q->whereDate('closed_at', '>=', $this->dt_start);
        });

        $query->when($this->dt_end, function ($q) {
            $q->whereDate('closed_at', '<=', $this->dt_end);
        });

        $query->when($this->month, function ($q) {
            try {
                $target = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
            } catch (\Throwable $th) {
                return;
            }

            $q->whereYear('closed_at', $target->year)
                ->whereMonth('closed_at', $target->month);
        });

        if (!$ignoreHistogram && $this->histogramMonth && $this->histogramYear) {
            if ($this->histogramSource === 'sla') {
                $query->whereYear('sla_due_at', (int) $this->histogramYear)
                    ->whereMonth('sla_due_at', (int) $this->histogramMonth);
            } else {
                $query->where(function ($q) {
                    $q->whereHas('medProtest.protest', function ($p) {
                        $p->where('tipoNota', 'NA')
                            ->whereYear('dtConclusaoDesej', (int) $this->histogramYear)
                            ->whereMonth('dtConclusaoDesej', (int) $this->histogramMonth);
                    })->orWhereHas('medProtest', function ($m) {
                        $m->whereYear('dtFimMedidaDesej', (int) $this->histogramYear)
                            ->whereMonth('dtFimMedidaDesej', (int) $this->histogramMonth)
                            ->whereHas('protest', function ($p) {
                                $p->where(function ($noteType) {
                                    $noteType->where('tipoNota', '!=', 'NA')->orWhereNull('tipoNota');
                                });
                            });
                    });
                });
            }
        }

        return $query->orderByDesc('closed_at');
    }

    public function getListProperty(): LengthAwarePaginator
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'dt_start', 'dt_end', 'month', 'histogramMonth']);
        $this->resetPage();
    }

    public function updatedHistogramSource($value): void
    {
        if (!in_array($value, ['measure', 'sla'], true)) {
            $this->histogramSource = 'measure';
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

    protected function authorizedUserIds(): Collection
    {
        if ($this->authorizedUserIds instanceof Collection) {
            return $this->authorizedUserIds;
        }

        /** @var User|null $viewer */
        $viewer = auth()->user();

        if (!$viewer) {
            return $this->authorizedUserIds = collect();
        }

        $ids = $viewer->descendantsQuery(
            includeSelf: true,
            includeDelegations: false,
            includeDelegatesTreesForPrincipal: false
        )
        ->pluck('users.id')
        ->push($viewer->id)
        ->unique()
        ->values();

        return $this->authorizedUserIds = $ids;
    }

    public function deadlineFor(ProtestJob $job): ?Carbon
    {
        $medProtest = $job->medProtest;
        $protest = $medProtest?->protest;

        if (!$medProtest || !$protest) {
            return null;
        }

        if (($protest->tipoNota ?? null) === 'NA') {
            return $protest->dtConclusaoDesej;
        }

        return $medProtest->dtFimMedidaDesej;
    }

    public function measureFinishedWithinDeadline(ProtestJob $job): ?bool
    {
        $deadline = $this->deadlineFor($job);
        $finishedAt = $job->medProtest?->dtFimMedida;

        if (!$deadline || !$finishedAt) {
            return null;
        }

        return $finishedAt->lessThanOrEqualTo($deadline);
    }

    public function jobFinishedWithinSla(ProtestJob $job): ?bool
    {
        $deadline = $job->sla_due_at;
        $finishedAt = $job->finished_at ?? $job->closed_at;

        if (!$deadline || !$finishedAt) {
            return null;
        }

        return $finishedAt->lessThanOrEqualTo($deadline);
    }

    public function getHistogramDataProperty(): array
    {
        $jobs = (clone $this->baseQuery(ignoreHistogram: true))->get();
        $monthNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $yearsMap = [];
        $onTimeByMonth = [];
        $lateByMonth = [];

        foreach ($jobs as $job) {
            if ($this->histogramSource === 'sla') {
                $bucketDate = $job->sla_due_at;
                $isOnTime = $this->jobFinishedWithinSla($job);
            } else {
                $bucketDate = $this->deadlineFor($job);
                $isOnTime = $this->measureFinishedWithinDeadline($job);
            }

            if (!$bucketDate || is_null($isOnTime)) {
                continue;
            }

            $year = (int) $bucketDate->format('Y');
            $month = (int) $bucketDate->format('n');
            $yearsMap[$year] = true;

            if ($isOnTime) {
                $onTimeByMonth[$year][$month] = ($onTimeByMonth[$year][$month] ?? 0) + 1;
            } else {
                $lateByMonth[$year][$month] = ($lateByMonth[$year][$month] ?? 0) + 1;
            }
        }

        $years = array_keys($yearsMap);
        rsort($years);
        $selectedYear = (int) ($this->histogramYear ?: now()->year);
        if (!empty($years) && !in_array($selectedYear, $years, true)) {
            $selectedYear = (int) $years[0];
            $this->histogramYear = $selectedYear;
        }

        $onTimeCounts = [];
        $lateCounts = [];
        for ($m = 1; $m <= 12; $m++) {
            $onTimeCounts[] = (int) ($onTimeByMonth[$selectedYear][$m] ?? 0);
            $lateCounts[] = (int) ($lateByMonth[$selectedYear][$m] ?? 0);
        }

        return [
            'labels' => $monthNames,
            'series' => [
                'onTime' => $onTimeCounts,
                'late' => $lateCounts,
            ],
            'years' => $years,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $this->histogramMonth,
            'source' => $this->histogramSource,
        ];
    }

    public function render()
    {
        return view('livewire.protests.services.history', [
            'list' => $this->list,
            'histogramData' => $this->histogramData,
        ]);
    }
}
