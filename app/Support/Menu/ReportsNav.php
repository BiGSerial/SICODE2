<?php

namespace App\Support\Menu;

use App\Support\Menu\Concerns\NavBuilders;

/**
 * Menu lateral dos RELATÓRIOS, no padrão de docs/PADRAO_MENU_LATERAL.md. Contexto: `variant` (a família de relatórios; ver VARIANTS), a
 * mesma que cada `reports/*menu.blade.php` informa ao chamar `<x-nav.sidebar>`. A lista do dropdown do topo (`topLinks()`) sai daqui
 * também, então lateral e topo nunca divergem.
 */
final class ReportsNav extends SidebarNav
{
    use NavBuilders;

    /** `informes` é o `reports/menu.blade.php`; as demais têm o próprio arquivo. */
    public const VARIANTS = ['informes', 'cancelamentos', 'reclamacoes', 'notas-d5', 'analise-projetos', 'retorno-interno'];

    public static function sampleContexts(): array
    {
        return array_map(fn (string $variant) => ['variant' => $variant], self::VARIANTS);
    }

    public static function modules(array $context = []): array
    {
        $complaints = self::item('reclamacao', 'Relatório de reclamação', 'reports.complaints_mede', 'ri-alarm-warning-line');
        $fiveNotes  = self::item('notas-d5', 'Relatório de notas D5', 'reports.five_notes', 'ri-file-excel-2-line');

        return match ((string) ($context['variant'] ?? 'informes')) {
            'cancelamentos' => self::module('cancelamentos', 'Cancelamentos', 'ri-close-circle-line', 'Relatórios de cancelamento', [
                self::group('cancelamentos', 'Cancelamentos', 'ri-close-circle-line', [
                    self::item('dashboard', 'Dashboard gerencial', 'reports.cancellations_dashboard', 'ri-line-chart-line'),
                    self::item('lista', 'Lista de cancelamentos', 'reports.cancellations_list', 'ri-table-line'),
                ]),
            ]),
            'reclamacoes' => self::module('reclamacoes', 'Reclamações', 'ri-alarm-warning-line', 'Relatório de reclamações', [
                self::group('reclamacoes', 'Reclamações', 'ri-alarm-warning-line', [$complaints], ['flat' => true]),
            ]),
            'notas-d5' => self::module('notas-d5', 'Notas D5', 'ri-file-list-3-line', 'Relatório de notas D5', [
                self::group('notas-d5', 'Notas D5', 'ri-file-list-3-line', [$fiveNotes], ['flat' => true]),
            ]),
            'analise-projetos' => self::module('analise-projetos', 'Análise de projetos', 'ri-task-line', 'Relatórios da análise', [
                self::group('analise', 'Análise de projetos', 'ri-task-line', [
                    self::item('dashboard', 'Dashboard', 'reports.project_review_dashboard', 'ri-speed-line'),
                    self::item('historico', 'Histórico', 'reports.project_review_history', 'ri-history-line'),
                ]),
            ]),
            'retorno-interno' => self::module('retorno-interno', 'Retorno interno', 'ri-arrow-go-back-line', 'Relatórios de RI', [
                self::group('ri', 'Retorno interno', 'ri-arrow-go-back-line', [
                    self::item('abertos', 'Retornos abertos', 'reports.return_intern', 'ri-inbox-archive-line'),
                    self::item('indicadores', 'Indicadores históricos', 'reports.return_intern_dashboard', 'ri-dashboard-line'),
                    self::item('historico', 'Relatório histórico', 'reports.return_intern_list', 'ri-file-list-3-line'),
                ]),
            ]),
            default => self::module('informes', 'Informes', 'ri-file-list-3-line', 'Relatórios de informes', [
                self::group('informes', 'Informes', 'ri-file-list-3-line', [
                    self::item('obras', 'Obras informadas', 'reports.workreport', 'ri-list-check-2'),
                    self::item('ads-tacita', 'Informe x ADS tácita', 'reports.informe_ads_tacita', 'ri-links-line'),
                    $fiveNotes,
                    self::item('pos-obra', 'Processo de medição pós-obra', 'reports.post_work_process', 'ri-time-line'),
                    $complaints,
                ]),
                self::group('rejeitados', 'Informes rejeitados', 'ri-close-circle-line', [
                    self::item('rejeitados', 'Informes rejeitados', 'reports.rejecetedWorkreport', 'ri-close-circle-line'),
                    self::item('rejeitados-hist', 'Histórico de informes rejeitados', 'reports.historicRejectReports', 'ri-history-line'),
                ]),
            ]),
        };
    }

    /**
     * Relatórios do dropdown do topo, com o gate de cada um (`can`); quem monta o menu só mostra os que o usuário pode abrir.
     *
     * @return array<int, array{route: string, label: string, can: string}>
     */
    public static function topLinks(): array
    {
        $management = fn (string $route, string $label) => ['route' => $route, 'label' => $label, 'can' => 'management'];

        return [
            $management('reports.general_sicode', 'RELATÓRIO GERAL SICODE'),
            $management('reports.team_panel', 'PAINEL DA EQUIPE'),
            $management('reports.productions', 'RELATÓRIO DE PRODUÇÃO'),
            $management('reports.viabilities', 'RELATÓRIO DE VIABILIDADE'),
            $management('reports.return_intern', 'RETORNO INTERNO'),
            $management('reports.cancellations_dashboard', 'RELATÓRIO CANCELAMENTOS'),
            $management('reports.return_work_reports', 'INFORMES REJEITADOS (RETURNWORK)'),
            $management('reports.complaints_mede', 'RELATÓRIO DE RECLAMAÇÃO'),
            $management('reports.five_notes', 'RELATÓRIO NOTAS D5'),
            $management('reports.post_work_process', 'PROCESSO MEDIÇÃO PÓS OBRA'),
            $management('reports.project_deadlines', 'RELATÓRIO PRAZO DE PROJETOS'),
            ['route' => 'reports.project_review_dashboard', 'label' => 'RELATÓRIO ANÁLISE PROJETOS', 'can' => 'projectReviewReports'],
            $management('reports.advancedsearch', 'BUSCAR AVANÇADA'),
        ];
    }
}
