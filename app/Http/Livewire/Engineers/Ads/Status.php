<?php

namespace App\Http\Livewire\Engineers\Ads;

use App\Jobs\Engineers\ExportAdsSituationJob;
use App\Models\Company;
use App\Services\Engineers\AdsSituationService;
use Livewire\Component;
use Livewire\WithPagination;

class Status extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public int $perPage = 25;
    public string $statusFilter = 'disabled';
    public string $detailStatusFilter = 'all';
    public ?string $date_in = null;
    public ?string $date_out = null;
    public ?string $search = null;
    public array $companyIds = [];
    public array $rowFineData = [];
    public $companies;

    protected $queryString = [
        'statusFilter' => ['except' => 'disabled', 'as' => 'status'],
        'detailStatusFilter' => ['except' => 'all', 'as' => 'detail'],
        'date_in' => ['except' => '', 'as' => 'din'],
        'date_out' => ['except' => '', 'as' => 'dout'],
        'search' => ['except' => '', 'as' => 'q'],
        'companyIds' => ['except' => [], 'as' => 'company'],
        'perPage' => ['except' => 25, 'as' => 'pp'],
    ];

    public function mount(): void
    {
        $this->date_in = $this->date_in ?: now()->startOfMonth()->format('Y-m-d');
        $this->date_out = $this->date_out ?: now()->format('Y-m-d');

        $this->companies = Company::query()
            ->join('work_reports as wr', 'wr.company_id', '=', 'companies.id')
            ->where('wr.rejected', false)
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
        $this->statusFilter = 'disabled';
        $this->detailStatusFilter = 'all';
        $this->search = null;
        $this->companyIds = [];
        $this->date_in = now()->startOfMonth()->format('Y-m-d');
        $this->date_out = now()->format('Y-m-d');
        $this->perPage = 25;
        $this->resetPage();
    }

    public function getRowsProperty()
    {
        return app(AdsSituationService::class)
            ->paginate($this->filters(), $this->perPage);
    }

    public function refreshFine(int $workReportId): void
    {
        $result = app(AdsSituationService::class)->refreshSingleWorkReportFine($workReportId);

        if (!$result) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Registro não encontrado.',
                'timer' => 2500,
            ]);

            return;
        }

        $this->rowFineData[$workReportId] = $result;
    }

    public function updatedStatusFilter(): void
    {
        if ($this->statusFilter !== 'atual') {
            $this->detailStatusFilter = 'all';
        }
        $this->resetPage();
    }

    public function setDetailStatusFilter(string $status): void
    {
        if ($this->statusFilter !== 'atual') {
            return;
        }

        $this->detailStatusFilter = $this->detailStatusFilter === $status ? 'all' : $status;
        $this->resetPage();
    }

    public function exportReport(): void
    {
        ExportAdsSituationJob::dispatch($this->filters(), (string) auth()->id());

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

    private function filters(): array
    {
        return [
            'statusFilter' => $this->statusFilter,
            'detailStatusFilter' => $this->detailStatusFilter,
            'date_in' => $this->date_in,
            'date_out' => $this->date_out,
            'search' => $this->search,
            'companyIds' => $this->companyIds,
        ];
    }

    public function render()
    {
        return view('livewire.engineers.ads.status', [
            'rows' => $this->rows,
            'summary' => app(AdsSituationService::class)->summarize($this->filters()),
        ]);
    }
}
