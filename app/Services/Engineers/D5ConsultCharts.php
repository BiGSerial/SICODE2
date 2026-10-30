<?php

namespace App\Services\Engineers;

use App\Services\D5\D5StageResolver;

/**
 * Configuração (só dados) dos gráficos da "Consulta geral D5". Usa o mesmo `my-panel.js` do Meu painel (criação e
 * atualização in-place); a ordem e a quantidade de datasets de cada gráfico é fixa.
 */
final class D5ConsultCharts
{
    private const RED   = '#ef4444';
    private const AMBER = '#f59e0b';
    private const GREEN = '#22c55e';
    private const BLUE  = '#2563eb';
    private const TEAL  = '#0d9488';

    private const STAGE_COLORS = [
        D5StageResolver::SUPPLIER      => self::RED,
        D5StageResolver::FISCALIZATION => self::AMBER,
        D5StageResolver::PAYMENT       => self::BLUE,
        D5StageResolver::FINALIZED     => self::GREEN,
    ];

    /**
     * @param array<string, mixed> $snapshot D5ConsultAnalytics::snapshot()
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(array $snapshot, bool $withFinalized = true): array
    {
        return [
            'stage' => self::stage($snapshot['stages'], $snapshot['summary']['finalized'], $snapshot['summary']['open'], $withFinalized),
            'flow'  => self::flow($snapshot['flow']),
            'age'   => self::age($snapshot['age_buckets']),
        ];
    }

    /**
     * Rosca por etapa, com o total em andamento no centro. As etapas sempre aparecem, nesta ordem (Finalizado só quando a tela o mostra).
     *
     * @param array<string, array{label: string, total: int}> $stages
     */
    public static function stage(array $stages, int $finalized, int $open, bool $withFinalized = true): array
    {
        $rows = [];

        foreach (D5ConsultAnalytics::OPEN_STAGES as $key) {
            $rows[$key] = ['label' => $stages[$key]['label'], 'total' => $stages[$key]['total']];
        }

        if ($withFinalized) {
            $rows[D5StageResolver::FINALIZED] = ['label' => D5ConsultAnalytics::STAGE_LABELS[D5StageResolver::FINALIZED], 'total' => $finalized];
        }

        return [
            'type' => 'doughnut',
            'data' => [
                'labels'   => array_column($rows, 'label'),
                'datasets' => [[
                    'label'           => 'D5',
                    'data'            => array_column($rows, 'total'),
                    'backgroundColor' => array_map(fn (string $k) => self::STAGE_COLORS[$k], array_keys($rows)),
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
                    'mpCenter' => ['text' => (string) $open, 'sub' => 'em andamento'],
                ],
            ],
        ];
    }

    /** @param array{labels: array<int, string>, sent: array<int, int>, returned: array<int, int>} $d */
    public static function flow(array $d): array
    {
        $line = ['borderWidth' => 2.5, 'pointRadius' => 3, 'pointHoverRadius' => 6, 'pointHoverBackgroundColor' => '#fff', 'pointHoverBorderWidth' => 2, 'cubicInterpolationMode' => 'monotone', 'fill' => true];

        return [
            'type' => 'line',
            'data' => [
                'labels'   => $d['labels'],
                'datasets' => [
                    $line + ['label' => 'Despachadas', 'data' => $d['sent'], 'borderColor' => self::BLUE, 'gradient' => ['rgba(37,99,235,.34)', 'rgba(37,99,235,0)']],
                    $line + ['label' => 'Retornadas pela empreiteira', 'data' => $d['returned'], 'borderColor' => self::TEAL, 'gradient' => ['rgba(13,148,136,.34)', 'rgba(13,148,136,0)']],
                ],
            ],
            'options' => self::options(['scales' => ['x' => ['grid' => ['display' => false]]]]),
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
                    ['label' => 'D5 em andamento', 'data' => $buckets['values'], 'gradient' => ['#fbbf24', '#b45309'], 'borderRadius' => 8, 'maxBarThickness' => 44],
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
