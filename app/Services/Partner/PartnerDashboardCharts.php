<?php

namespace App\Services\Partner;

/**
 * Configuração (só dados) dos gráficos do Dashboard da Parceira. Usa o `my-panel.js` da Home (criação e atualização in-place), então a
 * quantidade e a ordem dos datasets de cada gráfico é fixa. Os valores são escritos pelo plugin `mpBarLabels`, não por datalabels.
 */
final class PartnerDashboardCharts
{
    private const BLUE  = ['#60a5fa', '#2563eb'];
    private const RED   = ['#f87171', '#dc2626'];
    private const TEAL  = ['#2dd4bf', '#0f766e'];
    private const AMBER = ['#fbbf24', '#d97706'];

    /** Cor de cada faixa de atraso de ADS (da mais leve à mais grave). */
    private const AGING_COLORS = ['#f59e0b', '#f59e0b', '#ef4444', '#ef4444', '#7f1d1d'];

    /**
     * @param array<string, mixed> $executive    Main::buildExecutiveData()
     * @param array<string, mixed> $viabilityAge Main::getOpenViabilityAgeHistogram()
     * @param array<string, mixed> $d5Age        Main::getOpenD5AgeHistogram()
     * @param array<string, mixed> $reasons      labels/data dos motivos de informes rejeitados
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(array $executive, array $viabilityAge, array $d5Age, array $reasons): array
    {
        return [
            'aging'        => self::aging($executive['aging'] ?? []),
            'due'          => self::due($executive['due_by_day'] ?? []),
            'informs'      => self::informs($executive),
            'viabilityAge' => self::viabilityAge($viabilityAge),
            'd5Age'        => self::d5Age($d5Age),
            'reasons'      => self::reasons($reasons),
        ];
    }

    /** @param array<int, array{label: string, total: int}> $buckets */
    public static function aging(array $buckets): array
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels'   => array_column($buckets, 'label'),
                'datasets' => [[
                    'label'           => 'ADS em atraso',
                    'data'            => array_map('intval', array_column($buckets, 'total')),
                    'backgroundColor' => array_slice(self::AGING_COLORS, 0, count($buckets)),
                    'borderRadius'    => 6,
                    'maxBarThickness' => 28,
                ]],
            ],
            'options' => self::options(['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false], 'mpBarLabels' => ['outside' => true]]], horizontal: true),
        ];
    }

    /** @param array<int, array{label: string, ads: int, viability: int}> $days */
    public static function due(array $days): array
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels'   => array_column($days, 'label'),
                'datasets' => [
                    ['label' => 'Entregas de ADS', 'data' => array_map('intval', array_column($days, 'ads')), 'gradient' => self::AMBER, 'borderRadius' => 6, 'maxBarThickness' => 46, 'stack' => 'due'],
                    ['label' => 'Viabilidades', 'data' => array_map('intval', array_column($days, 'viability')), 'gradient' => self::BLUE, 'borderRadius' => 6, 'maxBarThickness' => 46, 'stack' => 'due'],
                ],
            ],
            'options' => self::options(['scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true]], 'plugins' => ['mpBarLabels' => ['segments' => true, 'totals' => true]]]),
        ];
    }

    /** @param array<string, mixed> $e */
    public static function informs(array $e): array
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $e['labels'] ?? [],
                'datasets' => [
                    ['label' => 'Válidos', 'data' => array_map('intval', $e['informs_valid'] ?? []), 'gradient' => self::TEAL, 'borderRadius' => 5, 'maxBarThickness' => 26, 'stack' => 'informs'],
                    ['label' => 'Cancelados/rejeitados', 'data' => array_map('intval', $e['informs_invalid'] ?? []), 'gradient' => self::RED, 'borderRadius' => 5, 'maxBarThickness' => 26, 'stack' => 'informs'],
                ],
            ],
            'options' => self::options(['scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true]], 'plugins' => ['mpBarLabels' => ['totals' => true]]]),
        ];
    }

    /** @param array<string, mixed> $d */
    public static function viabilityAge(array $d): array
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $d['labels'] ?? [],
                'datasets' => [
                    ['label' => 'Viabilidades em aberto', 'data' => array_map('intval', $d['data'] ?? []), 'gradient' => self::TEAL, 'borderRadius' => 5, 'maxBarThickness' => 34],
                ],
            ],
            'options' => self::options([
                'scales'  => ['x' => ['title' => ['display' => true, 'text' => 'Dias desde o envio']]],
                'plugins' => ['legend' => ['display' => false], 'mpBarLabels' => ['outside' => true]],
            ]),
        ];
    }

    /** @param array<string, mixed> $d */
    public static function d5Age(array $d): array
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $d['labels'] ?? [],
                'datasets' => [
                    ['label' => 'Em espera', 'data' => array_map('intval', $d['data'] ?? []), 'gradient' => self::BLUE, 'borderRadius' => 4, 'maxBarThickness' => 30, 'stack' => 'd5'],
                    ['label' => 'Rejeitados', 'data' => array_map('intval', $d['rejected_data'] ?? []), 'gradient' => self::RED, 'borderRadius' => 4, 'maxBarThickness' => 30, 'stack' => 'd5'],
                ],
            ],
            'options' => self::options([
                'scales'  => ['x' => ['stacked' => true, 'title' => ['display' => true, 'text' => 'Dias desde o despacho']], 'y' => ['stacked' => true]],
                'plugins' => ['mpBarLabels' => ['segments' => true, 'totals' => true]],
            ]),
        ];
    }

    /** @param array<string, mixed> $d */
    public static function reasons(array $d): array
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $d['labels'] ?? [],
                'datasets' => [
                    ['label' => 'Informes rejeitados', 'data' => array_map('intval', $d['data'] ?? []), 'gradient' => self::RED, 'borderRadius' => 6, 'maxBarThickness' => 26],
                ],
            ],
            'options' => self::options(['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false], 'mpBarLabels' => ['outside' => true]]], horizontal: true),
        ];
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private static function options(array $extra = [], bool $horizontal = false): array
    {
        $value = ['beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['color' => '#e8eef2'], 'border' => ['display' => false]];
        $label = ['grid' => ['display' => false]];

        return array_replace_recursive([
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'interaction'         => ['mode' => 'index', 'intersect' => false],
            'layout'              => ['padding' => ['top' => 18, 'right' => $horizontal ? 28 : 4]],
            'plugins'             => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]]],
            'scales'              => $horizontal ? ['x' => $value, 'y' => $label] : ['x' => $label, 'y' => $value],
        ], $extra);
    }
}
