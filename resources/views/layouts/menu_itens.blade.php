@can('admin')
    <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle text-edp-verde nav-profile" href="#" role="button" data-bs-toggle="dropdown"
            aria-expanded="false">
            ADMINISTRAÇÃO
        </a>
        <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2" style="background-color: #dbd8d8">
            <li><a class="dropdown-item" href="{{ route('admin.user.list') }}"><i
                        class="ri-account-pin-box-fill align-middle text-primary"></i>USUARIOS</a>
            </li>
            @can('superadm')
                <li><a class="dropdown-item" href="{{ route('admin.company.list') }}"><i
                            class="ri-building-4-fill align-middle text-primary"></i>EMPRESAS</a></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="{{ route('config.main') }}"><i
                            class="ri-home-gear-fill align-middle text-dark"></i>CONFIGURAÇÕES</a></li>
            @endcan

        </ul>
    </li>
@endcan


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
            <li><a class="dropdown-item" href="{{ route('reports.advancedsearch') }}"><i
                        class="ri-search-eye-line align-middle text-primary"></i>BUSCAR AVANÇADA</a>
            </li>
            <li><a class="dropdown-item" href="{{ route('monitor.services') }}"><i
                        class="ri-computer-line align-middle text-dark"></i>MONITOR - ATIVIDADE</a></li>
            <li><a class="dropdown-item" href="{{ route('monitor.analises') }}"><i
                        class="ri-computer-line align-middle text-dark"></i>GRAFICO - ATIVIDADE</a></li>

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

@if (isset(Auth()->user()->Employee->Contract) &&
        Auth()->user()->Employee->Contract->service &&
        Auth()->user()->Employee->Contract->services->where('project', true)->count())
    <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle text-edp-verde nav-profile" href="#" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            PROJETO
        </a>
        <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2"
            style="background-color: #dbd8d8; width: 300px;">
            @can('operator')
                @if (isset(Auth()->user()->Employee->Contract->services) &&
                        Auth()->user()->Employee->Contract->service &&
                        Auth()->user()->Employee->Contract->services->where('project', true)->count())
                    <li style="background-color: #ffffff; color: white;">
                        <h6 class="dropdown-header">DESPACHOS</h6>
                    </li>
                    @foreach (Auth()->user()->Employee->Contract->services->where('project', true) as $service)
                        @if ($service->pivot->dispatch)
                            <li><a class="dropdown-item"
                                    href="{{ route('dispatch.main', ['service' => $service->uuid]) }}"><i
                                        class="{{ $service->icon }} align-middle text-danger"></i>{{ mb_strToUpper($service->service) }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endcan
            @can('user')
                <li>
                    <hr class="dropdown-divider">
                </li>
                @if (isset(Auth()->user()->Employee->Contract->services) &&
                        Auth()->user()->Employee->Contract->service &&
                        Auth()->user()->Employee->Contract->services->where('project', true)->count())
                    <li style="background-color: #ffffff; color: white;">
                        <h6 class="dropdown-header">SERVIÇOS</h6>
                    </li>
                    @foreach (Auth()->user()->Employee->Contract->services->where('project', true) as $service)
                        <li><a class="dropdown-item" href="{{ route('services.main', ['service' => $service->uuid]) }}">
                                <div class="d-flex align-items-center">
                                    <i class="{{ $service->icon }} text-primary"></i>
                                    <span>{{ mb_strtoupper($service->service) }}</span>
                                    @livewire('components.count.countnotes', ['service' => $service->uuid], key('menu' . $service->uuid))
                                </div>
                            </a>
                        </li>
                    @endforeach
                @endif
            @endcan

        </ul>
    </li>
@endif

@if (isset(Auth()->user()->Employee->Contract) &&
        Auth()->user()->Employee->Contract->construction &&
        Auth()->user()->Employee->Contract->services->where('construction', true)->count())
    <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle text-edp-verde nav-profile" href="#" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            CONSTRUCAO
        </a>
        <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2"
            style="background-color: #dbd8d8; width: 300px;">
            @can('operator')
                @if (isset(Auth()->user()->Employee->Contract->services) &&
                        Auth()->user()->Employee->Contract->construction &&
                        Auth()->user()->Employee->Contract->services->where('construction', true)->count())
                    <li style="background-color: #ffffff; color: white;">
                        <h6 class="dropdown-header">DESPACHOS</h6>
                    </li>
                    @foreach (Auth()->user()->Employee->Contract->services->where('construction', true) as $service)
                        @if ($service->pivot->dispatch)
                            <li><a class="dropdown-item"
                                    href="{{ route('dispatch.main', ['service' => $service->uuid]) }}"><i
                                        class="{{ $service->icon }} align-middle text-info"></i>{{ mb_strToUpper($service->service) }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endcan
            @can('user')
                <li>
                    <hr class="dropdown-divider">
                </li>
                @if (isset(Auth()->user()->Employee->Contract->services) &&
                        Auth()->user()->Employee->Contract->construction &&
                        Auth()->user()->Employee->Contract->services->where('construction', true)->count())
                    <li style="background-color: #ffffff; color: white;">
                        <h6 class="dropdown-header">SERVIÇOS</h6>
                    </li>
                    @foreach (Auth()->user()->Employee->Contract->services->where('construction', true) as $service)
                        <li><a class="dropdown-item" href="{{ route('services.main', ['service' => $service->uuid]) }}">
                                <div class="d-flex align-items-center">
                                    <i class="{{ $service->icon }} text-primary"></i>
                                    <span>{{ mb_strtoupper($service->service) }}</span>
                                    @livewire('components.count.countnotes', ['service' => $service->uuid], key('menu' . $service->uuid))
                                </div>
                            </a>
                        </li>
                    @endforeach
                @endif
            @endcan

        </ul>
    </li>
@endif



{{-- @can(['operator'])
    <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle text-edp-verde nav-profile" href="#" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            CONTROLE
        </a>
        @if (isset(Auth()->user()->Employee->Contract->services) && Auth()->user()->Employee->Contract->service && Auth()->user()->Employee->Contract->services->count())
            <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2" style="background-color: #dbd8d8">

                @foreach (Auth()->user()->Employee->Contract->services as $service)
                    @if ($service->pivot->dispatch)
                        <li><a class="dropdown-item" href="{{ route('dispatch.main', ['service' => $service->uuid]) }}"><i
                                    class="ri-remote-control-2-line align-middle text-primary"></i>{{ mb_strToUpper($service->service) }}</a>
                        </li>
                    @endif
                @endforeach


            </ul>
        @endif
    </li>
@endcan


@can('user')
    <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle text-edp-verde nav-profile" href="#" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            SERVIÇOS
        </a>

        @livewire('components.count.countnotes', ['geral' => true], key('menu' . $service->uuid))



        @if (isset(Auth()->user()->Employee->Contract->services) && Auth()->user()->Employee->Contract->service && Auth()->user()->Employee->Contract->services->count())
            <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2" style="background-color: #dbd8d8">

                @foreach (Auth()->user()->Employee->Contract->services as $service)
                    <li><a class="dropdown-item" href="{{ route('services.main', ['service' => $service->uuid]) }}"><i
                                class="ri-tools-line align-middle text-primary"></i>{{ mb_strToUpper($service->service) }}@livewire('components.count.countnotes', ['service' => $service->uuid], key('menu' . $service->uuid))</a>
                    </li>
                @endforeach


            </ul>
        @endif


    </li>
@endcan --}}


<li class="nav-item dropdown mx-2">
    <a class="nav-link dropdown-toggle text-edp-verde nav-profile" href="#" role="button"
        data-bs-toggle="dropdown" aria-expanded="false">
        BUSCAR
    </a>

    <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2" style="background-color: #dbd8d8">
        <li><a class="dropdown-item" href="{{ route('reports.search') }}"><i
                    class="ri-search-eye-line align-middle text-primary"></i>BUSCAR</a>
        </li>

    </ul>
</li>
