<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#despachos-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>ENTIDADE EXTERNA</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="despachos-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('services.main', ['service' => $service->uuid]) }}"
                            class="nav-item edp-text-verde-dark">
                            <i class="bi bi-file-earmark-text fs-4 text-white"></i> <span>Á PROTOCOLAR</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('services.accompany', ['service' => $service->uuid]) }}"
                            class="nav-item edp-text-verde-dark">
                            <i class="bi bi-file-earmark-text fs-4 text-warning"></i> <span>ACOMPANHAMENTO
                                PROTOCOLADOS</span> @livewire('components.count.countnotes', ['service' => $service->uuid], key($service->uuid))
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('services.waiting_return', ['service' => $service->uuid]) }}"
                            class="nav-item edp-text-verde-dark">
                            <i class="bi bi-hourglass-split fs-4 text-white"></i> <span>AGUARDANDO RETORNO
                                INTERNO</span> @livewire('components.count.oexterno.count-return', key('return-'))
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('services.historic', ['service' => $service->uuid]) }}"
                            class="nav-item edp-text-verde-dark">
                            <i class="bi bi-clock-history fs-4 text-white"></i> <span>MEU HISTÓRICO</span>
                        </a>
                    </li>

                </div>
            </ul>
        </li>
    </ul>



</aside>
