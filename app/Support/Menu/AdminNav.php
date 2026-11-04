<?php

namespace App\Support\Menu;

/**
 * Navegação da ADMINISTRAÇÃO (cadastros, controle de dados, configurações e sistema), no padrão de docs/PADRAO_MENU_LATERAL.md.
 * Substitui os menus `admin/**\/menu`, `config/*menu` e o dropdown montado à mão no topo: tudo lê daqui.
 *
 * Módulos pelo processo de quem administra: Cadastros (quem e quais empresas), Dados (controle e auditoria), Configurações
 * (como o sistema se comporta) e Sistema (saúde e operação). A permissão de cada item segue a da própria rota (`admin`,
 * `management` ou `superadm`).
 */
final class AdminNav extends SidebarNav
{
    public const DEFAULT_MODULE = 'cadastros';

    public static function modules(array $context = []): array
    {
        return [
            'cadastros' => [
                'label'  => 'Cadastros',
                'icon'   => 'ri-team-line',
                'hint'   => 'Pessoas e empresas',
                'groups' => [
                    self::group('usuarios', 'Usuários', 'ri-user-settings-line', [
                        self::item('usuarios', 'Usuários', 'admin.user.list', 'ri-team-line'),
                        self::item('hierarquia', 'Hierarquia de usuários', 'admin.user.hierarchy', 'ri-organization-chart'),
                    ]),
                    self::group('empresas', 'Empresas', 'ri-building-4-line', [
                        self::item('empresas', 'Empresas', 'admin.company.list', 'ri-building-4-line', [], [], [], ['can' => 'superadm']),
                        self::item('contratos', 'Contratos', 'admin.company.contracts_list', 'ri-file-text-line', [], [], [], ['can' => 'superadm']),
                    ]),
                    self::group('catalogos', 'Catálogos', 'ri-price-tag-3-line', [
                        self::item('categorias', 'Categorias e motivos', 'admin.category.main', 'ri-price-tag-3-line', [], [], [], ['can' => 'superadm']),
                    ]),
                ],
            ],
            'dados' => [
                'label'  => 'Dados',
                'icon'   => 'ri-database-2-line',
                'hint'   => 'Controle e auditoria',
                'groups' => [
                    self::group('controle', 'Controle de dados', 'ri-database-2-line', [
                        self::item('d5', 'Notas D5', 'admin.control.d5', 'ri-sticky-note-line'),
                        self::item('viabilidade', 'Viabilidade', 'admin.control.viability', 'ri-radar-line'),
                        self::item('notas', 'Notas', 'admin.control.notes', 'ri-article-line'),
                        self::item('informe-obra', 'Informe de obra', 'admin.control.workreports', 'ri-file-list-3-line'),
                        self::item('informe-parcial', 'Informe parcial', 'admin.control.partials', 'ri-file-list-line'),
                    ]),
                    self::group('auditoria', 'Auditoria', 'ri-file-search-line', [
                        self::item('auditoria-notas', 'Auditoria de notas', 'admin.audits.notes', 'ri-file-search-line'),
                    ]),
                ],
            ],
            'configuracoes' => [
                'label'  => 'Configurações',
                'icon'   => 'ri-settings-3-line',
                'hint'   => 'Como o sistema se comporta',
                'groups' => [
                    self::group('atividades', 'Atividades e painéis', 'ri-stack-line', [
                        self::item('servicos', 'Configurar serviços', 'config.services', 'ri-settings-3-line', ['config.main']),
                        self::item('wall', 'Wall de produção', 'config.wall.index', 'ri-tv-2-line', ['config.wall.'], [], [], ['can' => 'superadm']),
                        self::item('wall-v2', 'Abrir o Wall (V2)', 'reports.wall.production_v2', 'ri-external-link-line', [], [], ['wall' => 1], ['can' => 'superadm', 'target' => '_blank']),
                    ]),
                    self::group('informes', 'Informes e análises', 'ri-file-list-3-line', [
                        self::item('termo', 'Termo de aceite do informe', 'config.acceptance_terms', 'ri-file-text-line', [], [], [], ['can' => 'superadm']),
                        self::item('avisos', 'Avisos legais do painel do parceiro', 'config.partner_dashboard_legal_notes', 'ri-shield-user-line', [], [], [], ['can' => 'superadm']),
                        self::item('encerramento-analise', 'Encerramento de análise', 'config.analysis_closure_rules', 'ri-checkbox-circle-line', [], [], [], ['can' => 'superadm']),
                    ]),
                    self::group('integracoes', 'Integrações', 'ri-mail-send-line', [
                        self::item('ads-destinatarios', 'Destinatários das requisições ADS', 'config.ads_request_recipients', 'ri-mail-send-line'),
                        self::item('ads-monitor', 'Monitor de requisições ADS', 'admin.control.ads_requests', 'ri-survey-line', [], [], [], ['can' => 'superadm']),
                        self::item('tokens', 'Tokens de API', 'config.system.api_tokens', 'ri-key-2-line'),
                    ]),
                ],
            ],
            'sistema' => [
                'label'  => 'Sistema',
                'icon'   => 'ri-server-line',
                'hint'   => 'Saúde e operação',
                'groups' => [
                    self::group('monitoramento', 'Monitoramento', 'ri-computer-line', [
                        self::item('status', 'Status do servidor', 'config.system.status', 'ri-server-line'),
                        self::item('producao', 'Monitor de produção', 'monitor.services', 'ri-computer-line', [], [], [], ['can' => 'management']),
                        self::item('historico', 'Histórico de execuções', 'config.system.history', 'ri-history-line'),
                        self::item('jobs', 'Jobs', 'config.system.jobs_view', 'ri-list-check-2'),
                        self::item('scheduler', 'Scheduler', 'config.system.schedule', 'ri-timer-line', [], [], [], ['can' => 'superadm']),
                        self::item('sqlserver', 'Saúde do SQL Server', 'config.system.sqlsrv_health', 'ri-database-2-line', [], [], [], ['can' => 'superadm']),
                    ]),
                    self::group('arquivos', 'Arquivos', 'ri-folder-2-line', [
                        self::item('arquivos', 'Gerenciamento de arquivos', 'files.main', 'ri-folder-2-line'),
                    ]),
                ],
            ],
        ];
    }

    /**
     * Nós do dropdown do topo (ADMINISTRAÇÃO), gerados da mesma definição: módulo → grupo → itens.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function topNodes(): array
    {
        $item = fn (array $i) => array_filter([
            'label'       => mb_strtoupper($i['label']),
            'route'       => $i['route'],
            'routeParams' => $i['params'] ?: null,
            'icon'        => $i['icon'],
            'can'         => $i['can'] ?? null,
        ]);

        $nodes = [];

        foreach (static::resolve() as $module) {
            $groups = [];

            foreach ($module['groups'] as $group) {
                $groups[] = [
                    'kind'  => 'group',
                    'label' => mb_strtoupper($group['label']),
                    'open'  => 'side',
                    'nodes' => array_map($item, $group['items']),
                ];
            }

            $nodes[] = ['kind' => 'group', 'label' => mb_strtoupper($module['label']), 'open' => 'side', 'nodes' => $groups];
        }

        return $nodes;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<string, mixed>
     */
    private static function group(string $key, string $label, string $icon, array $items): array
    {
        return ['key' => $key, 'label' => $label, 'icon' => $icon, 'items' => $items];
    }
}
