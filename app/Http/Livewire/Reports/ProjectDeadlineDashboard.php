<?php

namespace App\Http\Livewire\Reports;

use App\Jobs\Reports\ProjectDeadlineExportJob;
use App\Models\{Company, OvPhaseDuration};
use App\Services\Indicators\{ProjectDeadlineAnalytics, ProjectDeadlineCharts};
use App\Services\Notes\OvStatusHistoryService;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Prazo de Projetos (Serviços → Relatórios, gerência): aderência de prazo do levantamento (status 27) e do desenho (status 28), TMEP e empresas.
 * A tela só orquestra filtros; os números vêm de ProjectDeadlineAnalytics e os gráficos de ProjectDeadlineCharts (mesmo `my-panel.js`).
 */
class ProjectDeadlineDashboard extends Component
{
    public const PRESETS = [
        'year'  => 'Ano todo',
        '90d'   => 'Últimos 90 dias',
        'month' => 'Mês atual',
        'prev'  => 'Mês anterior',
    ];

    public ?string $selectedCompany = null;

    public string $preset = 'year';

    public ?string $dt_in = null;

    public ?string $dt_out = null;

    protected $queryString = [
        'selectedCompany' => ['except' => '', 'as' => 'empresa'],
        'preset'          => ['except' => 'year', 'as' => 'periodo'],
        'dt_in'           => ['except' => '', 'as' => 'dtin'],
        'dt_out'          => ['except' => '', 'as' => 'dtout'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('management'), 403);

        Carbon::setLocale('pt_BR');

        if ($this->preset !== 'custom' || !$this->dt_in || !$this->dt_out) {
            $this->applyPreset($this->preset);
        }
    }

    public function hydrate(): void
    {
        Carbon::setLocale('pt_BR');
        abort_unless(auth()->user()?->can('management'), 403);
    }

    public function setPreset(string $preset): void
    {
        $this->applyPreset($preset);
        $this->refreshCharts();
    }

    public function updatedDtIn(): void
    {
        $this->preset = 'custom';
        $this->refreshCharts();
    }

    public function updatedDtOut(): void
    {
        $this->preset = 'custom';
        $this->refreshCharts();
    }

    public function updatedSelectedCompany(): void
    {
        $this->selectedCompany = $this->selectedCompany ?: null;
        $this->refreshCharts();
    }

    /** Clique numa empresa da tabela: filtra o painel inteiro nela. */
    public function focusCompany(string $companyId): void
    {
        $this->selectedCompany = $companyId;
        $this->refreshCharts();
    }

    public function clearFilters(): void
    {
        $this->selectedCompany = null;
        $this->refreshCharts();
    }

    public function exportToExcel(): void
    {
        ProjectDeadlineExportJob::dispatch([
            'company' => $this->selectedCompany,
            'dt_init' => $this->dt_in,
            'dt_end'  => $this->dt_out,
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

    /** @return array<string,mixed> resultado do ProjectDeadlineAnalytics, calculado uma vez por requisição */
    public function getSnapshotProperty(): array
    {
        return app(ProjectDeadlineAnalytics::class)->report($this->filters(), withDetail: false);
    }

    public function getCompanyListProperty()
    {
        return Company::query()
            ->whereIn('id', OvPhaseDuration::query()->whereNotNull('company_id')->select('company_id'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function render()
    {
        $snapshot = $this->snapshot;

        return view('livewire.reports.project-deadline-dashboard', [
            'snapshot'    => $snapshot,
            'charts'      => ProjectDeadlineCharts::all($snapshot),
            'companyList' => $this->companyList,
            'hasFilters'  => (bool) $this->selectedCompany,
            'greeting'    => now()->translatedFormat('l, d \d\e F'),
        ]);
    }

    /** @return array{company: ?string, from: ?string, to: ?string} */
    private function filters(): array
    {
        return ['company' => $this->selectedCompany, 'from' => $this->dt_in, 'to' => $this->dt_out];
    }

    private function applyPreset(string $preset): void
    {
        $today = now();
        $floor = Carbon::parse(OvStatusHistoryService::FLOOR);

        [$from, $to] = match ($preset) {
            '90d'   => [$today->copy()->subDays(89), $today],
            'month' => [$today->copy()->startOfMonth(), $today],
            'prev'  => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            default => [$floor, $today],
        };

        $this->preset = array_key_exists($preset, self::PRESETS) ? $preset : 'year';
        $this->dt_in  = max($from, $floor)->toDateString();
        $this->dt_out = $to->toDateString();
    }

    /** Envia os gráficos novos; o JS atualiza cada dataset in-place (sem recriar o gráfico). */
    private function refreshCharts(): void
    {
        $this->dispatchBrowserEvent('my-panel:charts', ['charts' => ProjectDeadlineCharts::all($this->snapshot)]);
    }
}
