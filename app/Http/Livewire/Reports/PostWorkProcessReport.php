<?php

namespace App\Http\Livewire\Reports;

use App\Jobs\Reports\ExportPostWorkProcessReportJob;
use App\Models\Company;
use App\Services\Reports\PostWorkProcessReportService;
use Livewire\Component;
use Livewire\WithPagination;

class PostWorkProcessReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $type = 'all';
    public ?string $from = null;
    public ?string $to = null;
    public ?string $company_id = null;
    public ?string $search = null;
    public bool $only_late = false;
    public int $perPage = 30;

    public function mount(): void
    {
        $this->from = $this->from ?: now()->startOfMonth()->format('Y-m-d');
        $this->to = $this->to ?: now()->format('Y-m-d');
    }

    public function updating($name): void
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    public function exportReport(): void
    {
        ExportPostWorkProcessReportJob::dispatch($this->filters(), (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Exportação iniciada',
            'html'     => '<p class="mb-0"><strong>Você será notificado quando o download estiver disponível.</strong></p>',
            'timer'    => 5000,
        ]);
    }

    private function filters(): array
    {
        return [
            'type'       => $this->type,
            'from'       => $this->from,
            'to'         => $this->to,
            'company_id' => $this->company_id,
            'search'     => $this->search,
            'only_late'  => $this->only_late,
        ];
    }

    public function render()
    {
        $service = app(PostWorkProcessReportService::class);
        $all = $service->rows($this->filters());

        $page = $this->page ?? 1;
        $rows = new \Illuminate\Pagination\LengthAwarePaginator(
            $all->forPage($page, $this->perPage)->values(),
            $all->count(),
            $this->perPage,
            $page,
            ['path' => request()->url()]
        );

        return view('livewire.reports.post-work-process-report', [
            'rows'      => $rows,
            'summary'   => $service->summarize($all),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
