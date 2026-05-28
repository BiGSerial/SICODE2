<style>
    .dropdown-menu-custom {
        max-height: 600px;
        /* Ajuste a altura máxima conforme necessário */
        overflow-y: auto;
    }

    /* Custom scrollbar styles */
    .dropdown-menu-custom::-webkit-scrollbar {
        width: 8px;
        /* Largura da scrollbar */
    }

    .dropdown-menu-custom::-webkit-scrollbar-track {
        background: #dbd8d8;
        /* Cor de fundo da track da scrollbar */
    }

    .dropdown-menu-custom::-webkit-scrollbar-thumb {
        background-color: #888;
        /* Cor da barra de rolagem */
        border-radius: 10px;
        /* Bordas arredondadas */
        border: 2px solid #dbd8d8;
        /* Espaçamento entre a scrollbar e o conteúdo */
    }

    .dropdown-menu-custom::-webkit-scrollbar-thumb:hover {
        background: #555;
        /* Cor da barra de rolagem ao passar o mouse */
    }
</style>

@can('admin')
    @php
        $admin_sections = [
            [
                'children' => [
                    [
                        'label' => 'SICODE',
                        'open' => 'side',
                        'items' => [
                            ['label' => 'USUÁRIOS', 'route' => 'admin.user.list', 'icon' => 'ri-account-pin-box-fill'],
                            ['label' => 'EMPRESAS', 'route' => 'admin.company.list', 'icon' => 'ri-building-4-fill', 'can' => 'superadm'],
                            ['label' => 'CATEGORIAS', 'route' => 'admin.category.main', 'icon' => 'ri-price-tag-3-fill', 'can' => 'superadm'],
                        ],
                    ],
                    [
                        'label' => 'GERENCIAMENTO',
                        'open' => 'side',
                        'items' => [
                            ['label' => 'AUDITORIA NOTAS', 'route' => 'admin.audits.notes', 'icon' => 'ri-file-search-line'],
                            ['label' => 'CONTROLE DE DADOS', 'route' => 'admin.control.d5', 'icon' => 'ri-database-2-line', 'can' => 'superadm'],
                            ['label' => 'GERENCIAMENTO ADS', 'route' => 'admin.control.ads_requests', 'icon' => 'ri-survey-line', 'can' => 'superadm'],
                            ['label' => 'GERENCIAMENTO DE ARQUIVOS', 'route' => 'files.main', 'icon' => 'ri-folder-2-line'],
                            ['label' => 'MONITOR ATIVIDADE', 'route' => 'monitor.services', 'icon' => 'ri-computer-line', 'can' => 'management'],
                            ['label' => 'PAINEL CONFIGURAÇÕES', 'route' => 'config.main', 'icon' => 'ri-home-gear-fill'],
                            ['label' => 'STATUS SERVER', 'route' => 'config.system.status', 'icon' => 'ri-server-line'],
                            ['label' => 'LOG LOG', 'route' => 'config.system.history', 'icon' => 'ri-file-list-3-line'],
                            ['label' => 'SCHEDULE', 'route' => 'config.system.schedule', 'icon' => 'ri-calendar-schedule-line', 'can' => 'superadm'],
                        ],
                    ],
                ],
            ],
        ];
    @endphp
    <x-menu.dynamic-dropdown title="ADMINISTRAÇÃO" :sections="$admin_sections" id-prefix="administracao" layout="inline" panel-title="ADMINISTRAÇÃO" />
@endcan

@php
    $protests_nodes = [
        [
            'kind' => 'group',
            'label' => 'DESPACHO',
            'open' => 'side',
            'can' => 'can_dispatch',
            'nodes' => [
                ['label' => 'RECLAMAÇÕES', 'route' => 'protests.dispatch.lists', 'icon' => 'ri-account-pin-box-fill', 'iconClass' => 'text-danger'],
            ],
        ],
        [
            'kind' => 'group',
            'label' => 'SERVIÇO',
            'open' => 'side',
            'nodes' => [
                [
                    'label' => 'RECLAMAÇÕES',
                    'route' => 'protests.services.main',
                    'icon' => 'ri-account-pin-box-fill',
                    'countComponent' => 'components.count.protest.count-protests',
                    'countKey' => 'menu_protests_count',
                ],
            ],
        ],
    ];
@endphp
<x-menu.dynamic-dropdown title="RECLAMAÇÕES" :nodes="$protests_nodes" id-prefix="reclamacoes" item-class="mx-2 position-relative" layout="inline">
    <x-slot:triggerAppend>
        @livewire('components.count.protest.has-protests', key('menu_protests'))
    </x-slot:triggerAppend>
</x-menu.dynamic-dropdown>

@php
    $reports_links = [
        ['route' => 'reports.productions', 'label' => 'RELATÓRIO DE PRODUÇÃO', 'visible' => Auth()->user()->can('management')],
        ['route' => 'reports.viabilities', 'label' => 'RELATÓRIO DE VIABILIDADE', 'visible' => Auth()->user()->can('management')],
        ['route' => 'reports.return_intern_dashboard', 'label' => 'RELATORIO RETORNO INTERNO', 'visible' => Auth()->user()->can('management')],
        ['route' => 'reports.cancellations_dashboard', 'label' => 'RELATÓRIO CANCELAMENTOS', 'visible' => Auth()->user()->can('management')],
        ['route' => 'reports.return_work_reports', 'label' => 'INFORMES REJEITADOS (RETURNWORK)', 'visible' => Auth()->user()->can('management')],
        ['route' => 'reports.complaints_mede', 'label' => 'RELATÓRIO DE RECLAMAÇÃO', 'visible' => Auth()->user()->can('management')],
        ['route' => 'reports.five_notes', 'label' => 'RELATÓRIO NOTAS D5', 'visible' => Auth()->user()->can('management')],
        ['route' => 'reports.project_review_dashboard', 'label' => 'RELATÓRIO ANÁLISE PROJETOS', 'visible' => Auth()->user()->can('projectReviewReports')],
        ['route' => 'reports.advancedsearch', 'label' => 'BUSCAR AVANÇADA', 'visible' => Auth()->user()->can('management')],
    ];
@endphp

@can('responsible')
    <x-menu.responsible-dropdown />
@endcan

@can('engineer')
    <x-menu.engineer-dropdown />
@endcan

@can('superadm')
    @php
        $smc_sections = [
            [
                'label' => 'SMC',
                'items' => [
                    ['label' => 'INFORME SMC', 'route' => 'btzero.main', 'icon' => 'ri-eye-fill'],
                ],
            ],
        ];
    @endphp
    <x-menu.dynamic-dropdown title="SMC" :sections="$smc_sections" width="300px" id-prefix="smc" layout="inline" />
@endcan


@php

    $menu_projeto = Auth()->User()->ToServices->isNotEmpty()
        ? Auth()
            ->User()
            ->ToServices->filter(function ($service) {
                return ($service->service || $service->dispatch) && $service->Service->project;
            })
            ->count()
        : null;

    $menu_construcao = Auth()->User()->ToServices->isNotEmpty()
        ? Auth()
            ->User()
            ->ToServices->filter(function ($service) {
                return ($service->service || $service->dispatch) && $service->Service->construction;
            })
            ->count()
        : null;

    $payment_service = Auth()->User()->ToServices->first(function ($service) {
        return $service->service && $service->Service && $service->Service->folder === 'pagamento';
    });

@endphp



@if ($menu_projeto || $menu_construcao || Auth()->user()->can('management'))
    <x-menu.activities-dropdown
        :menu-projeto="$menu_projeto"
        :menu-construcao="$menu_construcao"
    />
@endif

@if (Auth::check())
    <x-menu.services-dropdown
        :payment-service="$payment_service"
        :reports-links="$reports_links"
    />
@endif




@php
    $can_view_workreports = !Auth()->user()->toServices->contains(function ($service) {
        return $service->service && isset($service->Service) && $service->Service->service === 'Publicação';
    }) || (Auth()->user()->operator ||
        Auth()->user()->responsible ||
        Auth()->user()->engineer ||
        Auth()->user()->management ||
        Auth()->user()->admin ||
        Auth()->user()->superadm);

    $search_sections = [
        [
            'items' => [
                ['label' => 'NOTAS/OVS', 'route' => 'reports.search', 'icon' => 'ri-search-eye-line'],
                ['label' => 'CONSULTA D5', 'route' => 'reports.consulta_d5', 'icon' => 'ri-search-eye-line'],
                ['label' => 'INFORMES', 'route' => 'reports.workreport', 'icon' => 'ri-search-eye-line', 'visible' => $can_view_workreports],
                ['label' => 'ADS SOLICITADAS', 'route' => 'ads.dashboard', 'icon' => 'ri-survey-line', 'visible' => $can_view_workreports],
                ['label' => 'SITUAÇÃO DE CONTRATAÇÃO', 'route' => 'reports.lookatnotes', 'icon' => 'ri-search-eye-line', 'visible' => $can_view_workreports],
                ['label' => 'INFORMES REJEITADOS', 'route' => 'reports.rejecetedWorkreport', 'icon' => 'ri-search-eye-line', 'visible' => $can_view_workreports && !Auth()->user()->onlyparner],
                ['label' => 'EQUIPAMENTOS DECLARADOS', 'route' => 'reports.equipments', 'icon' => 'ri-tools-line', 'visible' => $can_view_workreports],
            ],
        ],
    ];
@endphp
<x-menu.dynamic-dropdown title="BUSCAR" :sections="$search_sections" id-prefix="buscar" layout="inline" />
