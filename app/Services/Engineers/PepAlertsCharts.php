<?php

namespace App\Services\Engineers;

/**
 * Configuração (só dados) dos gráficos de "Informes sem saldo no PEP". Usa o mesmo `my-panel.js` do Meu painel (criação e
 * atualização in-place); a ordem e a quantidade de datasets de cada gráfico é fixa.
 */
final class PepAlertsCharts
{
    private const RED   = '#ef4444';
    private const AMBER = '#f59e0b';
    private const GREEN = '#22c55e';
    private const BLUE  = '#2563eb';

    private const REASON_COLORS = ['danger' => self::RED, 'warning' => self::AMBER];

    /**
     * @param array<string, mixed> $snapshot PepAlertsAnalytics::snapshot()
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(array $snapshot): array
    {
        return [
            'flow'   => self::flow($snapshot['daily']),
            'reason' => self::reason($snapshot['by_reason'], $snapshot['summary']['open']),
            'age'    => self::age($snapshot['age_buckets']),
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
                    $line + ['label' => 'Entraram na lista', 'data' => $d['entered'], 'borderColor' => self::RED, 'gradient' => ['rgba(239,68,68,.34)', 'rgba(239,68,68,0)']],
                    $line + ['label' => 'Tratados', 'data' => $d['handled'], 'borderColor' => self::GREEN, 'gradient' => ['rgba(34,197,94,.34)', 'rgba(34,197,94,0)']],
                ],
            ],
            'options' => self::options(['scales' => ['x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 10, 'autoSkip' => true]]]]),
        ];
    }

    /**
     * Rosca da pilha por motivo, com o total no centro.
     *
     * @param array<int, array{label: string, color: string, total: int}> $reasons
     */
    public static function reason(array $reasons, int $open): array
    {
        return [
            'type' => 'doughnut',
            'data' => [
                'labels'   => array_column($reasons, 'label'),
                'datasets' => [[
                    'label'           => 'Pendentes',
                    'data'            => array_column($reasons, 'total'),
                    'backgroundColor' => array_map(fn (array $r) => self::REASON_COLORS[$r['color']] ?? '#94a3b8', $reasons),
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
                    'mpCenter' => ['text' => (string) $open, 'sub' => $open === 1 ? 'pendente' : 'pendentes'],
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
                    ['label' => 'Pendentes', 'data' => $buckets['values'], 'gradient' => ['#fbbf24', '#b45309'], 'borderRadius' => 8, 'maxBarThickness' => 44],
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
