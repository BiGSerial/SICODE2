<?php

namespace App\Http\Livewire\Engineers;

use App\Jobs\Engineers\PepAlertsExportJob;
use App\Models\PepBalanceAlert;
use App\Services\Engineers\{PepAlertsAnalytics, PepAlertsCharts};
use App\Services\Partner\PepBudgetWatchService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

/**
 * Informes sem saldo no PEP (Engenharia): pilha para tratar, histórico dos tratados e painel de acompanhamento.
 * A tela só orquestra filtros; os números vêm de PepAlertsAnalytics e os gráficos de PepAlertsCharts (mesmo `my-panel.js`).
 */
class PepAlerts extends Component
{
    public const PRESETS = [
        '7d'    => 'Últimos 7 dias',
        '30d'   => 'Últimos 30 dias',
        'month' => 'Mês atual',
        'prev'  => 'Mês anterior',
    ];

    /** Linhas da tabela antes de expandir. */
    public const VISIBLE = 15;

    public const EXPANDED = 100;

    public string $tab = 'open';

    public ?string $company = null;

    public ?string $region = null;

    public string $preset = '30d';

    public ?string $dt_in = null;

    public ?string $dt_out = null;

    public bool $showAll = false;

    public ?int $handlingId = null;

    public string $handleNotes = '';

    protected $queryString = [
        'tab'     => ['except' => 'open', 'as' => 'aba'],
        'company' => ['except' => '', 'as' => 'empresa'],
        'region'  => ['except' => '', 'as' => 'regiao'],
        'preset'  => ['except' => '30d', 'as' => 'periodo'],
        'dt_in'   => ['except' => '', 'as' => 'dtin'],
        'dt_out'  => ['except' => '', 'as' => 'dtout'],
    ];

    public function mount(): void
    {
        Carbon::setLocale('pt_BR');

        $this->tab = $this->tab === 'history' ? 'history' : 'open';

        if ($this->preset !== 'custom' || !$this->dt_in || !$this->dt_out) {
            $this->applyPreset($this->preset);
        }
    }

    public function hydrate(): void
    {
        Carbon::setLocale('pt_BR');
    }

    public function setTab(string $tab): void
    {
        $this->tab     = $tab === 'history' ? 'history' : 'open';
        $this->showAll = false;
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

    public function updatedRegion(): void
    {
        $this->region = $this->region ?: null;
        $this->refreshCharts();
    }

    public function clearFilters(): void
    {
        $this->company = $this->region = null;
        $this->refreshCharts();
    }

    public function exportToExcel(): void
    {
        PepAlertsExportJob::dispatch([
            'company' => $this->company,
            'region'  => $this->region,
            'dt_init' => $this->filters()['from'],
            'dt_end'  => $this->filters()['to'],
        ], auth()->id())
            ->onConnection((string) config('queue.channels.exports.connection', 'database'))
            ->onQueue((string) config('queue.channels.exports.queue', 'exports'));

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO EM ANDAMENTO.',
            'html'     => "<div class='card'><div class='card-body'><p>O relatório de informes sem saldo no PEP está sendo gerado em Excel. Você será notificado quando o arquivo estiver pronto para download.</p><p class='fw-bold'>Verifique sua Central de Notificação.</p></div></div>",
            'timer'    => 5000,
        ]);
    }

    public function askHandle(int $id): void
    {
        if (!$this->visibleQuery()->open()->whereKey($id)->exists()) {
            return;
        }

        $this->handlingId  = $id;
        $this->handleNotes = '';
        $this->dispatchBrowserEvent('showModal', ['id' => 'pepAlertHandleModal']);
    }

    public function confirmHandle(): void
    {
        $alert = $this->handlingId
            ? $this->visibleQuery()->open()->whereKey($this->handlingId)->first()
            : null;

        if ($alert) {
            app(PepBudgetWatchService::class)->handle($alert, auth()->user(), $this->handleNotes);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'INFORME TRATADO',
                'html'     => 'O item foi para o histórico.',
                'timer'    => 3000,
            ]);
        }

        $this->handlingId  = null;
        $this->handleNotes = '';
        $this->dispatchBrowserEvent('hideModal');
        $this->refreshCharts();
    }

    /** @return array<string, mixed> resultado do PepAlertsAnalytics, calculado uma vez por requisição */
    public function getSnapshotProperty(): array
    {
        return app(PepAlertsAnalytics::class)->snapshot(auth()->user(), $this->filters());
    }

    public function render()
    {
        $snapshot = $this->snapshot;
        $rows     = $this->tab === 'history' ? $snapshot['history'] : $snapshot['open'];
        $limit    = $this->showAll ? self::EXPANDED : self::VISIBLE;

        return view('livewire.engineers.pep-alerts', [
            'snapshot'  => $snapshot,
            'charts'    => PepAlertsCharts::all($snapshot),
            'rows'      => array_slice($rows, 0, $limit),
            'rowsMore'  => max(0, count($rows) - self::VISIBLE),
            'rowsTotal' => count($rows),
        ]);
    }

    /** @return array{company: ?string, region: ?string, from: ?string, to: ?string} */
    private function filters(): array
    {
        return ['company' => $this->company, 'region' => $this->region, 'from' => $this->dt_in, 'to' => $this->dt_out];
    }

    /** Escopo visível ao usuário (região da obra ou empresa responsável). */
    private function visibleQuery(): Builder
    {
        return app(PepBudgetWatchService::class)->scopeVisibleTo(PepBalanceAlert::query(), auth()->user());
    }

    private function applyPreset(string $preset): void
    {
        $today = now();

        [$from, $to] = match ($preset) {
            '7d'    => [$today->copy()->subDays(6), $today],
            'month' => [$today->copy()->startOfMonth(), $today],
            'prev'  => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            default => [$today->copy()->subDays(29), $today],
        };

        $this->preset = array_key_exists($preset, self::PRESETS) ? $preset : '30d';
        $this->dt_in  = $from->toDateString();
        $this->dt_out = $to->toDateString();
    }

    /** Envia os gráficos novos; o JS atualiza cada dataset in-place (sem recriar o gráfico). */
    private function refreshCharts(): void
    {
        $this->dispatchBrowserEvent('my-panel:charts', ['charts' => PepAlertsCharts::all($this->snapshot)]);
    }
}
