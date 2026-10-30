<?php

namespace App\Services\Engineers;

/**
 * Configuração (só dados) dos gráficos do painel de Viabilidade. Usa o `my-panel.js` (criação e atualização in-place);
 * a ordem e a quantidade de datasets de cada gráfico é fixa.
 */
final class ViabilityDashboardCharts
{
    private const BLUE  = '#2563eb';
    private const GREEN = '#22c55e';
    private const RED   = '#ef4444';
    private const AMBER = '#f59e0b';
    private const SLATE = '#94a3b8';

    /**
     * @param array<string, mixed> $snapshot ViabilityDashboardAnalytics::snapshot()
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(array $snapshot): array
    {
        return [
            'flow'    => self::flow($snapshot['daily']),
            'outcome' => self::outcome($snapshot['summary']),
            'age'     => self::age($snapshot['age_buckets']),
        ];
    }

    /** @param array<string, mixed> $d */
    public static function flow(array $d): array
    {
        $line = ['borderWidth' => 2.5, 'pointRadius' => 0, 'pointHoverRadius' => 5, 'pointHoverBackgroundColor' => '#fff', 'pointHoverBorderWidth' => 2, 'cubicInterpolationMode' => 'monotone', 'fill' => true];

        return [
            'type' => 'line',
            'data' => [
                'labels'   => $d['labels'],
                'datasets' => [
                    $line + ['label' => 'Entradas (envios)', 'data' => $d['entered'], 'borderColor' => self::BLUE, 'gradient' => ['rgba(37,99,235,.34)', 'rgba(37,99,235,0)']],
                    $line + ['label' => 'Fechadas', 'data' => $d['closed'], 'borderColor' => self::GREEN, 'gradient' => ['rgba(34,197,94,.34)', 'rgba(34,197,94,0)']],
                ],
            ],
            'options' => self::options(['scales' => ['x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 10, 'autoSkip' => true]]]]),
        ];
    }

    /**
     * Rosca do resultado do período: realizadas, não realizadas (tácitas) e rejeitadas; total no centro.
     *
     * @param array<string, mixed> $s
     */
    public static function outcome(array $s): array
    {
        $total = $s['realized'] + $s['not_realized'] + $s['rejected'];

        return [
            'type' => 'doughnut',
            'data' => [
                'labels'   => ['Realizadas', 'Não realizadas', 'Rejeitadas'],
                'datasets' => [[
                    'label'           => 'Período',
                    'data'            => [$s['realized'], $s['not_realized'], $s['rejected']],
                    'backgroundColor' => [self::GREEN, self::RED, self::AMBER],
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
                    'mpCenter' => ['text' => (string) $total, 'sub' => $total === 1 ? 'viabilidade' : 'viabilidades'],
                ],
            ],
        ];
    }

    /** @param array{labels: array<int, string>, values: array<int, int>} $buckets */
    public static function age(array $buckets): array
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $buckets['labels'],
                'datasets' => [
                    ['label' => 'Em viabilidade', 'data' => $buckets['values'], 'gradient' => ['#60a5fa', '#1d4ed8'], 'borderRadius' => 8, 'maxBarThickness' => 44],
                ],
            ],
            'options' => self::options(['plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['grid' => ['display' => false]]]]),
        ];
    }

    /** @param array<string, mixed> $extra */
    public static function options(array $extra = []): array
    {
        return array_replace_recursive([
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'interaction'         => ['mode' => 'index', 'intersect' => false],
            'plugins'             => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]]],
            'scales'              => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['color' => '#e8eef2'], 'border' => ['display' => false]]],
        ], $extra);
    }
}
