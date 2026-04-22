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
    public ?string $company_id = null;
    public string $passive_mode = 'both';
    public string $open_only = '0';
    public ?string $search = null;
    public ?string $batch_search = null;
    public array $direct_terms = [];
    public int $perPage = 30;

    protected $queryString = [
        'dispatch_from' => ['except' => '', 'as' => 'dfi'],
        'dispatch_to' => ['except' => '', 'as' => 'dfo'],
        'company_id' => ['except' => '', 'as' => 'company'],
        'passive_mode' => ['except' => 'both', 'as' => 'passivo'],
        'open_only' => ['except' => '0', 'as' => 'abertos'],
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
        $this->company_id = null;
        $this->passive_mode = 'both';
        $this->open_only = '0';
        $this->search = null;
        $this->batch_search = null;
        $this->direct_terms = [];
        $this->perPage = 30;
        $this->resetPage();
    }

    public function applyBatchSearch(): void
    {
        $this->direct_terms = $this->parseTerms((string) ($this->batch_search ?? ''));
        $this->resetPage();
    }

    public function clearBatchSearch(): void
    {
        $this->batch_search = null;
        $this->direct_terms = [];
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
            'company_id' => $this->company_id,
            'passive_mode' => $this->passive_mode,
            'open_only' => $this->open_only,
            'search' => $this->search,
            'direct_terms' => $this->direct_terms,
        ];
    }

    private function parseTerms(string $value): array
    {
        $parts = preg_split('/[\s,;\n\r\t]+/', trim($value)) ?: [];
        $clean = array_values(array_unique(array_filter(array_map('trim', $parts), fn ($term) => $term !== '')));

        return $clean;
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
