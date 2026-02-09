@props([
    'menuProjeto' => 0,
    'menuConstrucao' => 0,
])

@php
    $toServices = Auth()->user()->ToServices ?? collect();

    $projectDispatchItems = auth()->user()->can('operator')
        ? $toServices->filter(fn($service) => $service->dispatch && $service->Service?->project)
        : collect();

    $projectServiceItems = auth()->user()->can('user')
        ? $toServices->filter(fn($service) => $service->service && $service->Service?->project)
        : collect();

    $constructionDispatchItems = auth()->user()->can('operator')
        ? $toServices->filter(fn($service) => $service->dispatch && $service->Service?->construction)
        : collect();

    $constructionServiceItems = auth()->user()->can('user')
        ? $toServices->filter(fn($service) => $service->service && $service->Service?->construction)
        : collect();

    $showProjeto = $projectDispatchItems->isNotEmpty() || $projectServiceItems->isNotEmpty();
    $showConstrucao = $constructionDispatchItems->isNotEmpty() || $constructionServiceItems->isNotEmpty();
@endphp

@if ($showProjeto || $showConstrucao)
    <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle text-white nav-profile" href="#" role="button" data-bs-toggle="dropdown"
            data-bs-auto-close="outside" aria-expanded="false">
            ATIVIDADES
        </a>
        <ul class="dropdown-menu dropdown-menu-arrow dropdown-menu-end mt-2 dropdown-menu-custom services-dropdown services-dropdown-menu"
            style="background-color: #dbd8d8; width: 340px;">
            @include('components.menu.partials.services-dropdown-style')

        <li class="dropdown-header" style="background-color: #ffffff;">ATIVIDADES</li>

        @if ($showProjeto)
            <li class="menu-item js-menu-toggle" data-target="#panel-projeto">
                PROJETO <i class="ri-arrow-right-s-line"></i>
            </li>
            <div id="panel-projeto" class="menu-panel">
                @if ($projectDispatchItems->isNotEmpty())
                    <div class="submenu mb-2">
                        <button class="submenu-toggle js-submenu-toggle" data-target="#submenu-projeto-despacho"
                            type="button">
                            DESPACHO <i class="ri-arrow-right-s-line"></i>
                        </button>
                        <div id="submenu-projeto-despacho" class="submenu-panel">
                            @foreach ($projectDispatchItems as $service)
                                <a class="dropdown-item" href="{{ route('dispatch.main', ['service' => $service->service_id]) }}"><i
                                        class="{{ $service->Service->icon }} align-middle text-danger"></i>{{ mb_strToUpper($service->Service->service) }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($projectServiceItems->isNotEmpty())
                    <div class="submenu">
                        <button class="submenu-toggle js-submenu-toggle" data-target="#submenu-projeto-servico"
                            type="button">
                            SERVIÇO <i class="ri-arrow-right-s-line"></i>
                        </button>
                        <div id="submenu-projeto-servico" class="submenu-panel">
                            @foreach ($projectServiceItems as $service)
                                <a class="dropdown-item" href="{{ route('services.main', ['service' => $service->service_id]) }}">
                                    <div class="d-flex align-items-center">
                                        <i class="{{ $service->Service->icon }} text-primary"></i>
                                        <span>{{ mb_strtoupper($service->Service->service) }}</span>
                                        @livewire('components.count.countnotes', ['service' => $service->service_id], key('menu' . $service->service_id))
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if ($showConstrucao)
            <li class="menu-item js-menu-toggle" data-target="#panel-construcao">
                CONSTRUÇÃO <i class="ri-arrow-right-s-line"></i>
            </li>
            <div id="panel-construcao" class="menu-panel">
                @if ($constructionDispatchItems->isNotEmpty())
                    <div class="submenu mb-2">
                        <button class="submenu-toggle js-submenu-toggle" data-target="#submenu-construcao-despacho"
                            type="button">
                            DESPACHO <i class="ri-arrow-right-s-line"></i>
                        </button>
                        <div id="submenu-construcao-despacho" class="submenu-panel">
                            @foreach ($constructionDispatchItems as $service)
                                <a class="dropdown-item" href="{{ route('dispatch.main', ['service' => $service->service_id]) }}"><i
                                        class="{{ $service->Service->icon }} align-middle text-danger"></i>{{ mb_strToUpper($service->Service->service) }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($constructionServiceItems->isNotEmpty())
                    <div class="submenu">
                        <button class="submenu-toggle js-submenu-toggle" data-target="#submenu-construcao-servico"
                            type="button">
                            SERVIÇO <i class="ri-arrow-right-s-line"></i>
                        </button>
                        <div id="submenu-construcao-servico" class="submenu-panel">
                            @foreach ($constructionServiceItems as $service)
                                <a class="dropdown-item" href="{{ route('services.main', ['service' => $service->service_id]) }}">
                                    <div class="d-flex align-items-center">
                                        <i class="{{ $service->Service->icon }} text-primary"></i>
                                        <span>{{ mb_strtoupper($service->Service->service) }}</span>
                                        @livewire('components.count.countnotes', ['service' => $service->service_id], key('menu' . $service->service_id))
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

            @include('components.menu.partials.services-dropdown-script')
        </ul>
    </li>
@endif
