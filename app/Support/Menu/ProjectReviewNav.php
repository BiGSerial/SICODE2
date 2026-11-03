<?php

namespace App\Support\Menu;

use App\Support\Menu\Concerns\NavBuilders;

/**
 * Menu lateral da ANÁLISE DE PROJETOS (project_review.*), no padrão de docs/PADRAO_MENU_LATERAL.md. Os dois números da fila
 * (itens para analisar e retornos) vêm do contador `project-review.menu-counts`.
 */
final class ProjectReviewNav extends SidebarNav
{
    use NavBuilders;

    public const DEFAULT_MODULE = 'analise';

    public static function modules(array $context = []): array
    {
        return self::module('analise', 'Análise de projetos', 'ri-task-line', 'Revisão e governança', [
            self::group('analise', 'Análise', 'ri-task-line', [
                self::item('lista', 'Lista para analisar', 'project_review.list', 'ri-task-line', [], [['project-review.menu-counts']]),
                self::item('historico', 'Histórico das análises', 'project_review.history', 'ri-history-line'),
            ]),
            self::group('governanca', 'Governança', 'ri-dashboard-line', [
                self::item('dashboard', 'Dashboard de governança', 'project_review.dashboard', 'ri-dashboard-line'),
            ]),
            self::group('configuracao', 'Configuração', 'ri-settings-3-line', [
                self::item('categorias', 'Categorias', 'project_review.categories', 'ri-price-tag-3-line'),
            ]),
        ]);
    }
}
