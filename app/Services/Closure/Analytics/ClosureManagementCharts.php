<?php

namespace App\Services\Closure\Analytics;

use App\Enum\ClosureOperationalStatus as Status;
use App\Services\Home\MyPanel\MyPanelCharts;

/**
 * Configuração (só dados) dos gráficos do painel gerencial do Encerramento, no contrato do `my-panel.js`: quantidade e ordem dos
 * datasets são FIXAS por gráfico (a atualização é in-place) e o JS aplica `gradient`, `mpBarLabels` e `mpCenter`.
 * Os números vêm de `ClosureManagementAnalytics` (e, na parte operacional, de `ClosureDispatcherAnalytics`): nada é recalculado aqui.
 */
final class ClosureManagementCharts
{
    private const GREEN = ['#16a34a', '#4ade80'];
    private const BLUE  = ['#1d4ed8', '#60a5fa'];
    private const SLATE = ['#94a3b8', '#cbd5e1'];
    private const TEAL  = ['#0f766e', '#2dd4bf'];
    private const AMBER = ['#d97706', '#fbbf24'];
    private const RED   = ['#dc2626', '#f87171'];

    private const TYPE_GRADIENTS = ['P' => self::RED, 'M' => self::TEAL, 'F' => self::BLUE, 'N' => self::SLATE];

    private const STATUS_COLORS = ['#263CC8', '#A8B1E9', '#212E3E', '#F7D200', '#E32C2C', '#0F8A77', '#225E66', '#0CD3F8', '#91AFB3'];

    /** Quantos itens os gráficos de ranking mostram (a tabela e o Excel trazem todos). */
    public const TOP = 10;

    /**
     * @param array<string, mixed> $management ClosureManagementAnalytics::snapshot()
     * @param array<string, mixed> $matrix     ClosureDispatcherAnalytics::matrix()
     *
     * @return array<string, array<string, mixed>> id do gráfico => config
     */
    public static function all(array $management, array $matrix): array
    {
        return [
            'trend'      => self::trend($management['comparator']),
            'partners'   => self::partners($management['partners']),
            'activities' => self::activities($management['activities']),
            'users'      => self::users($matrix),
            'status'     => self::status($matrix),
        ];
    }

    private static function bar(string $label, array $data, array $gradient, string $stack = 'a', array $extra = []): array
    {
        return $extra + ['label' => $label, 'data' => $data, 'gradient' => $gradient, 'borderRadius' => 4, 'maxBarThickness' => 36, 'stack' => $stack];
    }

    /** Pendências abertas × resolvidas por mês: a distância entre as duas barras é o estoque que cresce ou diminui. */
    public static function trend(array $comparator): array
    {
        $series = $comparator['series'];

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => array_column($comparator['months'], 'label'),
                'datasets' => [
                    self::bar('Pendências abertas', $series['issues_opened'], self::RED, 'abertas', ['maxBarThickness' => 30]),
                    self::bar('Pendências resolvidas', $series['issues_resolved'], self::GREEN, 'resolvidas', ['maxBarThickness' => 30]),
                    self::bar('Devoluções para correção', $series['returns'], self::AMBER, 'devolucoes', ['maxBarThickness' => 30]),
                ],
            ],
            'options' => MyPanelCharts::options(['scales' => ['x' => ['grid' => ['display' => false]]], 'plugins' => ['mpBarLabels' => ['segments' => false, 'totals' => false]]]),
        ];
    }

    /** As parceiras com mais pendências no período: recebidas × ainda em aberto (top 10; a tabela traz todas). */
    public static function partners(array $partners): array
    {
        $top = array_slice($partners, 0, self::TOP);

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => array_column($top, 'name'),
                'datasets' => [
                    self::bar('Recebidas no período', array_column($top, 'received'), self::BLUE, 'recebidas', ['maxBarThickness' => 22]),
                    self::bar('Em aberto agora', array_column($top, 'open'), self::AMBER, 'abertas', ['maxBarThickness' => 22]),
                    self::bar('Devoluções para correção', array_column($top, 'returns'), self::RED, 'devolucoes', ['maxBarThickness' => 22]),
                ],
            ],
            'options' => MyPanelCharts::options(['indexAxis' => 'y', 'scales' => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]], 'y' => ['grid' => ['display' => false]]]]),
        ];
    }

    /** Por atividade (Parceiras, Medição, Fiscalização, Desenho): recebidas, resolvidas e em aberto. */
    public static function activities(array $activities): array
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels'   => array_column($activities, 'name'),
                'datasets' => [
                    self::bar('Recebidas no período', array_column($activities, 'received'), self::BLUE, 'recebidas', ['maxBarThickness' => 26]),
                    self::bar('Resolvidas no período', array_column($activities, 'resolved'), self::GREEN, 'resolvidas', ['maxBarThickness' => 26]),
                    self::bar('Em aberto agora', array_column($activities, 'open'), self::AMBER, 'abertas', ['maxBarThickness' => 26]),
                ],
            ],
            'options' => MyPanelCharts::options(['scales' => ['x' => ['grid' => ['display' => false]]], 'plugins' => ['mpBarLabels' => ['segments' => false, 'totals' => false]]]),
        ];
    }

    /** @param array{rows: array<int, array<string, mixed>>} $matrix */
    public static function users(array $matrix): array
    {
        $rows = array_slice($matrix['rows'], 0, self::TOP);
        $sets = [];

        foreach (['P' => 'Passivo', 'M' => 'Meta atual', 'F' => 'Meta futura', 'N' => 'Sem tipo'] as $type => $label) {
            $sets[] = self::bar($label, array_map(fn ($r) => $r['types'][$type], $rows), self::TYPE_GRADIENTS[$type], 'tipo', ['maxBarThickness' => 26]);
        }

        return [
            'type'    => 'bar',
            'data'    => ['labels' => array_column($rows, 'name'), 'datasets' => $sets],
            'options' => MyPanelCharts::options(['indexAxis' => 'y', 'scales' => ['x' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0]], 'y' => ['stacked' => true, 'grid' => ['display' => false]]]]),
        ];
    }

    /** @param array{totals: array<string, mixed>} $matrix */
    public static function status(array $matrix): array
    {
        $codes  = ClosureDispatcherAnalytics::MATRIX_STATUSES;
        $values = array_map(fn ($s) => $matrix['totals']['status'][$s], $codes);
        $total  = (int) $matrix['totals']['total'];

        return [
            'type' => 'doughnut',
            'data' => [
                'labels'   => array_map(fn ($s) => Status::labelFor($s), $codes),
                'datasets' => [['label' => 'Ordens', 'data' => $values, 'backgroundColor' => self::STATUS_COLORS, 'borderWidth' => 3, 'borderColor' => '#ffffff', 'hoverOffset' => 8]],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'cutout'              => '66%',
                'plugins'             => [
                    'legend'   => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]],
                    'mpCenter' => ['text' => (string) $total, 'sub' => $total === 1 ? 'ordem ativa' : 'ordens ativas'],
                ],
            ],
        ];
    }
}
