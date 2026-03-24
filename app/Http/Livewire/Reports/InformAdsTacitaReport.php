<?php

namespace App\Http\Livewire\Reports;

use App\Jobs\Reports\ExportInformAdsTacitaReportJob;
use App\Models\Company;
use App\Services\Reports\InformAdsTacitReportService;
use Livewire\Component;
use Livewire\WithPagination;

class InformAdsTacitaReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public int $perPage = 50;
    public string $mode = 'note';
    public string $openFilter = 'all';
    public string $dateField = 'ads_created_at';
    public ?string $date_in = null;
    public ?string $date_out = null;
    public ?string $search = null;
    public array $companyIds = [];
    public $companies;

    protected $queryString = [
        'mode' => ['except' => 'note', 'as' => 'm'],
        'openFilter' => ['except' => 'all', 'as' => 'open'],
        'dateField' => ['except' => 'ads_created_at', 'as' => 'df'],
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
            ->join('work_reports as wr', 'wr.company_id', '=', 'companies.id')
            ->join('adsforms as af', 'af.work_report_id', '=', 'wr.id')
            ->where('af.tacit', true)
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
        $this->mode = 'note';
        $this->openFilter = 'all';
        $this->dateField = 'ads_created_at';
        $this->search = null;
        $this->companyIds = [];
        $this->date_in = now()->startOfMonth()->format('Y-m-d');
        $this->date_out = now()->format('Y-m-d');
        $this->perPage = 50;
        $this->resetPage();
    }

    public function exportReport(): void
    {
        ExportInformAdsTacitaReportJob::dispatch($this->filters(), (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Exportação iniciada',
            'html' => "<div class='card'><div class='card-body'>
                <p>Seu arquivo está sendo gerado.</p>
                <p class='mb-0'><strong>Você será notificado quando o download estiver disponível.</strong></p>
            </div></div>",
            'timer' => 5000,
        ]);
    }

    public function getRowsProperty()
    {
        return app(InformAdsTacitReportService::class)
            ->paginate($this->mode, $this->filters(), $this->perPage);
    }

    private function filters(): array
    {
        return [
            'mode' => $this->mode,
            'openFilter' => $this->openFilter,
            'dateField' => $this->dateField,
            'date_in' => $this->date_in,
            'date_out' => $this->date_out,
            'search' => $this->search,
            'companyIds' => $this->companyIds,
        ];
    }

    public function render()
    {
        $rows = $this->rows;
        $summary = app(InformAdsTacitReportService::class)
            ->summarize($this->mode, $this->filters());

        return view('livewire.reports.inform-ads-tacita-report', [
            'rows' => $rows,
            'modeLabel' => $this->mode === 'order' ? 'Por ORDEM' : 'Por NOTA',
            'summary' => $summary,
        ]);
    }
}
