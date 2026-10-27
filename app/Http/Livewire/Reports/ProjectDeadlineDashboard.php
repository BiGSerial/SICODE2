<?php

namespace App\Http\Livewire\Reports;

use App\Jobs\Reports\ProjectDeadlineExportJob;
use App\Models\{Company, OvPhaseDuration};
use App\Services\Indicators\ProjectDeadlineAnalytics;
use App\Services\Notes\OvStatusHistoryService;
use Livewire\Component;

class ProjectDeadlineDashboard extends Component
{
    private const MONTHS = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

    public string $dt_in = '';

    public string $dt_out = '';

    public string $companyId = '';

    protected $queryString = [
        'dt_in'     => ['except' => '', 'as' => 'de'],
        'dt_out'    => ['except' => '', 'as' => 'ate'],
        'companyId' => ['except' => '', 'as' => 'emp'],
    ];

    public function mount(): void
    {
        $this->dt_in  = $this->dt_in ?: substr(OvStatusHistoryService::FLOOR, 0, 10);
        $this->dt_out = $this->dt_out ?: now()->toDateString();
    }

    public function updated(): void
    {
        foreach ($this->phases() as $phase) {
            $this->dispatchBrowserEvent('grafico-atualizar-pd_' . $phase['phase'], $phase['chart']);
        }
    }

    public function getCompaniesProperty()
    {
        return Company::query()
            ->whereIn('id', OvPhaseDuration::query()->whereNotNull('company_id')->select('company_id'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return array<int,array<string,mixed>> fases do ProjectDeadlineAnalytics, cada uma com o gráfico da tela
     */
    protected function phases(): array
    {
        $report = app(ProjectDeadlineAnalytics::class)->report($this->filters(), withDetail: false);

        return array_map(fn (array $phase) => $phase + ['chart' => $this->buildChart($phase['phase'], $phase['months'])], $report['phases']);
    }

    /** @return array{from:?string,to:?string,company:?string} */
    protected function filters(): array
    {
        return ['from' => $this->dt_in, 'to' => $this->dt_out, 'company' => $this->companyId];
    }

    public function bandLabels(int $phase): array
    {
        return ProjectDeadlineAnalytics::bandLabels($phase);
    }

    public function monthLabel(string $ym): string
    {
        [$year, $month] = explode('-', $ym);

        return self::MONTHS[(int) $month - 1] . '-' . substr($year, 2);
    }

    protected function buildChart(int $phase, array $months): array
    {
        $colors   = ['#22c55e', '#9ca3af', '#0f4c5c', '#dc2626'];
        $datasets = [];

        foreach ($this->bandLabels($phase) as $i => $label) {
            $datasets[] = [
                'type'            => 'bar',
                'label'           => $label,
                'data'            => array_map(fn ($m) => $m['bands'][$i], $months),
                'backgroundColor' => $colors[$i],
                'stack'           => 'faixas',
                'yAxisID'         => 'y',
            ];
        }

        $datasets[] = [
            'type'            => 'line',
            'label'           => '% aderência',
            'data'            => array_column($months, 'adherence'),
            'borderColor'     => '#1d4ed8',
            'backgroundColor' => '#1d4ed8',
            'borderWidth'     => 2,
            'tension'         => 0.2,
            'yAxisID'         => 'y1',
        ];

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => array_map(fn ($m) => $this->monthLabel($m['month']), $months),
                'datasets' => $datasets,
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins'             => ['legend' => ['position' => 'bottom']],
                'scales'              => [
                    'x'  => ['stacked' => true],
                    'y'  => ['stacked' => true, 'beginAtZero' => true, 'title' => ['display' => true, 'text' => 'OVs']],
                    'y1' => [
                        'position' => 'right',
                        'min'      => 0,
                        'max'      => 100,
                        'grid'     => ['drawOnChartArea' => false],
                        'title'    => ['display' => true, 'text' => '% aderência'],
                    ],
                ],
            ],
        ];
    }

    public function exportToExcel(): void
    {
        $filters = $this->filters();

        ProjectDeadlineExportJob::dispatch([
            'company' => $filters['company'] ?: null,
            'dt_init' => $filters['from'],
            'dt_end'  => $filters['to'],
        ], auth()->id())
            ->onConnection((string) config('queue.channels.exports.connection', 'database'))
            ->onQueue((string) config('queue.channels.exports.queue', 'exports'));

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO EM ANDAMENTO.',
            'html'     => "<div class='card'><div class='card-body'><p>O relatório de prazo de projetos está sendo gerado em Excel. Você será notificado quando o arquivo estiver pronto para download.</p><p class='fw-bold'>Verifique sua Central de Notificação.</p></div></div>",
            'timer'    => 5000,
        ]);
    }

    public function render()
    {
        return view('livewire.reports.project-deadline-dashboard', [
            'phases' => $this->phases(),
        ]);
    }
}
