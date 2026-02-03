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
    <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle text-white nav-profile" href="#" role="button" data-bs-toggle="dropdown"
            aria-expanded="false">
            ADMINISTRAÇÃO
        </a>
        <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2 dropdown-menu-custom services-dropdown services-dropdown-menu"
            style="background-color: #dbd8d8">
            @include('components.menu.partials.services-dropdown-style')
            <li><a class="menu-link" href="{{ route('admin.user.list') }}"><i
                        class="ri-account-pin-box-fill align-middle text-primary"></i>USUÁRIOS</a>
            </li>
            @can('superadm')
                <li><a class="menu-link" href="{{ route('admin.company.list') }}"><i
                            class="ri-building-4-fill align-middle text-primary"></i>EMPRESAS</a></li>
                <li><a class="menu-link" href="{{ route('admin.category.main') }}"><i
                            class="ri-price-tag-3-fill align-middle text-primary"></i>CATEGORIAS</a></li>
                <li><a class="menu-link" href="{{ route('config.main') }}"><i
                            class="ri-home-gear-fill align-middle text-dark"></i>CONFIGURAÇÕES</a></li>
            @endcan
            <li>
                <hr class="dropdown-divider">
            </li>
            <li><a class="menu-link" href="{{ route('admin.audits.notes') }}"><i
                        class="ri-file-search-line align-middle text-primary"></i>AUDITORIA NOTAS</a>
            </li>
            <li><a class="menu-link" href="{{ route('admin.control.d5') }}"><i
                        class="ri-database-2-line align-middle text-primary"></i>CONTROLE DE DADOS</a>
            </li>
            <li><a class="menu-link" href="{{ route('monitor.services') }}"><i
                        class="ri-computer-line align-middle text-dark"></i>MONITOR - ATIVIDADE</a></li>
            <li><a class="menu-link" href="{{ route('monitor.analises') }}"><i
                        class="ri-computer-line align-middle text-dark"></i>GRAFICO - ATIVIDADE</a></li>
            @if (!Auth()->User()->contract)
                <li><a class="menu-link" href="{{ route('monitor.inconsistency') }}"><i
                            class="ri-alert-line align-middle text-danger"></i>INCONSISTÊNCIAS</a></li>
                @can('superadm')
                    <li><a class="menu-link" href="{{ route('tests.page') }}"><i
                                class="ri-computer-line align-middle text-danger"></i>COMMANDOS DIRETO</a></li>
                    <li><a class="menu-link" href="{{ route('monitor.logsupdate') }}"><i
                                class="ri-computer-line align-middle text-danger"></i>LOGGER UDATES</a></li>
                @endcan
            @endif

        </ul>
    </li>
@endcan

<li class="nav-item dropdown mx-2 position-relative">
    <a class="nav-link dropdown-toggle text-white nav-profile" href="#" role="button" data-bs-toggle="dropdown"
        data-bs-auto-close="outside" aria-expanded="false">
        RECLAMAÇÕES

    </a>
    @livewire('components.count.protest.has-protests', key('menu_protests'))
    <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2 dropdown-menu-custom services-dropdown services-dropdown-menu"
        style="background-color: #dbd8d8">
        @include('components.menu.partials.services-dropdown-style')
        @can('can_dispatch')
            <div class="submenu mb-2">
                <button class="submenu-toggle js-submenu-toggle" data-target="#submenu-protests-despachos"
                    type="button">
                    DESPACHOS <i class="ri-arrow-right-s-line"></i>
                </button>
                <div id="submenu-protests-despachos" class="submenu-panel">
                    <a class="dropdown-item" href="{{ route('protests.dispatch.lists') }}"><i
                            class="ri-account-pin-box-fill align-middle text-danger"></i>RECLAMAÇÕES</a>
                </div>
            </div>
        @endcan

        <div class="submenu">
            <button class="submenu-toggle js-submenu-toggle" data-target="#submenu-protests-servicos"
                type="button">
                SERVIÇOS <i class="ri-arrow-right-s-line"></i>
            </button>
            <div id="submenu-protests-servicos" class="submenu-panel">
                <a class="dropdown-item" href="{{ route('protests.services.main') }}"><i
                        class="ri-account-pin-box-fill align-middle text-primary"></i>RECLAMAÇÕES @livewire('components.count.protest.count-protests', key('menu_protests_count'))</a>
            </div>
        </div>

        @include('components.menu.partials.services-dropdown-script')
    </ul>
</li>

@php
    $reports_links = [
        ['route' => 'reports.productions', 'label' => 'RELATÓRIO DE PRODUÇÃO'],
        ['route' => 'reports.viabilities', 'label' => 'RELATÓRIO DE VIABILIDADE'],
        ['route' => 'reports.return_intern_dashboard', 'label' => 'RELATORIO RETORNO INTERNO'],
        ['route' => 'reports.advancedsearch', 'label' => 'BUSCAR AVANÇADA'],
        ['route' => 'files.main', 'label' => 'GERENCIAMENTO DE ARQUIVOS'],
    ];
@endphp

@can('responsible')
    <x-menu.responsible-dropdown />
@endcan

@can('engineer')
    <x-menu.engineer-dropdown />
@endcan

@can('btzero')
    <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle text-white nav-profile" href="#" role="button" data-bs-toggle="dropdown"
            aria-expanded="false">
            SMC
        </a>
        <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2 dropdown-menu-custom services-dropdown services-dropdown-menu"
            style="background-color: #dbd8d8; width: 300px;">
            @include('components.menu.partials.services-dropdown-style')
            <li>
                <a class="menu-single" href="{{ route('btzero.main') }}">
                    <i class="ri-eye-fill align-middle text-info"></i> INFORME SMC
                </a>
            </li>

        </ul>
    </li>
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




<li class="nav-item dropdown mx-2">
    <a class="nav-link dropdown-toggle text-white nav-profile" href="#" role="button"
        data-bs-toggle="dropdown" aria-expanded="false">
        BUSCAR
    </a>

    <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2 services-dropdown services-dropdown-menu" style="background-color: #dbd8d8">
        @include('components.menu.partials.services-dropdown-style')
        <li><a class="menu-link" href="{{ route('reports.search') }}"><i
                    class="ri-search-eye-line align-middle text-primary"></i>NOTAS/OVS</a>
        </li>
        <li><a class="menu-link" href="{{ route('reports.consulta_d5') }}"><i
                    class="ri-search-eye-line align-middle text-primary"></i>CONSULTA D5</a>
        </li>

        @if (
            !Auth()->user()->toServices->contains(function ($service) {
                    return $service->service && isset($service->Service) && $service->Service->service === 'Publicação';
                }) ||
                (Auth()->user()->operator ||
                    Auth()->user()->responsible ||
                    Auth()->user()->engineer ||
                    Auth()->user()->management ||
                    Auth()->user()->admin ||
                    Auth()->user()->superadm))
            <li><a class="menu-link" href="{{ route('reports.workreport') }}"><i
                        class="ri-search-eye-line align-middle text-primary"></i>INFORMES</a>
            </li>

            <li><a class="menu-link" href="{{ route('reports.lookatnotes') }}"><i
                        class="ri-search-eye-line align-middle text-primary"></i>SITUAÇÃO DE CONTRATAÇÃO</a>
            </li>
            @if (!Auth()->User()->onlyparner)
                <li><a class="menu-link" href="{{ route('reports.rejecetedWorkreport') }}"><i
                            class="ri-search-eye-line align-middle text-primary"></i>INFORMES REJEITADOS</a>
                </li>
            @endif
            <li><a class="menu-link" href="{{ route('reports.equipments') }}"><i
                        class="ri-tools-line align-middle text-primary"></i>EQUIPAMENTOS DECLARADOS</a>
            </li>
        @endif
    </ul>

</li>
