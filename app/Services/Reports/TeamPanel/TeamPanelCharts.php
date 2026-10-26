<?php

namespace App\Services\Reports\TeamPanel;

use App\Services\Home\MyPanel\MyPanelCharts;

/**
 * Configuração (só dados) dos gráficos do "Painel da equipe". Usa o mesmo `my-panel.js` do Meu painel (criação e atualização
 * in-place, `gradient` e `mpCenter`): a quantidade e a ordem dos datasets de cada gráfico são fixas; só mudam rótulos e valores.
 */
final class TeamPanelCharts
{
    /** Quantas pessoas/empresas aparecem nos gráficos de ranking (a tabela e o Excel trazem todas). */
    public const TOP = 10;

    private const BLUE  = '#2563eb';
    private const GREEN = '#22c55e';
    private const SLATE = '#cbd5e1';

    /**
     * @param array<string, mixed> $snapshot TeamPanelAnalytics::snapshot()
     *
     * @return array<string, array<string, mixed>> id do gráfico => config
     */
    public static function all(array $snapshot): array
    {
        return [
            'daily'        => MyPanelCharts::daily($snapshot['daily']),
            'monthly'      => MyPanelCharts::monthly($snapshot['monthly']),
            'situation'    => MyPanelCharts::situation($snapshot['status'], $snapshot['summary']['open']),
            'users'        => self::users($snapshot['users']),
            'load'         => self::load($snapshot['open_by_user']),
            'companies'    => self::companies($snapshot['companies']),
            'productivity' => self::productivity($snapshot['activities']),
        ];
    }

    /**
     * Concluídas × abertas por pessoa (as que mais produziram).
     *
     * @param array<int, array<string, mixed>> $users
     */
    public static function users(array $users): array
    {
        $top = array_slice($users, 0, self::TOP);

        return self::horizontal(array_column($top, 'name'), [
            ['label' => 'Concluídas no período', 'data' => array_column($top, 'notas'), 'gradient' => ['#1d4ed8', '#38bdf8'], 'borderRadius' => 8, 'maxBarThickness' => 20],
            ['label' => 'Abertas agora', 'data' => array_column($top, 'open'), 'backgroundColor' => self::SLATE, 'borderRadius' => 8, 'maxBarThickness' => 20],
        ]);
    }

    /**
     * Carga em aberto por pessoa, empilhada por idade (quanto do que está com a pessoa já é antigo).
     *
     * @param array<int, array<string, mixed>> $openByUser
     */
    public static function load(array $openByUser): array
    {
        $top = array_slice($openByUser, 0, self::TOP);

        return self::horizontal(array_column($top, 'name'), [
            ['label' => '0-15 dias', 'data' => array_column($top, 'b_new'), 'gradient' => ['#1d4ed8', '#60a5fa'], 'stack' => 'idade', 'maxBarThickness' => 20],
            ['label' => '16-30 dias', 'data' => array_column($top, 'b_mid'), 'gradient' => ['#d97706', '#fbbf24'], 'stack' => 'idade', 'maxBarThickness' => 20],
            ['label' => '31+ dias', 'data' => array_column($top, 'b_old'), 'gradient' => ['#b91c1c', '#f87171'], 'stack' => 'idade', 'borderRadius' => 8, 'maxBarThickness' => 20],
        ], true);
    }

    /**
     * @param array<int, array<string, mixed>> $companies
     */
    public static function companies(array $companies): array
    {
        $top = array_slice($companies, 0, self::TOP);

        return self::horizontal(array_column($top, 'name'), [
            ['label' => 'Concluídas no período', 'data' => array_column($top, 'notas'), 'gradient' => ['#0f766e', '#2dd4bf'], 'borderRadius' => 8, 'maxBarThickness' => 20],
            ['label' => 'Abertas agora', 'data' => array_column($top, 'open'), 'backgroundColor' => self::SLATE, 'borderRadius' => 8, 'maxBarThickness' => 20],
        ]);
    }

    /**
     * Notas por pessoa por dia trabalhado, por atividade, contra a média da equipe.
     *
     * @param array<int, array<string, mixed>> $activities
     */
    public static function productivity(array $activities): array
    {
        return self::horizontal(array_column($activities, 'service'), [
            ['label' => 'Atividade (notas/pessoa/dia)', 'data' => array_column($activities, 'per_day'), 'gradient' => ['#1d4ed8', '#38bdf8'], 'borderRadius' => 8, 'maxBarThickness' => 22],
            ['label' => 'Média da equipe', 'data' => array_column($activities, 'team_per_day'), 'backgroundColor' => self::SLATE, 'borderRadius' => 8, 'maxBarThickness' => 22],
        ]);
    }

    /**
     * @param array<int, string>               $labels
     * @param array<int, array<string, mixed>> $datasets
     */
    private static function horizontal(array $labels, array $datasets, bool $stacked = false): array
    {
        return [
            'type'    => 'bar',
            'data'    => ['labels' => $labels, 'datasets' => $datasets],
            'options' => MyPanelCharts::options([
                'indexAxis' => 'y',
                'scales'    => [
                    'x' => ['beginAtZero' => true, 'stacked' => $stacked, 'ticks' => ['precision' => 0], 'grid' => ['color' => '#e8eef2']],
                    'y' => ['stacked' => $stacked, 'grid' => ['display' => false]],
                ],
            ]),
        ];
    }
}
