<?php

namespace App\Support\Menu;

use App\Models\User;

/**
 * Definição ÚNICA do menu superior (docs/MenuSuperior.md): a ordem segue o fluxo de trabalho e cada assunto mora em um só lugar.
 *
 *   ATIVIDADES · OCORRÊNCIAS · ENGENHARIA · RESPONSÁVEL · JURÍDICO · CONSULTAS · RELATÓRIOS · ADMINISTRAÇÃO
 *
 * Cada menu devolve `['key', 'title', 'width', 'nodes'|'sections']` já com o `can` de cada item; quem renderiza (`<x-menu.dynamic-dropdown>`)
 * esconde o que o usuário não pode ver e o menu inteiro quando fica vazio. Contadores usam `count => 'chave'` (ver `TopbarCounts`).
 */
final class TopbarNav
{
    /** Ordem das atividades dentro de cada fase (pasta da atividade). Atividade nova entra no fim até ser posicionada aqui. */
    public const FLOW_ORDER = [
        'cadastro', 'pre_analise', 'analises_pre', 'analises', 'analises_inc', 'desenho', 'oexterno', 'fluxo_reverso',
        'contratacao', 'levantamento', 'comissionamento', 'fiscalizacao', 'publicacao', 'pagamento',
        'encerramento',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function menus(?User $user): array
    {
        if (!$user) {
            return [];
        }

        return array_values(array_filter([
            self::activities($user),
            self::occurrences(),
            self::engineering(),
            self::responsible(),
            self::legal(),
            self::consultations($user),
            self::reports(),
            self::admin(),
        ], fn (?array $menu) => $menu !== null && ($menu['nodes'] ?? $menu['sections'] ?? []) !== []));
    }

    // ---------- ATIVIDADES: por fase, uma linha por atividade com as ações rápidas ----------

    /** @return array<string, mixed> */
    private static function activities(User $user): array
    {
        $links = $user->ToServices()->with('Service')->get()->filter(fn ($link) => $link->Service);

        $canDispatch = $user->can('operator');
        $canWork     = $user->can('user');

        $row = function ($link) use ($canDispatch, $canWork): ?array {
            $service  = $link->Service;
            $dispatch = $canDispatch && $link->dispatch;
            $work     = $canWork && $link->service;

            if (!$dispatch && !$work) {
                return null;
            }

            $params   = ['service' => $service->uuid];
            $queue    = $service->folder === 'contratacao' ? 'construction.main' : 'services.main';
            $despacho = ['label' => 'Despacho', 'route' => 'dispatch.main', 'routeParams' => $params, 'icon' => 'ri-send-plane-line', 'tone' => 'danger'];
            $extras   = [
                ['label' => 'Controle', 'route' => 'dispatch.stack', 'routeParams' => $params, 'icon' => 'ri-radar-line', 'iconOnly' => true],
                ['label' => 'Dashboard', 'route' => 'dispatch.dashboard', 'routeParams' => $params, 'icon' => 'ri-bar-chart-2-line', 'iconOnly' => true],
            ];

            // O nome leva ao que a pessoa mais usa: a fila de quem trabalha; o despacho de quem só despacha.
            return [
                'label'       => mb_strtoupper($service->service),
                'route'       => $work ? $queue : 'dispatch.main',
                'routeParams' => $params,
                'icon'        => $service->icon,
                'iconClass'   => $work ? null : 'is-danger',
                'count'       => $work ? 'service:' . $service->uuid : null,
                'actions'     => $dispatch ? ($work ? [$despacho, ...$extras] : $extras) : [],
                'folder'      => (string) $service->folder,
                'phase'       => $service->post_construction ? 'post' : ($service->construction ? 'construction' : ($service->project ? 'project' : null)),
            ];
        };

        $rows = $links->map($row)->filter()->sortBy(function ($r) {
            $pos = array_search($r['folder'], self::FLOW_ORDER, true);

            return sprintf('%03d-%s', $pos === false ? 999 : $pos, $r['label']);
        })->values();

        $phase = fn (string $key, string $label, array $extra = []) => [
            'kind'  => 'group',
            'label' => $label,
            'open'  => true,
            'nodes' => array_merge($extra, $rows->where('phase', $key)->map(fn ($r) => collect($r)->except(['folder', 'phase'])->all())->values()->all()),
        ];

        $groups = [
            $phase('project', 'PROJETO', [
                ['label' => 'QUALIDADE', 'route' => 'quality.index', 'icon' => 'ri-shield-check-line', 'can' => 'quality.menu'],
                ['label' => 'ANÁLISE DE PROJETO', 'route' => 'project_review.list', 'icon' => 'ri-file-search-line', 'can' => 'analyst'],
            ]),
            $phase('construction', 'CONSTRUÇÃO'),
            $phase('post', 'PÓS-OBRA'),
        ];

        return [
            'key'   => 'atividades',
            'title' => 'ATIVIDADES',
            'width' => '430px',
            'nodes' => array_values(array_filter($groups, fn ($g) => $g['nodes'] !== [])),
        ];
    }

    // ---------- OCORRÊNCIAS: o que foge do fluxo normal ----------

    /** @return array<string, mixed> */
    private static function occurrences(): array
    {
        return [
            'key'   => 'ocorrencias',
            'title' => 'OCORRÊNCIAS',
            'width' => '320px',
            'nodes' => [
                [
                    'kind'  => 'group',
                    'label' => 'RECLAMAÇÕES',
                    'open'  => true,
                    'nodes' => [
                        ['label' => 'DESPACHO', 'route' => 'protests.dispatch.lists', 'icon' => 'ri-account-pin-box-fill', 'iconClass' => 'is-danger', 'can' => 'can_dispatch'],
                        ['label' => 'MINHA FILA', 'route' => 'protests.services.main', 'icon' => 'ri-account-pin-box-line', 'count' => 'protests'],
                    ],
                ],
                ['label' => 'OCORRÊNCIAS', 'route' => 'occurrences.index', 'icon' => 'ri-alarm-warning-line', 'can' => 'occ.access'],
                ['label' => 'CANCELAMENTO DE NOTAS', 'route' => 'cancellations.index', 'icon' => 'ri-close-circle-line'],
            ],
        ];
    }

    // ---------- Papéis de aprovação ----------

    /** @return array<string, mixed> */
    private static function engineering(): array
    {
        $counts = ['INFORMES' => 'engineer.informes', 'CANCELAMENTOS' => 'engineer.cancellations'];
        $items  = array_map(
            fn (array $item) => collect($item)->except(['countComponent', 'countParams', 'countKey'])->all() + ['can' => 'engineer'] + (isset($counts[$item['label']]) ? ['count' => $counts[$item['label']]] : []),
            EngineerNav::topItems()
        );

        // O informe SMC (BT Zero) é um informe: mora junto dos informes da Engenharia, só para quem tem o acesso.
        foreach (BtzeroNav::topItems() as $smc) {
            $items[] = $smc + ['can' => 'superadm'];
        }

        return ['key' => 'engenharia', 'title' => 'ENGENHARIA', 'width' => '300px', 'nodes' => $items];
    }

    /** @return array<string, mixed> */
    private static function responsible(): array
    {
        return [
            'key'   => 'responsavel',
            'title' => 'RESPONSÁVEL',
            'width' => '300px',
            'nodes' => array_map(fn (array $item) => $item + ['can' => 'responsible'], ResponsibleNav::topItems()),
        ];
    }

    /** @return array<string, mixed> */
    private static function legal(): array
    {
        return ['key' => 'juridico', 'title' => 'JURÍDICO', 'width' => '320px', 'sections' => LegalNav::topSections()];
    }

    // ---------- CONSULTAS: ler e localizar, sem alterar nada ----------

    /** @return array<string, mixed> */
    private static function consultations(User $user): array
    {
        $informesVisible = !$user->toServices->contains(fn ($s) => $s->service && $s->Service?->service === 'Publicação')
            || $user->operator || $user->responsible || $user->engineer || $user->management || $user->admin || $user->superadm;

        $search = fn (string $label, string $route, array $extra = []) => ['label' => $label, 'route' => $route, 'icon' => 'ri-search-eye-line'] + $extra;

        return [
            'key'   => 'consultas',
            'title' => 'CONSULTAS',
            'width' => '320px',
            'nodes' => [
                ['kind' => 'header', 'label' => 'Notas'],
                $search('NOTAS/OVS', 'reports.search'),
                $search('CONSULTA D5', 'reports.consulta_d5'),
                $search('BUSCA AVANÇADA', 'reports.advancedsearch', ['can' => 'management']),
                ['kind' => 'header', 'label' => 'Informes', 'visible' => $informesVisible],
                $search('INFORMES', 'reports.workreport', ['visible' => $informesVisible]),
                $search('INFORMES REJEITADOS', 'reports.rejecetedWorkreport', ['visible' => $informesVisible && !$user->onlyparner]),
                ...array_map(fn (array $item) => $item + ['visible' => $informesVisible], AdsNav::topItems()),
                $search('EQUIPAMENTOS DECLARADOS', 'reports.equipments', ['icon' => 'ri-tools-line', 'visible' => $informesVisible]),
                ['kind' => 'header', 'label' => 'Contratação', 'visible' => $informesVisible],
                $search('SITUAÇÃO DE CONTRATAÇÃO', 'reports.lookatnotes', ['visible' => $informesVisible]),
            ],
        ];
    }

    // ---------- RELATÓRIOS: gerencial ----------

    /** @return array<string, mixed> */
    private static function reports(): array
    {
        $groups = [
            'Gestão'      => ['reports.team_panel', 'reports.general_sicode', 'reports.productions', 'reports.project_deadlines'],
            'Projeto'     => ['reports.viabilities', 'reports.project_review_dashboard'],
            'Execução'    => ['reports.five_notes', 'reports.post_work_process', 'reports.return_intern', 'reports.return_work_reports'],
            'Atendimento' => ['reports.cancellations_dashboard', 'reports.complaints_mede'],
        ];

        $links = collect(ReportsNav::topLinks())->keyBy('route');
        $nodes = [];

        foreach ($groups as $label => $routes) {
            $items = collect($routes)->map(fn ($route) => $links->get($route))->filter()->map(fn (array $l) => ['label' => $l['label'], 'route' => $l['route'], 'can' => $l['can'], 'icon' => 'ri-file-chart-line'])->values()->all();

            if ($items !== []) {
                $nodes[] = ['kind' => 'header', 'label' => $label];
                array_push($nodes, ...$items);
            }
        }

        return ['key' => 'relatorios', 'title' => 'RELATÓRIOS', 'width' => '340px', 'nodes' => $nodes];
    }

    /** @return array<string, mixed> */
    private static function admin(): array
    {
        return ['key' => 'administracao', 'title' => 'ADMINISTRAÇÃO', 'width' => '320px', 'panelTitle' => 'ADMINISTRAÇÃO', 'nodes' => array_map(fn (array $n) => $n + ['can' => $n['can'] ?? 'admin'], AdminNav::topNodes())];
    }
}
