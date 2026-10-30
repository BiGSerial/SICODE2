<?php

namespace App\Http\Livewire\Engineers;

use App\Jobs\Engineers\ViabilityDashboardExportJob;
use App\Services\Engineers\{ViabilityDashboardAnalytics, ViabilityDashboardCharts};
use Carbon\Carbon;
use Livewire\Component;

/**
 * Painel de Viabilidade (Engenharia): o que está com o engenheiro agora, como o período fechou e como cada empresa está indo.
 * A tela só orquestra filtros; os números vêm de ViabilityDashboardAnalytics e os gráficos de ViabilityDashboardCharts (mesmo `my-panel.js`).
 */
class ViabilityDashboard extends Component
{
    public const PRESETS = [
        'month' => 'Mês atual',
        'prev'  => 'Mês anterior',
        '30d'   => 'Últimos 30 dias',
        '90d'   => 'Últimos 90 dias',
    ];

    public ?string $company = null;

    public string $preset = 'month';

    public ?string $dt_in = null;

    public ?string $dt_out = null;

    protected $queryString = [
        'company' => ['except' => '', 'as' => 'empresa'],
        'preset'  => ['except' => 'month', 'as' => 'periodo'],
        'dt_in'   => ['except' => '', 'as' => 'dtin'],
        'dt_out'  => ['except' => '', 'as' => 'dtout'],
    ];

    public function mount(): void
    {
        Carbon::setLocale('pt_BR');

        if ($this->preset !== 'custom' || !$this->dt_in || !$this->dt_out) {
            $this->applyPreset($this->preset);
        }
    }

    public function hydrate(): void
    {
        Carbon::setLocale('pt_BR');
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

    public function updatedCompany(): void
    {
        $this->company = $this->company ?: null;
        $this->refreshCharts();
    }

    public function clearFilters(): void
    {
        $this->company = null;
        $this->refreshCharts();
    }

    public function exportToExcel(): void
    {
        ViabilityDashboardExportJob::dispatch([
            'company' => $this->company,
            'dt_init' => $this->filters()['from'],
            'dt_end'  => $this->filters()['to'],
        ], auth()->id())
            ->onConnection((string) config('queue.channels.exports.connection', 'database'))
            ->onQueue((string) config('queue.channels.exports.queue', 'exports'));

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO EM ANDAMENTO.',
            'html'     => "<div class='card'><div class='card-body'><p>O painel de viabilidade está sendo gerado em Excel. Você será notificado quando o arquivo estiver pronto para download.</p><p class='fw-bold'>Verifique sua Central de Notificação.</p></div></div>",
            'timer'    => 5000,
        ]);
    }

    /** @return array<string, mixed> resultado do ViabilityDashboardAnalytics, calculado uma vez por requisição */
    public function getSnapshotProperty(): array
    {
        return app(ViabilityDashboardAnalytics::class)->snapshot(auth()->user(), $this->filters());
    }

    public function render()
    {
        $snapshot = $this->snapshot;

        return view('livewire.engineers.viability-dashboard', [
            'snapshot' => $snapshot,
            'charts'   => ViabilityDashboardCharts::all($snapshot),
        ]);
    }

    /** @return array{company: ?string, from: ?string, to: ?string} */
    private function filters(): array
    {
        return ['company' => $this->company, 'from' => $this->dt_in, 'to' => $this->dt_out];
    }

    private function applyPreset(string $preset): void
    {
        $today = now();

        [$from, $to] = match ($preset) {
            'prev'  => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            '30d'   => [$today->copy()->subDays(29), $today],
            '90d'   => [$today->copy()->subDays(89), $today],
            default => [$today->copy()->startOfMonth(), $today],
        };

        $this->preset = array_key_exists($preset, self::PRESETS) ? $preset : 'month';
        $this->dt_in  = $from->toDateString();
        $this->dt_out = $to->toDateString();
    }

    /** Envia os gráficos novos; o JS atualiza cada dataset in-place (sem recriar o gráfico). */
    private function refreshCharts(): void
    {
        $this->dispatchBrowserEvent('my-panel:charts', ['charts' => ViabilityDashboardCharts::all($this->snapshot)]);
    }
}
