<?php

namespace App\Support\Menu;

/**
 * Navegação do Responsável, no padrão de docs/PADRAO_MENU_LATERAL.md (mesma organização da Engenharia: Projetos e Informes).
 * Menu lateral (`Responsible\Menu`) e dropdown do topo (`menu/responsible-dropdown`) leem daqui.
 */
final class ResponsibleNav extends SidebarNav
{
    public const DEFAULT_MODULE = 'projetos';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function modules(array $context = []): array
    {
        return [
            'projetos' => [
                'label'  => 'Projetos',
                'icon'   => 'ri-pencil-ruler-2-line',
                'hint'   => 'Antes da obra',
                'groups' => [
                    [
                        'key'   => 'viabilidade',
                        'label' => 'Viabilidade',
                        'icon'  => 'ri-radar-line',
                        'items' => [
                            self::item('viab-em', 'Em viabilidade', 'responsible.viab_list', 'ri-run-line', ['responsible.main', 'responsible.viability', 'responsible.viability_waiting'], [['responsible.counts.in-viability']]),
                            self::item('viab-tratativa', 'Em tratativa', 'responsible.rejecte_viab', 'ri-chat-check-line', [], [['responsible.counts.in-work-count']]),
                            self::item('viab-ri', 'Retorno interno', 'responsible.intern_return', 'ri-arrow-go-back-line', [], [['responsible.counts.return-intern-count']]),
                            self::item('viab-justif', 'Avaliação de justificativas', 'responsible.justified_viab', 'ri-scales-3-line', [], [['responsible.counts.viab-justify-count']]),
                            self::item('viab-hist', 'Histórico', 'responsible.viab_hist', 'ri-history-line'),
                        ],
                    ],
                    [
                        'key'   => 'validacao',
                        'label' => 'Validação de projetos',
                        'icon'  => 'ri-shield-check-line',
                        'items' => [
                            self::item('val-pilha', 'Obras à validar', 'responsible.approve_list', 'ri-todo-line', ['responsible.validation'], [['engineers.counts.analises.to-approval-count']]),
                            self::item('val-em', 'Em validação', 'responsible.approve_control', 'ri-loader-4-line', [], [
                                ['engineers.counts.analises.in-approval-count'],
                                ['engineers.counts.analises.in-approval-return'],
                            ]),
                            self::item('val-ok', 'Validados', 'responsible.approve_hist', 'ri-checkbox-circle-line'),
                        ],
                    ],
                ],
            ],
            'informes' => [
                'label'  => 'Informes',
                'icon'   => 'ri-file-list-3-line',
                'hint'   => 'Obra informada',
                'groups' => [
                    [
                        'key'   => 'parciais',
                        'label' => 'Informes parciais',
                        'icon'  => 'ri-file-list-3-line',
                        'items' => [
                            self::item('par-hist', 'Histórico', 'responsible.partial_hist', 'ri-history-line', ['responsible.parciais']),
                        ],
                    ],
                    [
                        'key'   => 'finais',
                        'label' => 'Informes finais',
                        'icon'  => 'ri-check-double-line',
                        'items' => [
                            self::item('fin-lista', 'Lista de informes', 'responsible.inform_list', 'ri-article-line'),
                            self::item('fin-lancar', 'Lançar informe', 'responsible.inform_obra', 'ri-edit-box-line', ['responsible.informes']),
                        ],
                    ],
                    [
                        'key'   => 'ads',
                        'label' => 'ADS',
                        'icon'  => 'ri-money-dollar-circle-line',
                        'items' => [
                            self::item('ads-solic', 'Solicitações', 'responsible.ads.requests', 'ri-file-add-line'),
                        ],
                    ],
                    [
                        'key'   => 'd5',
                        'label' => 'Notas D5',
                        'icon'  => 'ri-sticky-note-line',
                        'items' => [
                            self::item('d5-resolucao', 'Aguardando resolução', 'responsible.dfive.waiting', 'ri-todo-line', ['responsible.d5'], [['engineers.count.waiting-five-notes-count']]),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Itens do dropdown do topo: um por módulo/assunto, apontando para a primeira tela útil.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function topItems(): array
    {
        return [
            ['label' => 'VIABILIDADE', 'route' => 'responsible.viab_list', 'icon' => 'ri-radar-line'],
            ['label' => 'VALIDAÇÃO DE PROJETOS', 'route' => 'responsible.approve_list', 'icon' => 'ri-shield-check-line'],
            ['label' => 'INFORMES', 'route' => 'responsible.inform_list', 'icon' => 'ri-file-list-3-line'],
        ];
    }
}
