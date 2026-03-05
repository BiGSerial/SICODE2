<?php

namespace App\Http\Livewire\Reports;

use App\Models\Company;
use App\Services\Reports\AdsRequestedReportService;
use Livewire\Component;
use Livewire\WithPagination;

class AdsRequestedReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public int $perPage = 50;
    public string $statusFilter = 'all';
    public ?string $date_in = null;
    public ?string $date_out = null;
    public ?string $search = null;
    public array $companyIds = [];
    public $companies;

    protected $queryString = [
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
        'date_in' => ['except' => '', 'as' => 'din'],
        'date_out' => ['except' => '', 'as' => 'dout'],
        'search' => ['except' => '', 'as' => 'q'],
        'companyIds' => ['except' => [], 'as' => 'company'],
        'perPage' => ['except' => 50, 'as' => 'pp'],
    ];

    public function mount(): void
    {
        $this->date_in = $this->date_in ?: now()->startOfMonth()->format('Y-m-d');
        $this->date_out = $this->date_out ?: now()->format('Y-m-d');

        $this->companies = Company::query()
            ->join('ads_requests as ar', 'ar.company_id', '=', 'companies.id')
            ->select('companies.id', 'companies.name')
            ->distinct()
            ->orderBy('companies.name')
            ->get();
    }

    public function updating($name): void
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->statusFilter = 'all';
        $this->search = null;
        $this->companyIds = [];
        $this->date_in = now()->startOfMonth()->format('Y-m-d');
        $this->date_out = now()->format('Y-m-d');
        $this->perPage = 50;
        $this->resetPage();
    }

    public function getRowsProperty()
    {
        return app(AdsRequestedReportService::class)
            ->paginate($this->filters(), $this->perPage);
    }

    private function filters(): array
    {
        return [
            'statusFilter' => $this->statusFilter,
            'date_in' => $this->date_in,
            'date_out' => $this->date_out,
            'search' => $this->search,
            'companyIds' => $this->companyIds,
        ];
    }

    public function render()
    {
        $rows = $this->rows;
        $summary = app(AdsRequestedReportService::class)->summarize($this->filters());

        return view('livewire.reports.ads-requested-report', [
            'rows' => $rows,
            'summary' => $summary,
        ]);
    }
}
