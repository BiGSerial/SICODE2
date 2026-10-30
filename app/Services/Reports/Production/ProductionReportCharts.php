<?php

namespace App\Services\Reports\Production;

use App\Services\Home\MyPanel\MyPanelCharts;

/**
 * Configuração (só dados) dos gráficos do "Relatório de Produção", no contrato do `my-panel.js` (criação e atualização in-place):
 * quantidade e ordem dos datasets de cada gráfico são fixas; só mudam rótulos e valores. Rankings mostram o top 10; a tabela e o Excel trazem todos.
 */
final class ProductionReportCharts
{
    public const TOP = 10;

    private const SLATE = '#cbd5e1';

    /**
     * @param array<string, mixed> $snapshot ProductionReportAnalytics::assemble()
     *
     * @return array<string, array<string, mixed>> id do gráfico => config
     */
    public static function all(array $snapshot): array
    {
        return [
            'evolution'   => self::evolution($snapshot['series']),
            'flags'       => self::flags($snapshot['flags'] ?: ['doe_only' => 0, 'ma_only' => 0, 'both' => 0, 'none' => 0]),
            'conclusions' => self::conclusions($snapshot['conclusions']),
            'services'    => self::services($snapshot['services']),
            'users'       => self::users($snapshot['users']),
        ];
    }

    /** @param array<string, mixed> $s */
    public static function evolution(array $s): array
    {
        $line = ['borderWidth' => 2.5, 'pointRadius' => 0, 'pointHoverRadius' => 5, 'pointHoverBackgroundColor' => '#fff', 'pointHoverBorderWidth' => 2, 'cubicInterpolationMode' => 'monotone', 'fill' => true];

        return [
            'type' => 'line',
            'data' => [
                'labels'   => $s['labels'],
                'datasets' => [
                    $line + ['label' => 'Concluídas', 'data' => $s['done'], 'borderColor' => '#22c55e', 'gradient' => ['rgba(34,197,94,.38)', 'rgba(34,197,94,0)']],
                    ['label' => 'Dependem de órgão externo', 'data' => $s['doe'], 'borderColor' => '#f59e0b', 'borderWidth' => 2, 'pointRadius' => 0, 'pointHoverRadius' => 4, 'cubicInterpolationMode' => 'monotone', 'fill' => false],
                    ['label' => 'Meio ambiente', 'data' => $s['ma'], 'borderColor' => '#0ea5e9', 'borderWidth' => 2, 'pointRadius' => 0, 'pointHoverRadius' => 4, 'cubicInterpolationMode' => 'monotone', 'fill' => false],
                ],
            ],
            'options' => MyPanelCharts::options(['scales' => ['x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 12, 'autoSkip' => true]]]]),
        ];
    }

    /**
     * Rosca: cada linha cai em um único grupo (só órgão externo, só meio ambiente, os dois ou nenhum).
     *
     * @param array{doe_only: int, ma_only: int, both: int, none: int} $f
     */
    public static function flags(array $f): array
    {
        $total = array_sum($f);

        return [
            'type' => 'doughnut',
            'data' => [
                'labels'   => ['Órgão externo', 'Meio ambiente', 'Órgão externo e meio ambiente', 'Sem dependência'],
                'datasets' => [[
                    'label'           => 'Registros',
                    'data'            => [$f['doe_only'], $f['ma_only'], $f['both'], $f['none']],
                    'backgroundColor' => ['#f59e0b', '#0ea5e9', '#7c3aed', '#cbd5e1'],
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
                    'mpCenter' => ['text' => number_format($total, 0, ',', '.'), 'sub' => $total === 1 ? 'registro' : 'registros'],
                ],
            ],
        ];
    }

    /** @param array<int, array<string, mixed>> $rows */
    public static function conclusions(array $rows): array
    {
        $top = array_slice($rows, 0, self::TOP);

        return self::horizontal(array_column($top, 'conclusion'), [
            ['label' => 'Registros', 'data' => array_column($top, 'total'), 'gradient' => ['#1d4ed8', '#38bdf8'], 'borderRadius' => 8, 'maxBarThickness' => 20],
        ]);
    }

    /** @param array<int, array<string, mixed>> $rows */
    public static function services(array $rows): array
    {
        $top = array_slice($rows, 0, self::TOP);

        return self::horizontal(array_column($top, 'service'), [
            ['label' => 'Concluídas', 'data' => array_column($top, 'done'), 'gradient' => ['#0f766e', '#2dd4bf'], 'borderRadius' => 8, 'maxBarThickness' => 20],
            ['label' => 'Em aberto', 'data' => array_column($top, 'open'), 'backgroundColor' => self::SLATE, 'borderRadius' => 8, 'maxBarThickness' => 20],
        ]);
    }

    /** @param array<int, array<string, mixed>> $rows */
    public static function users(array $rows): array
    {
        $top = array_slice($rows, 0, self::TOP);

        return self::horizontal(array_column($top, 'name'), [
            ['label' => 'Concluídas', 'data' => array_column($top, 'done'), 'gradient' => ['#1d4ed8', '#38bdf8'], 'borderRadius' => 8, 'maxBarThickness' => 20],
            ['label' => 'Em aberto', 'data' => array_column($top, 'open'), 'backgroundColor' => self::SLATE, 'borderRadius' => 8, 'maxBarThickness' => 20],
        ]);
    }

    /**
     * @param array<int, string>               $labels
     * @param array<int, array<string, mixed>> $datasets
     */
    private static function horizontal(array $labels, array $datasets): array
    {
        return [
            'type'    => 'bar',
            'data'    => ['labels' => $labels, 'datasets' => $datasets],
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
