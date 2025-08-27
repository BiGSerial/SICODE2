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
        <a class="nav-link dropdown-toggle text-edp-verde nav-profile" href="#" role="button" data-bs-toggle="dropdown"
            aria-expanded="false">
            ADMINISTRAÇÃO
        </a>
        <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2 dropdown-menu-custom"
            style="background-color: #dbd8d8">
            <li><a class="dropdown-item" href="{{ route('admin.user.list') }}"><i
                        class="ri-account-pin-box-fill align-middle text-primary"></i>USUARIOS</a>
            </li>
            @can('superadm')
                <li><a class="dropdown-item" href="{{ route('admin.company.list') }}"><i
                            class="ri-building-4-fill align-middle text-primary"></i>EMPRESAS</a></li>
                <li>
                <li><a class="dropdown-item" href="{{ route('admin.category.main') }}"><i
                            class="ri-price-tag-3-fill align-middle text-primary"></i>CATEGORIAS</a></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="{{ route('config.main') }}"><i
                            class="ri-home-gear-fill align-middle text-dark"></i>CONFIGURAÇÕES</a></li>
            @endcan

        </ul>
    </li>
@endcan

<li class="nav-item dropdown mx-2 position-relative">
    <a class="nav-link dropdown-toggle text-edp-verde nav-profile" href="#" role="button"
        data-bs-toggle="dropdown" aria-expanded="false">
        RECLAMAÇÕES

    </a>
    @livewire('components.count.protest.has-protests', key('menu_protests'))
    <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2 dropdown-menu-custom"
        style="background-color: #dbd8d8">
        @can('operator')
            @once
                <li style="background-color: #ffffff; color: white;">
                    <h6 class="dropdown-header">DESPACHOS</h6>
                </li>
            @endonce
            <li><a class="dropdown-item" href="{{ route('protests.dispatch.lists') }}"><i
                        class="ri-account-pin-box-fill align-middle text-danger"></i>RECLAMAÇÕES</a>

            </li>
        @endcan

        @once
            <li style="background-color: #ffffff; color: white;">
                <h6 class="dropdown-header">SERVIÇOS</h6>
            </li>
        @endonce
        <li><a class="dropdown-item" href="{{ route('protests.services.main') }}"><i
                    class="ri-account-pin-box-fill align-middle text-primary"></i>RECLAMAÇÕES @livewire('components.count.protest.count-protests', key('menu_protests_count'))</a>

        </li>

    </ul>
</li>

@can('management')
    <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle text-edp-verde nav-profile" href="#" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            MONITORIA
        </a>
        <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2" style="background-color: #dbd8d8">
            <li><a class="dropdown-item" href="{{ route('reports.productions') }}"><i
                        class="ri-git-repository-line align-middle text-primary"></i>RELATÓRIO DE PRODUÇÃO</a>
            </li>
            <li><a class="dropdown-item" href="{{ route('reports.viabilities') }}"><i
                        class="ri-git-repository-line align-middle text-primary"></i>RELATÓRIO DE VIABILIDADE</a>
            </li>
            <li><a class="dropdown-item" href="{{ route('reports.advancedsearch') }}"><i
                        class="ri-search-eye-line align-middle text-primary"></i>BUSCAR AVANÇADA</a>
            </li>
            <li><a class="dropdown-item" href="{{ route('monitor.services') }}"><i
                        class="ri-computer-line align-middle text-dark"></i>MONITOR - ATIVIDADE</a></li>
            <li><a class="dropdown-item" href="{{ route('monitor.analises') }}"><i
                        class="ri-computer-line align-middle text-dark"></i>GRAFICO - ATIVIDADE</a></li>

            <li><a class="dropdown-item" href="{{ route('files.main') }}"><i
                        class="ri-file-2-line align-middle text-dark"></i>GERENCIAMENTO DE ARQUIVOS</a></li>

            @if (!Auth()->User()->contract)
                <li><a class="dropdown-item" href="{{ route('monitor.inconsistency') }}"><i
                            class="ri-alert-line align-middle text-danger"></i>INCONSISTÊNCIAS</a></li>
                @can('superadm')
                    <li><a class="dropdown-item" href="{{ route('tests.page') }}"><i
                                class="ri-computer-line align-middle text-danger"></i>COMMANDOS DIRETO</a></li>
                    <li><a class="dropdown-item" href="{{ route('monitor.logsupdate') }}"><i
                                class="ri-computer-line align-middle text-danger"></i>LOGGER UDATES</a></li>
                @endcan
            @endif
        </ul>
    </li>
@endcan

@can('responsible')
    <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle text-edp-verde nav-profile" href="#" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            RESPONSÁVEL
        </a>
        <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2"
            style="background-color: #dbd8d8; width: 300px;">
            <li><a class="dropdown-item" href="{{ route('responsible.main') }}"><i
                        class="ri-eye-fill align-middle text-info"></i> VIABILIDADE</a>
            </li>

        </ul>
    </li>
@endcan

@can('engineer')
    <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle text-edp-verde nav-profile" href="#" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            ENGENHARIA
        </a>
        <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2"
            style="background-color: #dbd8d8; width: 300px;">
            <li><a class="dropdown-item" href="{{ route('engineers.main') }}"><i
                        class="ri-eye-fill align-middle text-info"></i> CONTROLE</a>
            </li>

        </ul>
    </li>
@endcan

@can('btzero')
    <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle text-edp-verde nav-profile" href="#" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            SMC
        </a>
        <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2"
            style="background-color: #dbd8d8; width: 300px;">
            <li><a class="dropdown-item" href="{{ route('btzero.main') }}"><i
                        class="ri-eye-fill align-middle text-info"></i> INFORME SMC</a>
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

@endphp



@if ($menu_projeto)
    <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle text-edp-verde nav-profile" href="#" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            PROJETO
        </a>
        <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2 dropdown-menu-custom"
            style="background-color: #dbd8d8; width: 300px;">
            @can('operator')
                @foreach (Auth()->User()->ToServices as $service)
                    @if ($service->dispatch && $service->Service->project)
                        @once
                            <li style="background-color: #ffffff; color: white;">
                                <h6 class="dropdown-header">DESPACHOS</h6>
                            </li>
                        @endonce
                        <li><a class="dropdown-item"
                                href="{{ route('dispatch.main', ['service' => $service->service_id]) }}"><i
                                    class="{{ $service->Service->icon }} align-middle text-danger"></i>{{ mb_strToUpper($service->Service->service) }}</a>
                        </li>
                    @endif
                @endforeach
            @endcan




            @can('user')
                <li>
                    <hr class="dropdown-divider">
                </li>

                @foreach (Auth()->User()->ToServices as $service)
                    @if ($service->service && $service->Service->project)
                        @once
                            <li style="background-color: #ffffff; color: white;">
                                <h6 class="dropdown-header">SERVIÇOS</h6>
                            </li>
                        @endonce
                        <li><a class="dropdown-item"
                                href="{{ route('services.main', ['service' => $service->service_id]) }}">
                                <div class="d-flex align-items-center">
                                    <i class="{{ $service->Service->icon }} text-primary"></i>
                                    <span>{{ mb_strtoupper($service->Service->service) }}</span>
                                    @livewire('components.count.countnotes', ['service' => $service->service_id], key('menu' . $service->service_id))
                                </div>
                            </a>
                        </li>
                    @endif
                @endforeach
            @endcan

        </ul>
    </li>
@endif

@if ($menu_construcao)
    <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle text-edp-verde nav-profile" href="#" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            CONSTRUÇÃO
        </a>
        <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2 dropdown-menu-custom"
            style="background-color: #dbd8d8; width: 300px;">

            @can('operator')
                @foreach (Auth()->User()->ToServices as $service)
                    @if ($service->dispatch && $service->Service->construction)
                        @once
                            <li style="background-color: #ffffff; color: white;">
                                <h6 class="dropdown-header">DESPACHOS</h6>
                            </li>
                        @endonce
                        <li><a class="dropdown-item"
                                href="{{ route('dispatch.main', ['service' => $service->service_id]) }}"><i
                                    class="{{ $service->Service->icon }} align-middle text-danger"></i>{{ mb_strToUpper($service->Service->service) }}</a>
                        </li>
                    @endif
                @endforeach
            @endcan




            @can('user')
                <li>
                    <hr class="dropdown-divider">
                </li>

                @foreach (Auth()->User()->ToServices as $service)
                    @if ($service->service && $service->Service->construction)
                        @once
                            <li style="background-color: #ffffff; color: white;">
                                <h6 class="dropdown-header">SERVIÇOS</h6>
                            </li>
                        @endonce
                        <li><a class="dropdown-item"
                                href="{{ route('services.main', ['service' => $service->service_id]) }}">
                                <div class="d-flex align-items-center">
                                    <i class="{{ $service->Service->icon }} text-primary"></i>
                                    <span>{{ mb_strtoupper($service->Service->service) }}</span>
                                    @livewire('components.count.countnotes', ['service' => $service->service_id], key('menu' . $service->service_id))
                                </div>
                            </a>
                        </li>
                    @endif
                @endforeach
            @endcan

        </ul>
    </li>
@endif




<li class="nav-item dropdown mx-2">
    <a class="nav-link dropdown-toggle text-edp-verde nav-profile" href="#" role="button"
        data-bs-toggle="dropdown" aria-expanded="false">
        BUSCAR
    </a>

    <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2" style="background-color: #dbd8d8">
        <li><a class="dropdown-item" href="{{ route('reports.search') }}"><i
                    class="ri-search-eye-line align-middle text-primary"></i>NOTAS/OVS</a>
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
            <li><a class="dropdown-item" href="{{ route('reports.workreport') }}"><i
                        class="ri-search-eye-line align-middle text-primary"></i>INFORMES</a>
            </li>

            <li><a class="dropdown-item" href="{{ route('reports.lookatnotes') }}"><i
                        class="ri-search-eye-line align-middle text-primary"></i>SITUAÇÃO DE CONTRATAÇÃO</a>
            </li>
            @if (!Auth()->User()->onlyparner)
                <li><a class="dropdown-item" href="{{ route('reports.rejecetedWorkreport') }}"><i
                            class="ri-search-eye-line align-middle text-primary"></i>INFORMES REJEITADOS</a>
                </li>
            @endif
        @endif
    </ul>

</li>
