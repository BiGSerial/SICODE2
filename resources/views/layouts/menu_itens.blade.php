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
                'label' => 'SICODE',
                'items' => [
                    ['label' => 'USUÁRIOS', 'route' => 'admin.user.list', 'icon' => 'ri-account-pin-box-fill'],
                    ['label' => 'EMPRESAS', 'route' => 'admin.company.list', 'icon' => 'ri-building-4-fill', 'can' => 'superadm'],
                    ['label' => 'CATEGORIAS', 'route' => 'admin.category.main', 'icon' => 'ri-price-tag-3-fill', 'can' => 'superadm'],
                ],
            ],
            [
                'label' => 'GERENCIAMENTO',
                'children' => [
                    [
                        'label' => 'AUDITORIA',
                        'items' => [
                            ['label' => 'AUDITORIA NOTAS', 'route' => 'admin.audits.notes', 'icon' => 'ri-file-search-line'],
                        ],
                    ],
                    [
                        'label' => 'CONTROLE',
                        'items' => [
                            ['label' => 'CONTROLE DE DADOS', 'route' => 'admin.control.d5', 'icon' => 'ri-database-2-line', 'can' => 'superadm'],
                            ['label' => 'GERENCIAMENTO DE ARQUIVOS', 'route' => 'files.main', 'icon' => 'ri-folder-2-line'],
                            ['label' => 'MONITOR ATIVIDADE', 'route' => 'monitor.services', 'icon' => 'ri-computer-line', 'can' => 'management'],
                            ['label' => 'PAINEL CONFIGURAÇÕES', 'route' => 'config.main', 'icon' => 'ri-home-gear-fill'],
                            ['label' => 'STATUS SERVER', 'route' => 'config.system.jobs_view', 'icon' => 'ri-server-line'],
                            ['label' => 'LOG LOG', 'route' => 'config.main', 'icon' => 'ri-file-list-3-line'],
                        ],
                    ],
                ],
            ],
        ];
    @endphp
    <x-menu.dynamic-dropdown title="ADMINISTRAÇÃO" :sections="$admin_sections" id-prefix="administracao" />
@endcan

@php
    $protests_sections = [
        [
            'label' => 'GERAL',
            'children' => [
                [
                    'label' => 'DESPACHOS',
                    'can' => 'can_dispatch',
                    'items' => [
                        ['label' => 'RECLAMAÇÕES', 'route' => 'protests.dispatch.lists', 'icon' => 'ri-account-pin-box-fill'],
                    ],
                ],
                [
                    'label' => 'SERVIÇOS',
                    'items' => [
                        [
                            'label' => 'RECLAMAÇÕES',
                            'route' => 'protests.services.main',
                            'icon' => 'ri-account-pin-box-fill',
                            'countComponent' => 'components.count.protest.count-protests',
                            'countKey' => 'menu_protests_count',
                        ],
                    ],
                ],
            ],
        ],
    ];
@endphp
<x-menu.dynamic-dropdown title="RECLAMAÇÕES" :sections="$protests_sections" id-prefix="reclamacoes" item-class="mx-2 position-relative">
    <x-slot:triggerAppend>
        @livewire('components.count.protest.has-protests', key('menu_protests'))
    </x-slot:triggerAppend>
</x-menu.dynamic-dropdown>

@php
    $reports_links = [
        ['route' => 'reports.productions', 'label' => 'RELATÓRIO DE PRODUÇÃO'],
        ['route' => 'reports.viabilities', 'label' => 'RELATÓRIO DE VIABILIDADE'],
        ['route' => 'reports.return_intern_dashboard', 'label' => 'RELATORIO RETORNO INTERNO'],
        ['route' => 'reports.advancedsearch', 'label' => 'BUSCAR AVANÇADA'],
    ];
@endphp

@can('responsible')
    <x-menu.responsible-dropdown />
@endcan

@can('engineer')
    <x-menu.engineer-dropdown />
@endcan

@can('btzero')
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
    <x-menu.dynamic-dropdown title="SMC" :sections="$smc_sections" width="300px" id-prefix="smc" />
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



@if ($menu_projeto || $menu_construcao)
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
            'label' => 'CONSULTAS',
            'items' => [
                ['label' => 'NOTAS/OVS', 'route' => 'reports.search', 'icon' => 'ri-search-eye-line'],
                ['label' => 'CONSULTA D5', 'route' => 'reports.consulta_d5', 'icon' => 'ri-search-eye-line'],
                ['label' => 'INFORMES', 'route' => 'reports.workreport', 'icon' => 'ri-search-eye-line', 'visible' => $can_view_workreports],
                ['label' => 'SITUAÇÃO DE CONTRATAÇÃO', 'route' => 'reports.lookatnotes', 'icon' => 'ri-search-eye-line', 'visible' => $can_view_workreports],
                ['label' => 'INFORMES REJEITADOS', 'route' => 'reports.rejecetedWorkreport', 'icon' => 'ri-search-eye-line', 'visible' => $can_view_workreports && !Auth()->user()->onlyparner],
                ['label' => 'EQUIPAMENTOS DECLARADOS', 'route' => 'reports.equipments', 'icon' => 'ri-tools-line', 'visible' => $can_view_workreports],
            ],
        ],
    ];
@endphp
<x-menu.dynamic-dropdown title="BUSCAR" :sections="$search_sections" id-prefix="buscar" />
