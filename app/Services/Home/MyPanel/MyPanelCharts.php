<?php

namespace App\Services\Home\MyPanel;

/**
 * Configuração (só dados, sem funções JS) dos gráficos do "Meu painel". O JS (`resources/js/home/my-panel.js`) cria e atualiza
 * cada gráfico in-place; a ordem e a quantidade de datasets de cada gráfico é fixa para que a atualização preserve o estado.
 *
 * Extensões lidas pelo JS (não existem no Chart.js): `gradient: [corTopo, corBase]` no dataset (preenchimento em degradê vertical,
 * ou horizontal em barras `indexAxis: y`) e `plugins.mpCenter: {text, sub}` nas roscas (total no centro).
 */
final class MyPanelCharts
{
    private const BLUE  = '#2563eb';
    private const GREEN = '#22c55e';
    private const AMBER = '#f59e0b';
    private const SLATE = '#94a3b8';

    private const GROUP_GRADIENTS = [
        'project'           => ['#1d4ed8', '#60a5fa'],
        'construction'      => ['#0f766e', '#2dd4bf'],
        'post_construction' => ['#7c3aed', '#c4b5fd'],
        'other'             => ['#64748b', '#cbd5e1'],
    ];

    private const STATUS_COLORS = ['primary' => '#2563eb', 'danger' => '#ef4444', 'warning' => '#f59e0b', 'success' => '#22c55e', 'secondary' => '#94a3b8', 'info' => '#0ea5e9'];

    /**
     * @param array<string, mixed> $snapshot MyPanelAnalytics::snapshot()
     *
     * @return array<string, array<string, mixed>> id do gráfico => config
     */
    public static function all(array $snapshot): array
    {
        return [
            'daily'        => self::daily($snapshot['daily']),
            'week'         => self::week($snapshot['week']),
            'monthly'      => self::monthly($snapshot['monthly']),
            'situation'    => self::situation($snapshot['status'], $snapshot['summary']['open']),
            'productivity' => self::productivity($snapshot['activities']),
        ];
    }

    /** @param array<string, mixed> $d */
    public static function daily(array $d): array
    {
        $line = ['borderWidth' => 2.5, 'pointRadius' => 0, 'pointHoverRadius' => 5, 'pointHoverBackgroundColor' => '#fff', 'pointHoverBorderWidth' => 2, 'cubicInterpolationMode' => 'monotone', 'fill' => true];

        return [
            'type' => 'line',
            'data' => [
                'labels'   => $d['labels'],
                'datasets' => [
                    $line + ['label' => 'Recebidas (ATT)', 'data' => $d['received'], 'borderColor' => self::BLUE, 'gradient' => ['rgba(37,99,235,.38)', 'rgba(37,99,235,0)']],
                    $line + ['label' => 'Concluídas', 'data' => $d['completed'], 'borderColor' => self::GREEN, 'gradient' => ['rgba(34,197,94,.38)', 'rgba(34,197,94,0)']],
                ],
            ],
            'options' => self::options(['scales' => ['x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 10, 'autoSkip' => true]]]]),
        ];
    }

    /** @param array<string, mixed> $w */
    public static function week(array $w): array
    {
        $team = array_fill(0, count($w['labels']), $w['team']);

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $w['labels'],
                'datasets' => [
                    ['type' => 'bar', 'label' => 'Concluídas por dia', 'data' => $w['completed'], 'gradient' => ['#60a5fa', '#1d4ed8'], 'borderRadius' => 8, 'maxBarThickness' => 30, 'order' => 2],
                    ['type' => 'line', 'label' => 'Média da equipe (pessoa/dia)', 'data' => $team, 'borderColor' => self::AMBER, 'borderWidth' => 2, 'borderDash' => [6, 4], 'pointRadius' => 0, 'fill' => false, 'order' => 1],
                ],
            ],
            'options' => self::options(['scales' => ['x' => ['grid' => ['display' => false]]]]),
        ];
    }

    /** @param array<string, mixed> $m */
    public static function monthly(array $m): array
    {
        $datasets = [];

        foreach (MyPanelAnalytics::GROUP_LABELS as $group => $label) {
            $datasets[] = ['label' => $label, 'data' => $m['series'][$group], 'gradient' => self::GROUP_GRADIENTS[$group], 'borderRadius' => 4, 'maxBarThickness' => 40, 'stack' => 'notas'];
        }

        return [
            'type'    => 'bar',
            'data'    => ['labels' => $m['labels'], 'datasets' => $datasets],
            'options' => self::options(['scales' => ['x' => ['stacked' => true, 'grid' => ['display' => false]], 'y' => ['stacked' => true]]]),
        ];
    }

    /**
     * Rosca das abertas por situação, com o total no centro.
     *
     * @param array<int, array{label: string, color: string, total: int}> $status
     */
    public static function situation(array $status, int $open): array
    {
        return [
            'type' => 'doughnut',
            'data' => [
                'labels'   => array_column($status, 'label'),
                'datasets' => [[
                    'label'           => 'Abertas',
                    'data'            => array_column($status, 'total'),
                    'backgroundColor' => array_map(fn (array $s) => self::STATUS_COLORS[$s['color']] ?? self::SLATE, $status),
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
                    'mpCenter' => ['text' => (string) $open, 'sub' => $open === 1 ? 'aberta' : 'abertas'],
                ],
            ],
        ];
    }

    /**
     * Notas por dia trabalhado, por atividade: você × média da equipe.
     *
     * @param array<int, array<string, mixed>> $activities
     */
    public static function productivity(array $activities): array
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels'   => array_column($activities, 'service'),
                'datasets' => [
                    ['label' => 'Você (notas/dia)', 'data' => array_column($activities, 'per_day'), 'gradient' => ['#1d4ed8', '#38bdf8'], 'borderRadius' => 8, 'maxBarThickness' => 22],
                    ['label' => 'Média da equipe', 'data' => array_column($activities, 'team_per_day'), 'backgroundColor' => '#cbd5e1', 'borderRadius' => 8, 'maxBarThickness' => 22],
                ],
            ],
            'options' => self::options(['indexAxis' => 'y', 'scales' => ['x' => ['beginAtZero' => true, 'grid' => ['color' => '#e8eef2']], 'y' => ['grid' => ['display' => false]]]]),
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
