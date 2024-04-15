<div>
    <aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

        <ul class="sidebar-nav" id="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#despachos-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-menu-button-wide"></i><span>ACOMPANHAMENTO</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="despachos-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('construction.main', ['service' => $service->uuid]) }}"
                                class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span>LISTA
                                    {{ mb_strToUpper($service->service) }}</span>
                                @livewire('components.count.countnotes', ['service' => $service->uuid], key($service->uuid))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('construction.accompany', ['service' => $service->uuid]) }}"
                                class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span>MEU CONTROLE
                                    {{ mb_strToUpper($service->service) }}</span>
                                {{-- @livewire('components.count.countnotes', ['service' => $service->uuid], key($service->uuid)) --}}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('construction.returned', ['service' => $service->uuid]) }}"
                                class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span>RETORNO VIABILIDADE
                                    {{ mb_strToUpper($service->service) }}</span>
                                {{-- @livewire('components.count.countnotes', ['service' => $service->uuid], key($service->uuid)) --}}
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('construction.waiting', ['service' => $service->uuid]) }}"
                                class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span>EM ESPERA
                                    {{ mb_strToUpper($service->service) }}</span>
                                {{-- @livewire('components.count.countnotes', ['service' => $service->uuid], key($service->uuid)) --}}
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('construction.historic', ['service' => $service->uuid]) }}"
                                class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span>OBRAS CONTRATADAS</span>
                            </a>
                        </li>

                    </div>
                </ul>
            </li>
        </ul>

    </aside>

</div>
