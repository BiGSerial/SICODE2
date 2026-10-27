<?php

namespace App\Services\Indicators;

use App\Models\OvPhaseDuration;
use App\Services\Home\MyPanel\MyPanelCharts;

/**
 * Configuração Chart.js da tela "Prazo de Projetos" (só dados; o `my-panel.js` cria e atualiza in-place).
 * Quantidade e ordem dos datasets são fixas por gráfico: faixas (4 barras) + % de aderência; TMEP (2 barras) + total + meta; empresas (2).
 */
class ProjectDeadlineCharts
{
    /** Empresas no gráfico (a tabela traz todas). */
    public const TOP = 10;

    private const MONTHS = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

    /** de → para (degradê vertical). */
    private const BANDS = [
        ['#4ade80', '#16a34a'],
        ['#cbd5e1', '#94a3b8'],
        ['#1e6f7d', '#0f4c5c'],
        ['#f87171', '#dc2626'],
    ];

    /** @return array<string,array<string,mixed>> */
    public static function all(array $report): array
    {
        return [
            'survey'    => self::phase($report['phases'][OvPhaseDuration::SURVEY]),
            'design'    => self::phase($report['phases'][OvPhaseDuration::DESIGN]),
            'tmep'      => self::tmep($report['tmep']),
            'companies' => self::companies($report['companies']),
        ];
    }

    public static function monthLabel(string $ym): string
    {
        [$year, $month] = explode('-', $ym);

        return self::MONTHS[(int) $month - 1] . '-' . substr($year, 2);
    }

    /** @return array<string,mixed> */
    private static function phase(array $phase): array
    {
        $months   = $phase['months'];
        $datasets = [];

        foreach ($phase['labels'] as $i => $label) {
            $datasets[] = [
                'type'       => 'bar',
                'label'      => $label,
                'data'       => array_map(fn ($m) => $m['bands'][$i], $months),
                'gradient'   => self::BANDS[$i],
                'stack'      => 'faixas',
                'yAxisID'    => 'y',
                'labelColor' => $i === 1 ? '#0f172a' : '#ffffff',
                'order'      => 2,
            ];
        }

        $datasets[] = [
            'type'            => 'line',
            'label'           => '% aderência',
            'data'            => array_column($months, 'adherence'),
            'borderColor'     => '#1d4ed8',
            'backgroundColor' => '#1d4ed8',
            'borderWidth'     => 2.5,
            'pointRadius'     => 4,
            'tension'         => 0.25,
            'yAxisID'         => 'y1',
            'order'           => 1,
        ];

        return [
            'type' => 'bar',
            // Rótulo em duas linhas: o mês e, embaixo, a % no prazo (a faixa de aderência do painel original).
            'data'    => ['labels' => array_map(fn ($m) => [self::monthLabel($m['month']), number_format($m['adherence'], 1, ',', '.') . '%'], $months), 'datasets' => $datasets],
            'options' => MyPanelCharts::options([
                'layout'  => ['padding' => ['top' => 26]],
                'plugins' => ['mpBarLabels' => ['segments' => true, 'totals' => true]],
                'scales'  => [
                    'x'  => ['stacked' => true, 'grid' => ['display' => false]],
                    'y'  => ['stacked' => true, 'title' => ['display' => true, 'text' => 'OVs']],
                    'y1' => ['position' => 'right', 'min' => 0, 'max' => 100, 'grid' => ['drawOnChartArea' => false], 'title' => ['display' => true, 'text' => '% aderência']],
                ],
            ]),
        ];
    }

    /** @return array<string,mixed> */
    private static function tmep(array $tmep): array
    {
        $months = $tmep['months'];

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => array_map(fn ($m) => self::monthLabel($m['month']), $months),
                'datasets' => [
                    ['type' => 'bar', 'label' => 'Levantamento', 'data' => array_column($months, 'survey'), 'gradient' => ['#60a5fa', '#1d4ed8'], 'stack' => 'tmep', 'order' => 3],
                    ['type' => 'bar', 'label' => 'Desenho', 'data' => array_column($months, 'design'), 'gradient' => ['#2dd4bf', '#0f766e'], 'stack' => 'tmep', 'order' => 3],
                    ['type' => 'line', 'label' => 'TMEP', 'data' => array_column($months, 'total'), 'borderColor' => '#0f172a', 'backgroundColor' => '#0f172a', 'borderWidth' => 2.5, 'pointRadius' => 4, 'tension' => 0.25, 'showValues' => true, 'decimals' => 2, 'stack' => 'tmep-total', 'order' => 1],
                    ['type' => 'line', 'label' => 'Meta ' . $tmep['target'] . ' dias', 'data' => array_fill(0, count($months), $tmep['target']), 'borderColor' => '#dc2626', 'backgroundColor' => '#dc2626', 'borderWidth' => 2, 'borderDash' => [8, 6], 'pointRadius' => 0, 'stack' => 'tmep-meta', 'order' => 2],
                ],
            ],
            'options' => MyPanelCharts::options([
                'layout'  => ['padding' => ['top' => 22]],
                'plugins' => ['mpBarLabels' => ['segments' => true, 'totals' => false, 'decimals' => 1]],
                'scales'  => [
                    'x' => ['stacked' => true, 'grid' => ['display' => false]],
                    'y' => ['stacked' => true, 'ticks' => ['precision' => 0], 'title' => ['display' => true, 'text' => 'Dias']],
                ],
            ]),
        ];
    }

    /** @return array<string,mixed> */
    private static function companies(array $companies): array
    {
        $survey = OvPhaseDuration::SURVEY;
        $design = OvPhaseDuration::DESIGN;

        $top = collect($companies)
            ->sortByDesc(fn ($c) => ($c[$survey]['total'] ?? 0) + ($c[$design]['total'] ?? 0))
            ->take(self::TOP)
            ->values();

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $top->pluck('name')->all(),
                'datasets' => [
                    ['label' => 'Levantamento', 'data' => $top->map(fn ($c) => $c[$survey]['adherence'] ?? 0)->all(), 'gradient' => ['#60a5fa', '#1d4ed8']],
                    ['label' => 'Desenho', 'data' => $top->map(fn ($c) => $c[$design]['adherence'] ?? 0)->all(), 'gradient' => ['#2dd4bf', '#0f766e']],
                ],
            ],
            'options' => MyPanelCharts::options([
                'indexAxis' => 'y',
                'scales'    => [
                    'x' => ['beginAtZero' => true, 'max' => 100, 'ticks' => ['precision' => 0], 'title' => ['display' => true, 'text' => '% aderência']],
                    'y' => ['grid' => ['display' => false]],
                ],
            ]),
        ];
    }
}
