<?php

namespace App\Services\Dispatch\Dashboard;

use App\Services\Home\MyPanel\MyPanelCharts;

/**
 * Configuração (só dados) dos gráficos do Dashboard de atividade, no contrato do `my-panel.js`: quantidade e ordem dos datasets são
 * FIXAS por gráfico (a atualização é in-place). Extensões lidas pelo JS: `gradient`, `mpBarLabels` (veja MyPanelCharts).
 */
final class ActivityDashboardCharts
{
    private const GREEN  = ['#16a34a', '#4ade80'];
    private const BLUE   = ['#1d4ed8', '#60a5fa'];
    private const SLATE  = ['#94a3b8', '#cbd5e1'];
    private const TEAL   = ['#0f766e', '#2dd4bf'];
    private const VIOLET = ['#7c3aed', '#c4b5fd'];
    private const AMBER  = ['#d97706', '#fbbf24'];
    private const RED    = ['#dc2626', '#f87171'];

    /** @param array<string, mixed> $snapshot ActivityDashboardAnalytics::snapshot() @return array<string, array<string, mixed>> */
    public static function all(array $snapshot): array
    {
        return [
            'status'     => self::status($snapshot['status_age'], $snapshot['profile']['time']['axis']),
            'deadline'   => self::deadline($snapshot['deadline'], $snapshot['profile']['deadline']['axis']),
            'users'      => self::users($snapshot['users']),
            'band_satel' => self::band($snapshot['bands'], 'satel'),
            'band_own'   => self::band($snapshot['bands'], 'own'),
        ];
    }

    private static function bar(string $label, array $data, array $gradient, string $stack = 'pilha', array $extra = []): array
    {
        return $extra + ['label' => $label, 'data' => $data, 'gradient' => $gradient, 'borderRadius' => 4, 'maxBarThickness' => 36, 'stack' => $stack];
    }

    /** @param array{labels: array<int, string>, assigned: array<int, int>, unassigned: array<int, int>} $d */
    public static function status(array $d, string $axis = 'Dias no status'): array
    {
        return [
            'type'    => 'bar',
            'data'    => ['labels' => $d['labels'], 'datasets' => [self::bar('Em atribuição', $d['assigned'], self::GREEN), self::bar('Na pilha', $d['unassigned'], self::BLUE)]],
            'options' => MyPanelCharts::options(['scales' => ['x' => ['stacked' => true, 'grid' => ['display' => false], 'title' => ['display' => true, 'text' => $axis]], 'y' => ['stacked' => true]], 'plugins' => ['mpBarLabels' => ['segments' => false, 'totals' => true]]]),
        ];
    }

    /** @param array{labels: array<int, string>, series: array<string, array<int, int>>} $d */
    public static function deadline(array $d, string $axis = 'Dias consumidos do prazo'): array
    {
        $palette = ['Na pilha' => self::SLATE, 'SATEL' => self::BLUE, 'Própria (EDP)' => self::TEAL, 'Outras empresas' => self::VIOLET];
        $sets    = [];

        foreach (ActivityDashboardAnalytics::DEADLINE_SERIES as $name) {
            $sets[] = self::bar($name, $d['series'][$name] ?? [], $palette[$name]);
        }

        return [
            'type'    => 'bar',
            'data'    => ['labels' => $d['labels'], 'datasets' => $sets],
            'options' => MyPanelCharts::options(['scales' => ['x' => ['stacked' => true, 'grid' => ['display' => false], 'title' => ['display' => true, 'text' => $axis]], 'y' => ['stacked' => true]], 'plugins' => ['mpBarLabels' => ['segments' => false, 'totals' => true]]]),
        ];
    }

    /** @param array{labels: array<int, string>, buckets: array<string, array<int, int>>} $d */
    public static function users(array $d): array
    {
        $tones = [ActivityDashboardAnalytics::AGE_BUCKETS[0] => self::GREEN, ActivityDashboardAnalytics::AGE_BUCKETS[1] => self::AMBER, ActivityDashboardAnalytics::AGE_BUCKETS[2] => self::RED];
        $sets  = [];

        foreach (ActivityDashboardAnalytics::AGE_BUCKETS as $bucket) {
            $sets[] = self::bar($bucket, $d['buckets'][$bucket] ?? [], $tones[$bucket], 'colaborador', ['maxBarThickness' => 26]);
        }

        return [
            'type'    => 'bar',
            'data'    => ['labels' => $d['labels'], 'datasets' => $sets],
            'options' => MyPanelCharts::options(['indexAxis' => 'y', 'scales' => ['x' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0]], 'y' => ['stacked' => true, 'grid' => ['display' => false]]]]),
        ];
    }

    /** @param array<string, mixed> $bands @return array<string, mixed> */
    public static function band(array $bands, string $group): array
    {
        $g     = $bands['groups'][$group];
        $tones = [self::GREEN, self::SLATE, self::TEAL, self::RED];
        $sets  = [];

        foreach ($bands['band_labels'] as $i => $label) {
            $sets[] = self::bar($label, $g['series'][$i] ?? [], $tones[$i], 'faixa');
        }

        return [
            'type'    => 'bar',
            'data'    => ['labels' => array_map(fn (array $m) => [$m['label'], 'Total ' . $m['total'], $m['pct']], $g['months']), 'datasets' => $sets],
            'options' => MyPanelCharts::options(['scales' => ['x' => ['stacked' => true, 'grid' => ['display' => false]], 'y' => ['stacked' => true]], 'plugins' => ['mpBarLabels' => ['segments' => true, 'totals' => false]]]),
        ];
    }
}
