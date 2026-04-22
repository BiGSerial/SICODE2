<?php

namespace App\Http\Livewire\Reports;

use App\Enum\AdsRequestStatus;
use App\Models\Company;
use App\Services\Reports\AdsRequestedReportService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class AdsRequestedReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public int $perPage = 50;
    public string $statusFilter = 'all';
    public ?string $statusExact = null;
    public ?string $date_in = null;
    public ?string $date_out = null;
    public ?string $completed_in = null;
    public ?string $completed_out = null;
    public ?string $search = null;
    public array $companyIds = [];
    public $companies;
    public array $statusExactOptions = [];
    public string $chartPeriod = '7d'; // 7d | 30d | 12m | custom
    public string $chartGranularity = 'day'; // day | month
    private bool $syncingChartPeriod = false;

    protected $listeners = [
        'adsChartFilterByDay' => 'applyChartDayFilter',
        'adsChartFilterByQueueStatus' => 'applyChartQueueStatusFilter',
    ];

    protected $queryString = [
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
        'statusExact' => ['except' => '', 'as' => 'sx'],
        'date_in' => ['except' => '', 'as' => 'din'],
        'date_out' => ['except' => '', 'as' => 'dout'],
        'completed_in' => ['except' => '', 'as' => 'cin'],
        'completed_out' => ['except' => '', 'as' => 'cout'],
        'search' => ['except' => '', 'as' => 'q'],
        'companyIds' => ['except' => [], 'as' => 'company'],
        'perPage' => ['except' => 50, 'as' => 'pp'],
        'chartPeriod' => ['except' => '7d', 'as' => 'cp'],
        'chartGranularity' => ['except' => 'day', 'as' => 'cg'],
    ];

    public function mount(): void
    {
        if (!$this->isValidChartPeriod($this->chartPeriod)) {
            $this->chartPeriod = '7d';
        }

        if (!$this->isValidChartGranularity($this->chartGranularity)) {
            $this->chartGranularity = 'day';
        }

        $activeDateType = $this->resolveActiveDateType();
        if ($activeDateType === 'completed') {
            $this->date_in = null;
            $this->date_out = null;
        } else {
            $this->completed_in = null;
            $this->completed_out = null;
        }

        if ($this->chartPeriod === 'custom') {
            $this->ensureCustomHasAnchorDate($activeDateType);
            $this->syncGranularityFromDateRange();
        } else {
            $this->applyChartPeriod($this->chartPeriod, $activeDateType);
        }

        $this->companies = Company::query()
            ->join('ads_requests as ar', 'ar.company_id', '=', 'companies.id')
            ->select('companies.id', 'companies.name')
            ->distinct()
            ->orderBy('companies.name')
            ->get();

        $this->statusExactOptions = array_map(function (AdsRequestStatus $status) {
            $label = $status->label();
            if ($status === AdsRequestStatus::IN_PROGRESS) {
                $label = 'Em execução';
            }

            return [
                'value' => $status->value,
                'label' => $label,
            ];
        }, AdsRequestStatus::cases());

        $this->dispatchFiltersToCharts();
    }

    public function updating($name): void
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    public function updated($name): void
    {
        if ($name !== 'page') {
            $this->dispatchFiltersToCharts();
        }
    }

    public function clearFilters(): void
    {
        $this->statusFilter = 'all';
        $this->statusExact = null;
        $this->search = null;
        $this->companyIds = [];
        $this->chartPeriod = '7d';
        $this->applyChartPeriod('7d');
        $this->completed_in = null;
        $this->completed_out = null;
        $this->perPage = 50;
        $this->resetPage();
        $this->dispatchFiltersToCharts();
    }

    public function applyChartDayFilter(string $date): void
    {
        $this->completed_in = null;
        $this->completed_out = null;

        if (preg_match('/^\d{4}\-\d{2}$/', $date) === 1) {
            $monthStart = Carbon::createFromFormat('Y-m', $date)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $today = now()->endOfDay();
            if ($monthEnd->gt($today)) {
                $monthEnd = $today;
            }

            $this->date_in = $monthStart->toDateString();
            $this->date_out = $monthEnd->toDateString();
            $this->chartPeriod = 'custom';
            $this->chartGranularity = 'day';
            $this->resetPage();
            $this->dispatchFiltersToCharts();
            return;
        }

        if (!$this->isValidDate($date)) {
            return;
        }

        $this->date_in = $date;
        $this->date_out = $date;
        $this->chartPeriod = 'custom';
        $this->chartGranularity = 'day';
        $this->resetPage();
        $this->dispatchFiltersToCharts();
    }

    public function applyChartQueueStatusFilter(string $status): void
    {
        $status = trim($status);
        if ($status === '') {
            return;
        }

        $this->statusExact = $status;
        $this->resetPage();
        $this->dispatchFiltersToCharts();
    }

    public function updatedChartPeriod(string $value): void
    {
        if (!$this->isValidChartPeriod($value)) {
            $this->chartPeriod = '7d';
            $value = '7d';
        }

        $activeDateType = $this->resolveActiveDateType();

        if ($value === 'custom') {
            $this->ensureCustomHasAnchorDate($activeDateType);
            $this->syncGranularityFromDateRange();
            $this->dispatchFiltersToCharts();
            return;
        }

        $this->applyChartPeriod($value, $activeDateType);
        $this->dispatchFiltersToCharts();
    }

    public function updatedDateIn(): void
    {
        $this->completed_in = null;
        $this->completed_out = null;

        if ($this->chartPeriod !== 'custom') {
            if (blank($this->date_out) && filled($this->date_in)) {
                $this->date_out = $this->date_in;
            }
            $this->applyChartPeriod($this->chartPeriod, 'request');
            $this->dispatchFiltersToCharts();
            return;
        }

        $this->markCustomPeriod();
        $this->dispatchFiltersToCharts();
    }

    public function updatedDateOut(): void
    {
        $this->completed_in = null;
        $this->completed_out = null;

        if ($this->chartPeriod !== 'custom') {
            $this->applyChartPeriod($this->chartPeriod, 'request');
            $this->dispatchFiltersToCharts();
            return;
        }

        $this->markCustomPeriod();
        $this->dispatchFiltersToCharts();
    }

    public function updatedCompletedIn(): void
    {
        $this->date_in = null;
        $this->date_out = null;

        if ($this->chartPeriod !== 'custom') {
            if (blank($this->completed_out) && filled($this->completed_in)) {
                $this->completed_out = $this->completed_in;
            }
            $this->applyChartPeriod($this->chartPeriod, 'completed');
            $this->dispatchFiltersToCharts();
            return;
        }

        $this->markCustomPeriod();
        $this->dispatchFiltersToCharts();
    }

    public function updatedCompletedOut(): void
    {
        $this->date_in = null;
        $this->date_out = null;

        if ($this->chartPeriod !== 'custom') {
            $this->applyChartPeriod($this->chartPeriod, 'completed');
            $this->dispatchFiltersToCharts();
            return;
        }

        $this->markCustomPeriod();
        $this->dispatchFiltersToCharts();
    }

    public function getRowsProperty()
    {
        return app(AdsRequestedReportService::class)
            ->paginate($this->filters(), $this->perPage);
    }

    public function getQueueRowsProperty()
    {
        return app(AdsRequestedReportService::class)
            ->paginateQueue($this->filters(), 20, 'queue_page');
    }

    public function getFiltersForChildrenProperty(): array
    {
        return $this->filters();
    }

    public function syncLast40Days(): void
    {
        $since = now()->subDays(40)->startOfDay()->toDateTimeString();

        try {
            Artisan::call('sicode:sync_ads_requests', [
                '--since' => $since,
            ]);

            $output = trim((string) Artisan::output());
            $message = $output !== ''
                ? 'Sincronização concluída. ' . mb_substr($output, 0, 240)
                : 'Sincronização concluída com sucesso.';

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'success',
                'title' => 'SYNC ADS CONCLUÍDO',
                'text' => $message,
                'timer' => 5000,
            ]);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'SYNC ADS FALHOU',
                'text' => mb_substr($e->getMessage(), 0, 280),
                'timer' => 7000,
            ]);
        }

        $this->resetPage();
    }

    private function filters(): array
    {
        $isCustom = $this->chartPeriod === 'custom';
        $effectiveGranularity = $this->chartPeriod === '12m' ? 'month' : 'day';
        $activeDateType = $this->resolveActiveDateType();
        $dateIn = null;
        $dateOut = null;
        $completedIn = null;
        $completedOut = null;

        if ($activeDateType === 'completed') {
            $anchor = $this->resolveAnchorDate('completed');
            $normalizedIn = $this->normalizeDateOrNull($this->completed_in);
            $normalizedOut = $this->normalizeDateOrNull($this->completed_out);
            if ($isCustom) {
                $completedIn = $normalizedIn ?? $anchor->toDateString();
                $completedOut = $normalizedOut ?? $anchor->toDateString();
            } else {
                if ($normalizedIn && $normalizedOut) {
                    $completedIn = $normalizedIn;
                    $completedOut = $normalizedOut;
                } else {
                    [$start, $end] = $this->resolvePeriodRange($this->chartPeriod, $anchor);
                    $completedIn = $start;
                    $completedOut = $end;
                }
            }
        } else {
            $anchor = $this->resolveAnchorDate('request');
            $normalizedIn = $this->normalizeDateOrNull($this->date_in);
            $normalizedOut = $this->normalizeDateOrNull($this->date_out);
            if ($isCustom) {
                $dateIn = $normalizedIn ?? $anchor->toDateString();
                $dateOut = $normalizedOut ?? $anchor->toDateString();
            } else {
                if ($normalizedIn && $normalizedOut) {
                    $dateIn = $normalizedIn;
                    $dateOut = $normalizedOut;
                } else {
                    [$start, $end] = $this->resolvePeriodRange($this->chartPeriod, $anchor);
                    $dateIn = $start;
                    $dateOut = $end;
                }
            }
        }

        return [
            'statusFilter' => $this->statusFilter,
            'status_exact' => $this->statusExact,
            'date_in' => $dateIn,
            'date_out' => $dateOut,
            'completed_in' => $completedIn,
            'completed_out' => $completedOut,
            'search' => $this->search,
            'companyIds' => $this->companyIds,
            'chart_period' => $this->chartPeriod,
            'chart_granularity' => $effectiveGranularity,
        ];
    }

    public function render()
    {
        $rows = $this->rows;
        $queueRows = $this->queueRows;
        $summary = app(AdsRequestedReportService::class)->summarize($this->filters());

        return view('livewire.reports.ads-requested-report', [
            'rows' => $rows,
            'queueRows' => $queueRows,
            'summary' => $summary,
        ]);
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));

        return checkdate($month, $day, $year);
    }

    private function dispatchFiltersToCharts(): void
    {
        $this->dispatchBrowserEvent('ads-filters-updated', $this->filters());
    }

    private function applyChartPeriod(string $period, string $activeDateType = 'request'): void
    {
        $this->syncingChartPeriod = true;
        $anchor = $this->resolveAnchorDate($activeDateType);
        [$start, $end, $granularity] = $this->resolvePeriodRange($period, $anchor, true);

        if ($activeDateType === 'completed') {
            $this->date_in = null;
            $this->date_out = null;
            $this->completed_in = $start;
            $this->completed_out = $end;
        } else {
            $this->completed_in = null;
            $this->completed_out = null;
            $this->date_in = $start;
            $this->date_out = $end;
        }

        $this->chartGranularity = $granularity;
        $this->syncingChartPeriod = false;
    }

    private function markCustomPeriod(): void
    {
        if ($this->syncingChartPeriod) {
            return;
        }

        $this->chartPeriod = 'custom';
        $this->syncGranularityFromDateRange();
    }

    private function syncGranularityFromDateRange(): void
    {
        $this->chartGranularity = $this->chartPeriod === '12m' ? 'month' : 'day';
    }

    private function resolveActiveDateType(): string
    {
        $hasRequestDates = filled($this->date_in) || filled($this->date_out);
        $hasCompletedDates = filled($this->completed_in) || filled($this->completed_out);

        if ($hasCompletedDates && !$hasRequestDates) {
            return 'completed';
        }

        if ($hasRequestDates && !$hasCompletedDates) {
            return 'request';
        }

        if ($hasCompletedDates && $hasRequestDates) {
            if (filled($this->completed_out) && blank($this->date_out)) {
                return 'completed';
            }

            return 'request';
        }

        return 'request';
    }

    private function ensureCustomHasAnchorDate(string $activeDateType): void
    {
        $today = now()->toDateString();
        if ($activeDateType === 'completed') {
            if (blank($this->completed_in) && blank($this->completed_out)) {
                $this->completed_in = $today;
                $this->completed_out = $today;
            }
            $this->date_in = null;
            $this->date_out = null;
            return;
        }

        if (blank($this->date_in) && blank($this->date_out)) {
            $this->date_in = $today;
            $this->date_out = $today;
        }
        $this->completed_in = null;
        $this->completed_out = null;
    }

    private function resolveAnchorDate(string $activeDateType): Carbon
    {
        $candidate = $activeDateType === 'completed'
            ? $this->normalizeDateOrNull($this->completed_out)
            : $this->normalizeDateOrNull($this->date_out);

        return $candidate ? Carbon::parse($candidate)->startOfDay() : now()->startOfDay();
    }

    /**
     * @return array{0:string,1:string}|array{0:string,1:string,2:string}
     */
    private function resolvePeriodRange(string $period, Carbon $anchor, bool $withGranularity = false): array
    {
        if ($period === '7d') {
            $start = $anchor->copy()->subDays(6)->toDateString();
            $end = $anchor->toDateString();
            return $withGranularity ? [$start, $end, 'day'] : [$start, $end];
        }

        if ($period === '12m') {
            $start = $anchor->copy()->subMonthsNoOverflow(11)->startOfMonth()->toDateString();
            $end = $anchor->toDateString();
            return $withGranularity ? [$start, $end, 'month'] : [$start, $end];
        }

        $start = $anchor->copy()->subDays(29)->toDateString();
        $end = $anchor->toDateString();
        return $withGranularity ? [$start, $end, 'day'] : [$start, $end];
    }

    private function normalizeDateOrNull(?string $date): ?string
    {
        $value = trim((string) $date);
        if ($value === '') {
            return null;
        }

        return $this->isValidDate($value) ? $value : null;
    }

    private function isValidChartPeriod(string $period): bool
    {
        return in_array($period, ['7d', '30d', '12m', 'custom'], true);
    }

    private function isValidChartGranularity(string $granularity): bool
    {
        return in_array($granularity, ['day', 'month'], true);
    }
}
