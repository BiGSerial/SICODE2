<?php

namespace App\Http\Livewire\Reports;

use App\Jobs\Reports\ExportFiveNoteReportJob;
use App\Models\Company;
use App\Services\Reports\FiveNoteReportService;
use Livewire\Component;
use Livewire\WithPagination;

class FiveNoteReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public ?string $dispatch_from = null;
    public ?string $dispatch_to = null;
    public ?string $completed_from = null;
    public ?string $completed_to = null;
    public ?string $company_id = null;
    public ?string $search = null;
    public int $perPage = 30;

    protected $queryString = [
        'dispatch_from' => ['except' => '', 'as' => 'dfi'],
        'dispatch_to' => ['except' => '', 'as' => 'dfo'],
        'completed_from' => ['except' => '', 'as' => 'cfi'],
        'completed_to' => ['except' => '', 'as' => 'cfo'],
        'company_id' => ['except' => '', 'as' => 'company'],
        'search' => ['except' => '', 'as' => 'q'],
        'perPage' => ['except' => 30, 'as' => 'pp'],
    ];

    public function mount(): void
    {
        $this->dispatch_from = $this->dispatch_from ?: now()->startOfMonth()->format('Y-m-d');
        $this->dispatch_to = $this->dispatch_to ?: now()->format('Y-m-d');
    }

    public function updating($name): void
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->dispatch_from = now()->startOfMonth()->format('Y-m-d');
        $this->dispatch_to = now()->format('Y-m-d');
        $this->completed_from = null;
        $this->completed_to = null;
        $this->company_id = null;
        $this->search = null;
        $this->perPage = 30;
        $this->resetPage();
    }

    public function exportReport(): void
    {
        ExportFiveNoteReportJob::dispatch($this->filters(), (string) auth()->id());

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

    public function getCompaniesProperty()
    {
        return Company::query()
            ->join('five_notes as fn', 'fn.company_id', '=', 'companies.id')
            ->select('companies.id', 'companies.name')
            ->distinct()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function filters(): array
    {
        return [
            'dispatch_from' => $this->dispatch_from,
            'dispatch_to' => $this->dispatch_to,
            'completed_from' => $this->completed_from,
            'completed_to' => $this->completed_to,
            'company_id' => $this->company_id,
            'search' => $this->search,
        ];
    }

    public function render()
    {
        $service = app(FiveNoteReportService::class);
        $rows = $service->paginate($this->filters(), $this->perPage);
        $summary = $service->summarize($this->filters());

        return view('livewire.reports.five-note-report', [
            'rows' => $rows,
            'summary' => $summary,
            'companies' => $this->companies,
        ]);
    }
}
