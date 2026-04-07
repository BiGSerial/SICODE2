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
    public string $chartPeriod = '30d'; // 7d | 30d | 12m | custom
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
        'chartPeriod' => ['except' => '30d', 'as' => 'cp'],
        'chartGranularity' => ['except' => 'day', 'as' => 'cg'],
    ];

    public function mount(): void
    {
        if (!$this->isValidChartPeriod($this->chartPeriod)) {
            $this->chartPeriod = '30d';
        }

        if (!$this->isValidChartGranularity($this->chartGranularity)) {
            $this->chartGranularity = 'day';
        }

        if (blank($this->date_in) || blank($this->date_out)) {
            $this->applyChartPeriod($this->chartPeriod);
        } else {
            $this->syncGranularityFromDateRange();
            $this->chartPeriod = 'custom';
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
        $this->chartPeriod = '30d';
        $this->applyChartPeriod('30d');
        $this->completed_in = null;
        $this->completed_out = null;
        $this->perPage = 50;
        $this->resetPage();
        $this->dispatchFiltersToCharts();
    }

    public function applyChartDayFilter(string $date): void
    {
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
            $this->chartGranularity = 'month';
            $this->resetPage();
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
    }

    public function applyChartQueueStatusFilter(string $status): void
    {
        $status = trim($status);
        if ($status === '') {
            return;
        }

        $this->statusExact = $status;
        $this->resetPage();
    }

    public function updatedChartPeriod(string $value): void
    {
        if (!$this->isValidChartPeriod($value)) {
            $this->chartPeriod = '30d';
            $value = '30d';
        }

        if ($value === 'custom') {
            $this->syncGranularityFromDateRange();
            return;
        }

        $this->applyChartPeriod($value);
    }

    public function updatedDateIn(): void
    {
        $this->markCustomPeriod();
    }

    public function updatedDateOut(): void
    {
        $this->markCustomPeriod();
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
        return [
            'statusFilter' => $this->statusFilter,
            'status_exact' => $this->statusExact,
            'date_in' => $this->date_in,
            'date_out' => $this->date_out,
            'completed_in' => $this->completed_in,
            'completed_out' => $this->completed_out,
            'search' => $this->search,
            'companyIds' => $this->companyIds,
            'chart_granularity' => $this->chartGranularity,
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

    private function applyChartPeriod(string $period): void
    {
        $this->syncingChartPeriod = true;
        $today = now();
        if ($period === '7d') {
            $this->date_in = $today->copy()->subDays(6)->toDateString();
            $this->date_out = $today->toDateString();
            $this->chartGranularity = 'day';
            $this->syncingChartPeriod = false;
            return;
        }

        if ($period === '12m') {
            $this->date_in = $today->copy()->subMonthsNoOverflow(11)->startOfMonth()->toDateString();
            $this->date_out = $today->toDateString();
            $this->chartGranularity = 'month';
            $this->syncingChartPeriod = false;
            return;
        }

        // default: 30d
        $this->date_in = $today->copy()->subDays(29)->toDateString();
        $this->date_out = $today->toDateString();
        $this->chartGranularity = 'day';
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
        if (!$this->isValidDate((string) $this->date_in) || !$this->isValidDate((string) $this->date_out)) {
            return;
        }

        $start = Carbon::parse($this->date_in)->startOfDay();
        $end = Carbon::parse($this->date_out)->startOfDay();
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $days = $start->diffInDays($end) + 1;
        $this->chartGranularity = $days > 120 ? 'month' : 'day';
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
