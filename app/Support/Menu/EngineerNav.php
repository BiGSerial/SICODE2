<?php

namespace App\Support\Menu;

/**
 * Definição única da navegação da Engenharia, no padrão de docs/PADRAO_MENU_LATERAL.md: o menu lateral (`Engineers\Menu`) e o
 * dropdown do topo (`menu/engineer-dropdown`) leem daqui, então nunca divergem. Módulos seguem o ciclo da obra: Projetos (antes da obra), Informes (execução) e Cancelamentos.
 *
 * Item: key, label, route, icon, `match` (nomes de rota ou prefixos terminados em ".") que o deixam ativo e `counts`
 * (componentes Livewire de contador). Grupo `flat` não mostra cabeçalho. Quem usa só precisa de `modules()` e `locate()`.
 */
final class EngineerNav extends SidebarNav
{
    public const DEFAULT_MODULE = 'projetos';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function modules(array $context = []): array
    {
        $userId = (string) auth()->id();

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
                            self::item('viab-painel', 'Painel', 'engineers.main', 'ri-dashboard-3-line', ['engineers.viability', 'engineers.viability_waiting']),
                            self::item('viab-em', 'Em viabilidade', 'engineers.viab_list', 'ri-run-line', [], [['engineers.counts.in-viability']]),
                            self::item('viab-tratativa', 'Em tratativa', 'engineers.rejecte_viab', 'ri-chat-check-line', [], [['engineers.counts.in-work-count']]),
                            self::item('viab-ri', 'Retorno interno', 'engineers.intern_return', 'ri-arrow-go-back-line', [], [['engineers.counts.return-intern-count']]),
                            self::item('viab-justif', 'Avaliação de justificativas', 'engineers.justified_viab', 'ri-scales-3-line', [], [['engineers.counts.viab-justify-count']]),
                            self::item('viab-hist', 'Histórico', 'engineers.viab_hist', 'ri-history-line'),
                            self::item('viab-resumo', 'Resumo da viabilidade', 'engineers.viabilityreports', 'ri-bar-chart-box-line'),
                        ],
                    ],
                    [
                        'key'   => 'validacao',
                        'label' => 'Validação de projetos',
                        'icon'  => 'ri-shield-check-line',
                        'items' => [
                            self::item('val-painel', 'Painel', 'engineers.analises.dashboard', 'ri-dashboard-3-line'),
                            self::item('val-pilha', 'Obras à validar', 'engineers.analises.toAnalise', 'ri-todo-line', ['engineers.validation'], [['engineers.counts.analises.to-approval-count']]),
                            self::item('val-em', 'Em validação', 'engineers.analises.inAnalise', 'ri-loader-4-line', [], [
                                ['engineers.counts.analises.in-approval-count', ['engineer' => true]],
                                ['engineers.counts.analises.in-approval-return', ['engineer' => true]],
                            ]),
                            self::item('val-ok', 'Validados', 'engineers.analises.analised', 'ri-checkbox-circle-line'),
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
                        'key'   => 'geral',
                        'label' => 'Visão geral',
                        'icon'  => 'ri-dashboard-3-line',
                        'flat'  => true,
                        'items' => [
                            self::item('inf-painel', 'Painel de informes', 'engineers.dashboard.conclusion_inform', 'ri-dashboard-3-line'),
                        ],
                    ],
                    [
                        'key'   => 'parciais',
                        'label' => 'Informes parciais',
                        'icon'  => 'ri-file-list-3-line',
                        'items' => [
                            self::item('par-aguardando', 'Aguardando aprovação', 'engineers.info.parcial', 'ri-timer-flash-line', ['engineers.parciais'], [['engineers.counts.count-parcial']]),
                            self::item('par-hist', 'Histórico', 'engineers.hist.parcial', 'ri-history-line'),
                        ],
                    ],
                    [
                        'key'   => 'finais',
                        'label' => 'Informes finais',
                        'icon'  => 'ri-check-double-line',
                        'items' => [
                            self::item('fin-lista', 'Lista de informes', 'engineers.inform_list', 'ri-article-line'),
                            self::item('fin-lancar', 'Lançar informe', 'engineers.inform_obra', 'ri-edit-box-line', ['engineers.informes']),
                        ],
                    ],
                    [
                        'key'   => 'ads',
                        'label' => 'ADS',
                        'icon'  => 'ri-money-dollar-circle-line',
                        'items' => [
                            self::item('ads-solic', 'Solicitações', 'engineers.ads.requests', 'ri-file-add-line'),
                            self::item('ads-sit', 'Situação', 'engineers.ads.situation', 'ri-alarm-warning-line'),
                            self::item('ads-cal', 'Calendário', 'engineers.ads.calendar', 'ri-calendar-check-line'),
                        ],
                    ],
                    [
                        'key'   => 'd5',
                        'label' => 'Notas D5',
                        'icon'  => 'ri-sticky-note-line',
                        'items' => [
                            self::item('d5-resolucao', 'Aguardando resolução', 'engineers.dfive.waiting', 'ri-todo-line', ['engineers.d5'], [['engineers.count.waiting-five-notes-count']]),
                        ],
                    ],
                    [
                        'key'   => 'pep',
                        'label' => 'Orçamento (PEP)',
                        'icon'  => 'ri-wallet-3-line',
                        'items' => [
                            self::item('pep-sem-saldo', 'Informes sem saldo', 'engineers.pep_alerts', 'ri-wallet-3-line', [], [['engineers.counts.pep-alerts-count']]),
                        ],
                    ],
                ],
            ],
            'cancelamentos' => [
                'label'  => 'Cancelamentos',
                'icon'   => 'ri-close-circle-line',
                'hint'   => 'Fluxo próprio',
                'groups' => [
                    [
                        'key'   => 'cancelamentos',
                        'label' => 'Cancelamentos',
                        'icon'  => 'ri-close-circle-line',
                        'flat'  => true,
                        'items' => [
                            self::item('can-aprovacao', 'Aprovações', 'engineers.cancellations.index', 'ri-checkbox-circle-line', ['engineers.cancellations.show'], [['components.count.cancellation-requests', ['mode' => 'engineer_pending', 'userId' => $userId]]]),
                            self::item('can-hist', 'Histórico de aprovações', 'engineers.cancellations.history', 'ri-history-line'),
                            self::item('can-notas', 'Notas em cancelamento', 'engineers.cancellations.regional', 'ri-file-warning-line'),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Itens do dropdown do topo: um por módulo, apontando para a primeira tela útil.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function topItems(): array
    {
        return [
            ['label' => 'VIABILIDADE', 'route' => 'engineers.viab_list', 'icon' => 'ri-radar-line'],
            ['label' => 'VALIDAÇÃO DE PROJETOS', 'route' => 'engineers.analises.toAnalise', 'icon' => 'ri-shield-check-line'],
            [
                'label'          => 'INFORMES',
                'route'          => 'engineers.dashboard.conclusion_inform',
                'icon'           => 'ri-file-list-3-line',
                'countComponent' => 'engineers.counts.informes-count',
                'countKey'       => 'engineer-informes-top',
            ],
            [
                'label'          => 'CANCELAMENTOS',
                'route'          => 'engineers.cancellations.index',
                'icon'           => 'ri-close-circle-line',
                'countComponent' => 'components.count.cancellation-requests',
                'countParams'    => ['mode' => 'engineer_pending', 'userId' => (string) auth()->id()],
                'countKey'       => 'engineer-cancellations-pending-top',
            ],
        ];
    }
}
