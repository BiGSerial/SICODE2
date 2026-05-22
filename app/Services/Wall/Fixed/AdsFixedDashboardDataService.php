<?php

namespace App\Services\Wall\Fixed;

use App\Models\WallScreen;
use App\Services\Reports\AdsRequestedReportService;
use App\Services\Wall\Support\CacheLockTrait;
use Carbon\Carbon;

class AdsFixedDashboardDataService
{
    use CacheLockTrait;

    private const CACHE_TTL_SECONDS = 120;

    public function __construct(
        private readonly AdsRequestedReportService $adsService,
    ) {
    }

    public function buildItemPayload(WallScreen $screen): array
    {
        $cacheKey = sprintf('wall_v2:fixed:ads:screen:%d', (int) $screen->id);

        return $this->rememberWithOptionalLock($cacheKey, self::CACHE_TTL_SECONDS, fn () => $this->compute());
    }

    public function buildManifestItem(): array
    {
        return [
            'service_id'                => 'ads-dashboard',
            'service_name'              => 'ADS - Dashboard',
            'previous_service_id'       => null,
            'previous_service_name'     => null,
            'ads_chart'                 => ['kind' => 'dashboard'],
            'cards'                     => [],
            'queue_histogram'           => ['labels' => [], 'values' => []],
            'note_type_donut'           => ['labels' => [], 'values' => [], 'total' => 0, 'associated' => 0],
            'production_open_histogram' => ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
            'production_daily'          => ['labels' => [], 'assigned' => [], 'delivered' => []],
            'internal_return_donut'     => ['labels' => [], 'values' => []],
            'recent_completed'          => [],
            'week'                      => null,
        ];
    }

    private function compute(): array
    {
        $now        = now();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $today      = $now->copy()->toDateString();
        $last15     = $now->copy()->subDays(14)->toDateString();

        $baseFilters = [
            'date_in'            => $monthStart,
            'date_out'           => $today,
            'completed_in'       => $monthStart,
            'completed_out'      => $today,
            'status_exact'       => null,
            'search'             => null,
            'companyIds'         => [],
            'statusFilter'       => 'all',
            'chart_granularity'  => null,
            'prefer_local_queue' => true,
            'exclude_statuses'   => ['FAILED', 'CANCELED'],
        ];

        $chartFilters = array_merge($baseFilters, [
            'date_in'           => $last15,
            'completed_in'      => $last15,
            'chart_granularity' => 'day',
        ]);

        $summary   = $this->adsService->summarize($baseFilters);
        $flowMonth = $this->adsService->demandVsDeliverySeries($baseFilters);
        $flowChart = $this->adsService->demandVsDeliverySeries($chartFilters);
        $queue     = $this->adsService->queueDonutSeries($baseFilters);
        $reuse     = $this->adsService->reuseEconomyDonutSeries($baseFilters);

        $periodDays = max(1, (int) ($summary['period_days'] ?? count($flowMonth['labels'] ?? []) ?: 1));
        $requestedTotal = (int) ($flowMonth['analytics']['requested_total'] ?? 0);
        $deliveredTotal = (int) ($flowMonth['analytics']['delivered_total'] ?? 0);
        $openedDailyAvg = round($requestedTotal / $periodDays, 2);
        $deliveredDailyAvg = round($deliveredTotal / $periodDays, 2);

        $labelCount    = max(1, count($flowChart['labels'] ?? []));
        $lineMeanOpen  = (float) ($flowChart['analytics']['backlog_avg'] ?? 0);
        $lineMeanOver  = (float) ($flowChart['analytics']['overdue_avg'] ?? 0);

        $lineChart = [
            'labels'   => $flowChart['labels'] ?? [],
            'datasets' => [
                [
                    'label'              => 'Acumulado em aberto',
                    'data'               => $flowChart['open_backlog'] ?? [],
                    'borderColor'        => '#7c3aed',
                    'backgroundColor'    => 'rgba(124,58,237,.2)',
                    'pointBackgroundColor' => '#7c3aed',
                    'tension'            => 0.25,
                    'fill'               => false,
                ],
                [
                    'label'              => 'Atrasadas (>24h)',
                    'data'               => $flowChart['overdue_backlog'] ?? [],
                    'borderColor'        => '#ef4444',
                    'backgroundColor'    => 'rgba(239,68,68,.2)',
                    'pointBackgroundColor' => '#ef4444',
                    'tension'            => 0.25,
                    'fill'               => false,
                ],
                [
                    'label'       => 'Média (acumulado)',
                    'data'        => array_fill(0, $labelCount, $lineMeanOpen),
                    'borderColor' => 'rgba(167,139,250,.9)',
                    'borderDash'  => [6, 5],
                    'pointRadius' => 0,
                    'fill'        => false,
                ],
                [
                    'label'       => 'Média (atrasadas)',
                    'data'        => array_fill(0, $labelCount, $lineMeanOver),
                    'borderColor' => 'rgba(248,113,113,.9)',
                    'borderDash'  => [6, 5],
                    'pointRadius' => 0,
                    'fill'        => false,
                ],
            ],
        ];

        $barChart = [
            'labels'   => $flowChart['labels'] ?? [],
            'datasets' => [
                [
                    'label'           => 'Demandas (solicitadas)',
                    'data'            => $flowChart['requested'] ?? [],
                    'backgroundColor' => 'rgba(59,130,246,.8)',
                    'borderColor'     => '#3b82f6',
                    'borderWidth'     => 1,
                ],
                [
                    'label'           => 'Saídas (concluídas)',
                    'data'            => $flowChart['delivered'] ?? [],
                    'backgroundColor' => 'rgba(16,185,129,.8)',
                    'borderColor'     => '#10b981',
                    'borderWidth'     => 1,
                ],
            ],
        ];

        $kpis = [
            'queue_total'   => $requestedTotal,
            'in_analysis'   => (int) ($summary['in_progress_now_count'] ?? 0),
            'returned'      => (int) ($queue['total'] ?? 0),
            'previous_done' => $deliveredTotal,
            'previous_ready' => $requestedTotal,
        ];

        return [
            'service_id'           => 'ads-dashboard',
            'service_name'         => 'ADS - Dashboard',
            'previous_service_id'  => null,
            'previous_service_name' => null,
            'cards'                => $kpis,
            'ads_chart'            => ['kind' => 'dashboard', 'title' => 'Dashboard ADS', 'labels' => [], 'datasets' => []],
            'ads_dashboard'        => [
                'top_cards' => [
                    ['label' => 'Abertas no período',        'value' => (string) $requestedTotal],
                    ['label' => 'Média de aberturas/dia',   'value' => number_format($openedDailyAvg, 2, ',', '.')],
                    ['label' => 'Média de entregas/dia',    'value' => number_format($deliveredDailyAvg, 2, ',', '.')],
                    ['label' => 'Tempo médio de entrega',   'value' => (string) ($summary['delivered_avg_label'] ?? '0')],
                    ['label' => 'Em execução agora',        'value' => (string) ((int) ($summary['in_progress_now_count'] ?? 0))],
                ],
                'middle_cards' => [
                    ['label' => 'Solicitadas',       'value' => $requestedTotal],
                    ['label' => 'Concluídas',        'value' => $deliveredTotal],
                    ['label' => 'Taxa de conclusão', 'value' => number_format((float) ($flowMonth['analytics']['completion_rate'] ?? 0), 1, ',', '.') . '%'],
                    ['label' => 'Abertas agora',     'value' => (int) ($flowMonth['analytics']['current_open'] ?? 0)],
                    ['label' => 'Atrasadas agora',   'value' => (int) ($flowMonth['analytics']['current_overdue'] ?? 0)],
                    ['label' => 'Média em aberto',   'value' => number_format((float) ($flowMonth['analytics']['backlog_avg'] ?? 0), 1, ',', '.')],
                    ['label' => 'Pico em aberto',    'value' => (int) ($flowMonth['analytics']['backlog_peak'] ?? 0)],
                    ['label' => 'Média atrasadas',   'value' => number_format((float) ($flowMonth['analytics']['overdue_avg'] ?? 0), 1, ',', '.')],
                ],
                'subtitle'    => sprintf(
                    'Período vigente: %s a %s | Gráficos: últimos 15 dias',
                    Carbon::parse($monthStart)->format('d/m'),
                    Carbon::parse($today)->format('d/m')
                ),
                'line_chart'  => $lineChart,
                'bar_chart'   => $barChart,
                'queue_donut' => [
                    'labels' => $queue['labels'] ?? [],
                    'values' => $queue['values'] ?? [],
                    'colors' => $queue['colors'] ?? [],
                    'total'  => (int) ($queue['total'] ?? 0),
                ],
                'reuse_donut' => [
                    'labels'     => ['Solicitações Reaproveitadas', 'Novas Solicitações'],
                    'values'     => [(int) ($reuse['reused'] ?? 0), (int) ($reuse['queued'] ?? 0)],
                    'colors'     => ['rgba(5,150,105,0.85)', 'rgba(59,130,246,0.8)'],
                    'total'      => (int) ($reuse['total'] ?? 0),
                    'reuse_rate' => (float) ($reuse['reuse_rate'] ?? 0),
                ],
            ],
            'queue_histogram'           => ['labels' => [], 'values' => []],
            'note_type_donut'           => ['labels' => ['Com produção', 'Sem produção'], 'values' => [0, 0], 'total' => 0, 'associated' => 0],
            'production_open_histogram' => ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
            'production_daily'          => ['labels' => [], 'assigned' => [], 'delivered' => []],
            'internal_return_donut'     => ['labels' => [], 'values' => []],
            'recent_completed'          => [],
            'week'                      => null,
        ];
    }
}
