<?php

namespace App\Support\Menu;

use App\Support\Menu\Concerns\NavBuilders;

/**
 * Menu lateral do JURÍDICO, no padrão de docs/PADRAO_MENU_LATERAL.md. Os três papéis (Controlador, Executante e Gestão) são grupos de um
 * módulo só, cada um liberado pelo seu gate; quem acumula papéis vê os grupos juntos. O dropdown do topo lê daqui (`topSections()`).
 */
final class LegalNav extends SidebarNav
{
    use NavBuilders;

    public const DEFAULT_MODULE = 'juridico';

    public static function modules(array $context = []): array
    {
        return self::module('juridico', 'Jurídico', 'ri-scales-3-line', 'Demandas e casos', [
            self::group('controlador', 'Controlador', 'ri-inbox-line', [
                self::item('fila', 'Fila de demandas', 'legal.queue', 'ri-list-check-2'),
                self::item('minhas', 'Minhas demandas', 'legal.triage', 'ri-inbox-line', ['legal.demand.detail']),
                self::item('monitor', 'Monitor de subdemandas', 'legal.subdemand.monitor', 'ri-node-tree', ['legal.subdemand.detail']),
            ], ['can' => 'legal.demands.triage']),
            self::group('executante', 'Executante', 'ri-user-follow-line', [
                self::item('atribuicoes', 'Minhas atribuições', 'legal.field.queue', 'ri-task-line', ['legal.field.']),
            ], ['can' => 'legal.demands.answer']),
            self::group('gestao', 'Gestão', 'ri-bar-chart-box-line', [
                self::item('dashboard', 'Dashboard', 'legal.dashboard', 'ri-dashboard-3-line'),
                self::item('casos', 'Busca de casos', 'legal.cases', 'ri-search-line'),
                self::item('relatorios', 'Relatórios', 'legal.reports', 'ri-file-chart-line'),
            ], ['can' => 'legal.manager']),
        ]);
    }

    /**
     * Seções do dropdown "JURÍDICO" do topo (só os grupos que o usuário pode ver), a partir da mesma definição do lateral.
     *
     * @return array<int, array{label: string, items: array<int, array{label: string, route: string, icon: string}>}>
     */
    public static function topSections(): array
    {
        $sections = [];

        foreach (static::resolve() as $module) {
            foreach ($module['groups'] as $group) {
                $sections[] = [
                    'label' => $group['label'],
                    'items' => array_map(fn (array $item) => ['label' => mb_strtoupper($item['label']), 'route' => $item['route'], 'icon' => $item['icon']], $group['items']),
                ];
            }
        }

        return $sections;
    }
}
