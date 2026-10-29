<?php

namespace App\Http\Livewire\Reports;

use App\Jobs\Reports\ExportPostWorkProcessReportJob;
use App\Models\{Company, SystemSetting};
use App\Services\Reports\PostWorkProcessReportService;
use App\Support\SicodeRules;
use Livewire\{Component, WithPagination};

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

    public bool $only_open = false;

    public int $perPage = 30;

    public string $view = 'table';

    public ?string $cell_stage = null;

    public ?int $cell_day = null;

    public bool $showParams = false;

    /** @var array<string, int|string> */
    public array $params = [];

    public function mount(): void
    {
        $this->loadParams();

        $this->from = $this->from ?: now()->startOfMonth()->format('Y-m-d');
        $this->to   = $this->to ?: now()->format('Y-m-d');
    }

    private function loadParams(): void
    {
        $this->params = [
            ...app(PostWorkProcessReportService::class)->limits(),
            'start_basis' => SicodeRules::postWorkProcessStartBasis(),
        ];
    }

    /** Prazos (dias úteis) e início da contagem; cada deployment (SP/ES) guarda os seus. */
    public function saveParams(): void
    {
        abort_unless(auth()->user()?->can('management'), 403);

        $this->validate([
            'params.fiscal_dispatch' => 'required|integer|min:1|max:60',
            'params.fiscalization'   => 'required|integer|min:1|max:60',
            'params.measurement'     => 'required|integer|min:1|max:60',
            'params.total'           => 'required|integer|min:1|max:120',
            'params.start_basis'     => 'required|in:' . SicodeRules::POST_WORK_PROCESS_START_INFORM . ',' . SicodeRules::POST_WORK_PROCESS_START_ADS,
        ]);

        foreach (['fiscal_dispatch', 'fiscalization', 'measurement', 'total'] as $stage) {
            SystemSetting::setValue(SicodeRules::databaseRuleKey("post_work_process.limits.{$stage}"), (string) (int) $this->params[$stage]);
        }

        SystemSetting::setValue(SicodeRules::databaseRuleKey('post_work_process.start_basis'), (string) $this->params['start_basis']);

        $this->resetPage();
        $this->showParams = false;
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Parâmetros salvos',
            'timer'    => 2500,
        ]);
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
        $this->cell_day   = $day;
        $this->resetPage();
    }

    public function clearCell(): void
    {
        $this->cell_stage = null;
        $this->cell_day   = null;
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
            'only_open'  => $this->only_open,
        ];
    }

    public function render()
    {
        $service = app(PostWorkProcessReportService::class);
        $all     = $service->rows($this->filters());

        // A matriz sempre conta o conjunto filtrado por tipo/período/empresa; a célula só filtra a lista abaixo.
        $matrix = $service->matrix($all);
        $list   = $service->filterByCell($all, $this->cell_stage, $this->cell_day);

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
            'startBasis'  => $service->startBasis(),
            'rows'        => $rows,
            'summary'     => $service->summarize($all),
            'syncedAt'    => $service->lastSyncedAt(),
            'matrix'      => $matrix,
            'limits'      => $service->limits(),
            'stageLabels' => $service->stageLabels(),
            'ganttMax'    => min($ganttMax, 30),
            'companies'   => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
