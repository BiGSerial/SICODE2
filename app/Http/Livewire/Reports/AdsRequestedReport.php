<?php

namespace App\Http\Livewire\Reports;

use App\Enum\AdsRequestStatus;
use App\Jobs\Ads\AdsDashboardExportJob;
use App\Models\Company;
use App\Services\Ads\{AdsConnectionProbe, AdsDashboardAnalytics, AdsDashboardCharts};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{Artisan, Cache};
use Livewire\{Component, WithPagination};

/**
 * Painel de ADS solicitadas (Engenharia): só filtros e orquestração. Os dados vêm de AdsDashboardAnalytics (a mesma fonte do Excel) e os
 * gráficos de AdsDashboardCharts (mesmo `my-panel.js` da Home). Cada parte depende de uma conexão (banco local ou SQL Server); a que estiver
 * sem conexão fica desabilitada com o motivo, e as demais continuam funcionando.
 */
class AdsRequestedReport extends Component
{
    use WithPagination;

    public const PRESETS = [
        '7d'  => 'Últimos 7 dias',
        '30d' => 'Últimos 30 dias',
        '12m' => 'Últimos 12 meses',
    ];

    /** Maior intervalo personalizado (dias): acima disso o gráfico diário vira um borrão. */
    public const MAX_DAYS = 93;

    public const QUEUE_PAGE = 'queue_page';

    protected $paginationTheme = 'bootstrap';

    public int $perPage = 25;

    public string $statusFilter = 'all';

    public ?string $statusExact = null;

    public ?string $company = null;

    /** Origem da solicitação automática: `reused` (reaproveitou ADS já disponível) ou `new` (entrou na fila). Vem do clique na rosca. */
    public ?string $origin = null;

    public ?string $search = null;

    /** `request` filtra o período pela data da solicitação; `completed`, pela data de conclusão. */
    public string $dateBy = 'request';

    public string $preset = '7d';

    public ?string $dt_in = null;

    public ?string $dt_out = null;

    protected $queryString = [
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
        'statusExact'  => ['except' => '', 'as' => 'sx'],
        'company'      => ['except' => '', 'as' => 'empresa'],
        'origin'       => ['except' => '', 'as' => 'origem'],
        'search'       => ['except' => '', 'as' => 'q'],
        'dateBy'       => ['except' => 'request', 'as' => 'por'],
        'preset'       => ['except' => '7d', 'as' => 'cp'],
        'dt_in'        => ['except' => '', 'as' => 'din'],
        'dt_out'       => ['except' => '', 'as' => 'dout'],
        'perPage'      => ['except' => 25, 'as' => 'pp'],
    ];

    public function mount(): void
    {
        Carbon::setLocale('pt_BR');

        $this->dateBy  = $this->dateBy === 'completed' ? 'completed' : 'request';
        $this->preset  = in_array($this->preset, [...array_keys(self::PRESETS), 'custom'], true) ? $this->preset : '7d';
        $this->perPage = in_array($this->perPage, [25, 50, 100], true) ? $this->perPage : 25;

        if ($this->preset !== 'custom') {
            $this->applyPreset($this->preset);
        } elseif (!$this->dt_in || !$this->dt_out) {
            $this->applyPreset('7d');
        }
    }

    public function hydrate(): void
    {
        Carbon::setLocale('pt_BR');
    }

    public function updated($name): void
    {
        if ($name === 'page' || $name === self::QUEUE_PAGE) {
            return;
        }

        if ($name === 'dt_in' || $name === 'dt_out') {
            $this->preset = 'custom';
        }

        $this->resetPage();
        $this->resetPage(self::QUEUE_PAGE);
        $this->refreshCharts();
    }

    public function setPreset(string $preset): void
    {
        $this->applyPreset($preset);
        $this->resetPage();
        $this->resetPage(self::QUEUE_PAGE);
        $this->refreshCharts();
    }

    public function clearFilters(): void
    {
        $this->reset(['statusFilter', 'statusExact', 'company', 'origin', 'search', 'dateBy', 'perPage']);
        $this->applyPreset('7d');
        $this->resetPage();
        $this->resetPage(self::QUEUE_PAGE);
        $this->refreshCharts();
    }

    /**
     * Clique num gráfico: filtra as tabelas (e o resto do painel) para o que compõe aquele ponto, barra ou fatia.
     * Dia/mês das linhas e barras viram o período (a barra "Concluídas" filtra pela data de conclusão, as demais pela de solicitação);
     * a rosca da fila filtra o status exato; a de reaproveitamento, a origem da solicitação.
     */
    public function applyChartFilter(string $chart, string $key, int $dataset = 0): void
    {
        if (in_array($chart, ['backlog', 'flow'], true)) {
            $range = $this->rangeFromChartKey($key);

            if (!$range) {
                return;
            }

            [$this->dt_in, $this->dt_out] = $range;
            $this->preset                 = 'custom';
            $this->dateBy                 = $chart === 'flow' && $dataset === 1 ? 'completed' : 'request';
        } elseif ($chart === 'queue' && in_array($key, AdsRequestStatus::values(), true)) {
            $this->statusExact = $key;
        } elseif ($chart === 'reuse' && in_array($key, ['reused', 'new'], true)) {
            $this->origin = $key;
        } else {
            return;
        }

        $this->resetPage();
        $this->resetPage(self::QUEUE_PAGE);
        $this->refreshCharts();
    }

    /** Testa as conexões na hora (ignora o cache) e atualiza o que voltou. */
    public function recheck(): void
    {
        $health = app(AdsConnectionProbe::class)->status(true);

        $this->refreshCharts();
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => collect($health)->every(fn (array $c) => $c['up']) ? 'success' : 'warning',
            'title'    => collect($health)->every(fn (array $c) => $c['up']) ? 'CONEXÕES OK' : 'HÁ CONEXÃO FORA DO AR',
            'html'     => collect($health)->map(fn (array $c) => '<div><strong>' . e($c['label']) . ':</strong> ' . ($c['up'] ? 'online' : 'sem conexão') . '</div>')->implode(''),
            'timer'    => 4000,
        ]);
    }

    /** Chamado pelo `wire:poll`: os gráficos se atualizam in-place; a fila em tempo real volta sozinha quando a conexão voltar. */
    public function refreshCharts(): void
    {
        $this->dispatchBrowserEvent('my-panel:charts', ['charts' => AdsDashboardCharts::live($this->snapshot)]);
    }

    public function syncLast40Days(): void
    {
        if (!app(AdsConnectionProbe::class)->up(AdsConnectionProbe::LIVE)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'SEM CONEXÃO COM O SQL SERVER',
                'text'     => 'A sincronização lê do SQL Server. Tente de novo quando a conexão voltar.',
                'timer'    => 6000,
            ]);

            return;
        }

        try {
            Artisan::call('sicode:sync_ads_requests', ['--since' => now()->subDays(40)->startOfDay()->toDateTimeString()]);

            $output = trim((string) Artisan::output());

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'SYNC ADS CONCLUÍDO',
                'text'     => $output !== '' ? 'Sincronização concluída. ' . mb_substr($output, 0, 240) : 'Sincronização concluída com sucesso.',
                'timer'    => 5000,
            ]);
        } catch (\Throwable $e) {
            report($e);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'SYNC ADS FALHOU',
                'text'     => mb_substr($e->getMessage(), 0, 280),
                'timer'    => 7000,
            ]);
        }

        $this->resetPage();
        $this->refreshCharts();
    }

    public function exportToExcel(): void
    {
        AdsDashboardExportJob::dispatch($this->filters(), auth()->id())
            ->onConnection((string) config('queue.channels.exports.connection', 'database'))
            ->onQueue((string) config('queue.channels.exports.queue', 'exports'));

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO EM ANDAMENTO.',
            'html'     => "<div class='card'><div class='card-body'><p>O relatório do painel de ADS está sendo gerado em Excel. Você será notificado quando o arquivo estiver pronto para download.</p><p class='fw-bold'>Verifique sua Central de Notificação.</p></div></div>",
            'timer'    => 5000,
        ]);
    }

    /** @return array<string, mixed> resultado do AdsDashboardAnalytics, calculado uma vez por requisição */
    public function getSnapshotProperty(): array
    {
        return app(AdsDashboardAnalytics::class)->snapshot($this->filters());
    }

    public function render()
    {
        $analytics = app(AdsDashboardAnalytics::class);
        $filters   = $this->filters();
        $snapshot  = $this->snapshot;

        $queue   = $analytics->queueRows($filters, 20, self::QUEUE_PAGE);
        $history = $analytics->historyRows($filters, $this->perPage);

        return view('livewire.reports.ads-requested-report', [
            's'       => $snapshot,
            'charts'  => AdsDashboardCharts::all($snapshot),
            'queue'   => $queue,
            'history' => $history,
            'options' => $this->options(),
            'period'  => $this->periodLabel($filters),
            'live'    => $snapshot['health'][AdsConnectionProbe::LIVE]['up'],
        ]);
    }

    /**
     * Filtros no formato do AdsRequestedReportService. O período vale para a data da solicitação ou da conclusão, conforme `dateBy`.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        [$from, $to] = $this->range();
        $byCompleted = $this->dateBy === 'completed';

        return [
            'statusFilter'      => $this->statusFilter,
            'status_exact'      => $this->statusExact,
            'date_in'           => $byCompleted ? null : $from,
            'date_out'          => $byCompleted ? null : $to,
            'completed_in'      => $byCompleted ? $from : null,
            'completed_out'     => $byCompleted ? $to : null,
            'search'            => $this->search,
            'origin'            => $this->origin,
            'companyIds'        => $this->company ? [$this->company] : [],
            'chart_period'      => $this->preset,
            'chart_granularity' => $this->preset === '12m' ? 'month' : 'day',
        ];
    }

    /** @return array{0: string, 1: string}|null dia (Y-m-d) ou mês (Y-m) do eixo do gráfico, como intervalo; null se a chave não for válida */
    private function rangeFromChartKey(string $key): ?array
    {
        try {
            if (preg_match('/^\d{4}-\d{2}$/', $key)) {
                $start = Carbon::createFromFormat('Y-m', $key)->startOfMonth();
                $end   = $start->copy()->endOfMonth()->min(now());

                return [$start->toDateString(), $end->toDateString()];
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $key)) {
                $day = Carbon::createFromFormat('Y-m-d', $key)->toDateString();

                return [$day, $day];
            }
        } catch (\Throwable) {
        }

        return null;
    }

    /** @return array{0: string, 1: string} intervalo (Y-m-d) do período, já limitado a MAX_DAYS no personalizado */
    private function range(): array
    {
        if ($this->preset !== 'custom') {
            return $this->presetRange($this->preset);
        }

        try {
            $from = Carbon::parse($this->dt_in)->startOfDay();
            $to   = Carbon::parse($this->dt_out)->startOfDay();
        } catch (\Throwable) {
            return $this->presetRange('7d');
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($from->diffInDays($to) >= self::MAX_DAYS) {
            $from = $to->copy()->subDays(self::MAX_DAYS - 1);
        }

        return [$from->toDateString(), $to->toDateString()];
    }

    /** @return array{0: string, 1: string} */
    private function presetRange(string $preset): array
    {
        $today = now();

        return match ($preset) {
            '30d'   => [$today->copy()->subDays(29)->toDateString(), $today->toDateString()],
            '12m'   => [$today->copy()->subMonthsNoOverflow(11)->startOfMonth()->toDateString(), $today->toDateString()],
            default => [$today->copy()->subDays(6)->toDateString(), $today->toDateString()],
        };
    }

    private function applyPreset(string $preset): void
    {
        $this->preset = array_key_exists($preset, self::PRESETS) ? $preset : '7d';

        [$this->dt_in, $this->dt_out] = $this->presetRange($this->preset);
    }

    /** @param array<string, mixed> $filters */
    private function periodLabel(array $filters): string
    {
        $from = $filters['date_in'] ?? $filters['completed_in'];
        $to   = $filters['date_out'] ?? $filters['completed_out'];

        return Carbon::parse($from)->format('d/m/Y') . ' a ' . Carbon::parse($to)->format('d/m/Y');
    }

    /** @return array{companies: \Illuminate\Support\Collection, statuses: array<int, array{value: string, label: string}>} */
    private function options(): array
    {
        return [
            'companies' => Cache::remember('ads-dashboard:companies', now()->addMinutes(10), fn () => Company::query()
                ->join('ads_requests as ar', 'ar.company_id', '=', 'companies.id')
                ->select('companies.id', 'companies.name')->distinct()->orderBy('companies.name')->get()),
            'statuses' => array_map(fn (AdsRequestStatus $status) => [
                'value' => $status->value,
                'label' => $status === AdsRequestStatus::IN_PROGRESS ? 'Em execução' : $status->label(),
            ], AdsRequestStatus::cases()),
        ];
    }
}
