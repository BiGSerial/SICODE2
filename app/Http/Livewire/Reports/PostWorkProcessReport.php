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
    public string $view = 'table';
    public ?string $cell_stage = null;
    public ?int $cell_day = null;

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

    /** Clique numa célula da matriz: filtra a lista por etapa atual + idade (clicar de novo limpa). */
    public function pickCell(string $stage, int $day): void
    {
        if ($this->cell_stage === $stage && $this->cell_day === $day) {
            $this->clearCell();

            return;
        }

        $this->cell_stage = $stage;
        $this->cell_day = $day;
        $this->resetPage();
    }

    public function clearCell(): void
    {
        $this->cell_stage = null;
        $this->cell_day = null;
        $this->resetPage();
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

        // A matriz sempre conta o conjunto filtrado por tipo/período/empresa; a célula só filtra a lista abaixo.
        $matrix = $service->matrix($all);
        $list = $service->filterByCell($all, $this->cell_stage, $this->cell_day);

        $page = $this->page ?? 1;
        $rows = new \Illuminate\Pagination\LengthAwarePaginator(
            $list->forPage($page, $this->perPage)->values(),
            $list->count(),
            $this->perPage,
            $page,
            ['path' => request()->url()]
        );

        $ganttMax = max(
            $service->limits()['total'] + 2,
            (int) $rows->getCollection()->flatMap(fn ($r) => collect($r['gantt'])->pluck('end'))->max()
        );

        return view('livewire.reports.post-work-process-report', [
            'rows'        => $rows,
            'summary'     => $service->summarize($all),
            'matrix'      => $matrix,
            'limits'      => $service->limits(),
            'stageLabels' => $service->stageLabels(),
            'ganttMax'    => min($ganttMax, 30),
            'companies'   => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
