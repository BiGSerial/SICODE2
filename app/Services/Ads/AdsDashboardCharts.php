<?php

namespace App\Services\Ads;

/**
 * Configuração (só dados) dos gráficos do painel de ADS. Usa o `my-panel.js` da Home (criação e atualização in-place), então a
 * quantidade e a ordem dos datasets de cada gráfico é fixa; gráfico sem dados ou sem conexão recebe a mesma estrutura, vazia.
 */
final class AdsDashboardCharts
{
    private const PURPLE = '#7c3aed';
    private const RED    = '#ef4444';
    private const BLUE   = '#2563eb';
    private const GREEN  = '#16a34a';

    /** Até quantos pontos o valor é escrito sobre a linha (acima disso polui). */
    private const VALUES_MAX_POINTS = 12;

    /** Nomes dos gráficos e a seção do snapshot de onde cada um sai. */
    public const SECTIONS = [
        'backlog' => 'flow',
        'flow'    => 'flow',
        'queue'   => 'queue',
        'reuse'   => 'reuse',
    ];

    /**
     * Todos os gráficos, com os dados das seções que têm conexão e a estrutura vazia das demais.
     *
     * @param array<string, mixed> $snapshot AdsDashboardAnalytics::snapshot()
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(array $snapshot): array
    {
        $ok = fn (string $section) => ($snapshot[$section]['state'] ?? null) === AdsDashboardAnalytics::STATE_OK;

        $flow  = $ok('flow') ? $snapshot['flow']['data'] : self::emptyFlow();
        $queue = $ok('queue') ? $snapshot['queue']['data'] : ['labels' => [], 'status_keys' => [], 'values' => [], 'colors' => [], 'total' => 0];
        $reuse = $ok('reuse') ? $snapshot['reuse']['data'] : ['labels' => [], 'values' => [], 'colors' => [], 'total' => 0];

        return [
            'backlog' => self::backlog($flow),
            'flow'    => self::flow($flow),
            'queue'   => self::queue($queue),
            'reuse'   => self::reuse($reuse),
        ];
    }

    /**
     * Só os gráficos cujas seções têm conexão: é o que vai no evento `my-panel:charts`. Os demais ficam como estão (último estado
     * preservado por baixo do aviso), nunca esvaziados.
     *
     * @param array<string, mixed> $snapshot
     *
     * @return array<string, array<string, mixed>>
     */
    public static function live(array $snapshot): array
    {
        return array_intersect_key(
            self::all($snapshot),
            array_filter(self::SECTIONS, fn (string $section) => ($snapshot[$section]['state'] ?? null) === AdsDashboardAnalytics::STATE_OK)
        );
    }

    /**
     * Acumulado em aberto e atrasadas (>24h), com as médias do período.
     *
     * @param array<string, mixed> $d demandVsDeliverySeries()
     */
    public static function backlog(array $d): array
    {
        $count  = count($d['labels']);
        $values = $count <= self::VALUES_MAX_POINTS;
        $line   = ['type' => 'line', 'borderWidth' => 2.5, 'pointRadius' => $values ? 3 : 0, 'pointHoverRadius' => 5, 'cubicInterpolationMode' => 'monotone'];
        $avg    = ['type' => 'line', 'borderWidth' => 1.5, 'borderDash' => [6, 6], 'pointRadius' => 0, 'pointHoverRadius' => 0, 'fill' => false];

        return [
            'type' => 'line',
            'data' => [
                'labels'   => $d['labels'],
                'datasets' => [
                    $line + ['label' => 'Acumulado em aberto', 'data' => $d['open_backlog'], 'borderColor' => self::PURPLE, 'fill' => true, 'gradient' => ['rgba(124,58,237,.30)', 'rgba(124,58,237,0)'], 'showValues' => $values],
                    $line + ['label' => 'Atrasadas (>24h)', 'data' => $d['overdue_backlog'], 'borderColor' => self::RED, 'fill' => true, 'gradient' => ['rgba(239,68,68,.26)', 'rgba(239,68,68,0)'], 'showValues' => $values],
                    $avg + ['label' => 'Média (acumulado)', 'data' => self::flat($d['open_backlog']), 'borderColor' => 'rgba(124,58,237,.7)'],
                    $avg + ['label' => 'Média (atrasadas)', 'data' => self::flat($d['overdue_backlog']), 'borderColor' => 'rgba(239,68,68,.7)'],
                ],
            ],
            'options' => self::options([
                'scales'  => ['x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 12, 'autoSkip' => true]]],
                'plugins' => ['mpClick' => ['keys' => $d['date_keys']]],
            ]),
        ];
    }

    /**
     * Entradas (solicitadas) x saídas (concluídas), com as médias do período.
     *
     * @param array<string, mixed> $d demandVsDeliverySeries()
     */
    public static function flow(array $d): array
    {
        $avg = ['type' => 'line', 'borderWidth' => 1.5, 'borderDash' => [6, 6], 'pointRadius' => 0, 'pointHoverRadius' => 0, 'fill' => false];

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $d['labels'],
                'datasets' => [
                    ['type' => 'bar', 'label' => 'Solicitadas', 'data' => $d['requested'], 'gradient' => ['#60a5fa', '#2563eb'], 'borderRadius' => 6, 'maxBarThickness' => 34],
                    ['type' => 'bar', 'label' => 'Concluídas', 'data' => $d['delivered'], 'gradient' => ['#4ade80', '#15803d'], 'borderRadius' => 6, 'maxBarThickness' => 34],
                    $avg + ['label' => 'Média (solicitadas)', 'data' => self::flat($d['requested']), 'borderColor' => self::BLUE],
                    $avg + ['label' => 'Média (concluídas)', 'data' => self::flat($d['delivered']), 'borderColor' => self::GREEN],
                ],
            ],
            'options' => self::options([
                // Barra exata sob o cursor: o clique em "Concluídas" filtra pela data de conclusão, o em "Solicitadas" pela de solicitação.
                'interaction' => ['mode' => 'nearest', 'intersect' => true],
                'scales'      => ['x' => ['grid' => ['display' => false]]],
                'plugins'     => ['mpBarLabels' => ['outside' => true], 'mpClick' => ['keys' => $d['date_keys']]],
            ]),
        ];
    }

    /** @param array{labels: array<int, string>, values: array<int, int>, colors: array<int, string>, total: int} $d */
    public static function queue(array $d): array
    {
        return self::donut($d, 'Fila', 'na fila', $d['status_keys'] ?? []);
    }

    /** @param array{labels: array<int, string>, values: array<int, int>, colors: array<int, string>, total: int} $d */
    public static function reuse(array $d): array
    {
        return self::donut($d, 'Solicitações', (int) $d['total'] === 1 ? 'solicitação' : 'solicitações', ['reused', 'new']);
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

    /** @param array{labels: array<int, string>, values: array<int, int>, colors: array<int, string>, total: int} $d */
    private static function donut(array $d, string $label, string $sub, array $keys): array
    {
        // A legenda leva a quantidade e a fatia de cada item, já que a rosca não escreve valores sobre as fatias.
        $total  = max(1, (int) array_sum($d['values']));
        $labels = array_map(
            fn (string $name, int $value) => $name . ' · ' . $value . ' (' . number_format($value / $total * 100, 1, ',', '.') . '%)',
            $d['labels'],
            $d['values'],
        );

        return [
            'type' => 'doughnut',
            'data' => [
                'labels'   => $labels,
                'datasets' => [[
                    'label'           => $label,
                    'data'            => $d['values'],
                    'backgroundColor' => $d['colors'],
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
                    'mpCenter' => ['text' => (string) (int) $d['total'], 'sub' => $sub],
                    'mpClick'  => ['keys' => $keys],
                ],
            ],
        ];
    }

    /**
     * Linha constante com a média da série (as médias das linhas tracejadas).
     *
     * @param array<int, int|float> $values
     *
     * @return array<int, float>
     */
    private static function flat(array $values): array
    {
        $count = count($values);

        return $count === 0 ? [] : array_fill(0, $count, round(array_sum($values) / $count, 2));
    }

    /** @return array<string, array<int, mixed>> */
    private static function emptyFlow(): array
    {
        return ['labels' => [], 'date_keys' => [], 'requested' => [], 'delivered' => [], 'open_backlog' => [], 'overdue_backlog' => []];
    }
}
