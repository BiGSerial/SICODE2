<?php

namespace App\Services\Engineers;

use App\Services\Home\MyPanel\MyPanelCharts;

/**
 * Configuração (só dados) dos gráficos do "Painel de informes", no contrato do `my-panel.js` (criação e atualização in-place):
 * quantidade e ordem dos datasets de cada gráfico são fixas; só mudam rótulos e valores. Rankings mostram o top 10.
 */
final class InformDashboardCharts
{
    /** Cor de cada situação da ADS, na ordem de InformDashboardAnalytics::BUCKETS. */
    public const BUCKET_COLORS = ['#22c55e', '#f59e0b', '#ef4444', '#38bdf8', '#64748b', '#a78bfa'];

    /**
     * @param array<string, mixed> $snapshot InformDashboardAnalytics::assemble()
     *
     * @return array<string, array<string, mixed>> id do gráfico => config
     */
    public static function all(array $snapshot): array
    {
        return [
            'evolution' => self::evolution($snapshot['series']),
            'ads'       => self::ads($snapshot['summary']['buckets']),
            'companies' => self::companies($snapshot['companies']),
            'reasons'   => self::reasons($snapshot['reasons']),
        ];
    }

    /** @param array<string, mixed> $s */
    public static function evolution(array $s): array
    {
        $thin = ['borderWidth' => 2, 'pointRadius' => 0, 'pointHoverRadius' => 4, 'cubicInterpolationMode' => 'monotone', 'fill' => false];

        return [
            'type' => 'line',
            'data' => [
                'labels'   => $s['labels'],
                'datasets' => [
                    ['label' => 'Informes recebidos', 'data' => $s['received'], 'borderColor' => '#1d4ed8', 'borderWidth' => 2.5, 'pointRadius' => 0, 'pointHoverRadius' => 5, 'pointHoverBackgroundColor' => '#fff', 'pointHoverBorderWidth' => 2, 'cubicInterpolationMode' => 'monotone', 'fill' => true, 'gradient' => ['rgba(29,78,216,.34)', 'rgba(29,78,216,0)']],
                    $thin + ['label' => 'ADS em atraso ou vencida', 'data' => $s['late'], 'borderColor' => '#ef4444'],
                    $thin + ['label' => 'Devolvidos', 'data' => $s['returned'], 'borderColor' => '#f59e0b'],
                ],
            ],
            'options' => MyPanelCharts::options(['scales' => ['x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 12, 'autoSkip' => true]]]]),
        ];
    }

    /** @param array<string, int> $buckets */
    public static function ads(array $buckets): array
    {
        $total = array_sum($buckets);

        return [
            'type' => 'doughnut',
            'data' => [
                'labels'   => array_values(InformDashboardAnalytics::BUCKETS),
                'datasets' => [[
                    'label'           => 'Informes',
                    'data'            => array_values($buckets),
                    'backgroundColor' => self::BUCKET_COLORS,
                    'borderWidth'     => 3,
                    'borderColor'     => '#ffffff',
                    'hoverOffset'     => 8,
                ]],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'cutout'              => '68%',
                'plugins'             => [
                    'legend'   => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]],
                    'mpCenter' => ['text' => number_format($total, 0, ',', '.'), 'sub' => $total === 1 ? 'informe' : 'informes'],
                ],
            ],
        ];
    }

    /**
     * Empreiteiras com mais informes, empilhadas pela situação da ADS (mesmas cores da rosca).
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public static function companies(array $rows): array
    {
        $top = array_slice($rows, 0, InformDashboardAnalytics::TOP);
        $set = fn (string $label, string $key, string $color) => ['label' => $label, 'data' => array_column($top, $key), 'backgroundColor' => $color, 'borderRadius' => 4, 'maxBarThickness' => 20];

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => array_column($top, 'name'),
                'datasets' => [
                    $set('Entregue no prazo', 'on_time', self::BUCKET_COLORS[0]),
                    $set('Entregue em atraso', 'late', self::BUCKET_COLORS[1]),
                    $set('Vencida sem entrega', 'overdue', self::BUCKET_COLORS[2]),
                    $set('Em aberto, no prazo', 'open', self::BUCKET_COLORS[3]),
                ],
            ],
            'options' => MyPanelCharts::options([
                'indexAxis' => 'y',
                'scales'    => [
                    'x' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['color' => '#e8eef2']],
                    'y' => ['stacked' => true, 'grid' => ['display' => false]],
                ],
            ]),
        ];
    }

    /** @param array<int, array{reason: string, total: int}> $rows */
    public static function reasons(array $rows): array
    {
        $top = array_slice($rows, 0, InformDashboardAnalytics::TOP);

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => array_column($top, 'reason'),
                'datasets' => [
                    ['label' => 'Devoluções', 'data' => array_column($top, 'total'), 'gradient' => ['#b45309', '#fbbf24'], 'borderRadius' => 8, 'maxBarThickness' => 20],
                ],
            ],
            'options' => MyPanelCharts::options([
                'indexAxis' => 'y',
                'scales'    => [
                    'x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['color' => '#e8eef2']],
                    'y' => ['grid' => ['display' => false]],
                ],
            ]),
        ];
    }
}
