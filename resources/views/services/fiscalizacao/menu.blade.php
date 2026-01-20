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
                        <a href="{{ route('services.main', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="ri-play-circle-line fw-light edp-text-verde-dark fs-5"></i> <span>ATIVIDADE
                                {{ mb_strToUpper($service->service) }}</span>
                            @livewire('components.count.countnotes', ['service' => $service->uuid], key($service->uuid))
                        </a>
                    </li>

                    {{-- <li>
                        <a href="{{ route('reports.workreport') }}" class="nav-item text-white fw-normal">
                            <i class="ri-history-line fw-light edp-text-verde-dark fs-5"></i> <span>LISTA DE INFORMES</span>
                        </a>
                    </li> --}}
                    <li>
                        <a href="{{ route('services.historic', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="ri-history-line fw-light edp-text-verde-dark fs-5"></i> <span>MEU HISTÓRICO</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('services.ads.requests', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="ri-file-add-line fw-light edp-text-verde-dark fs-5"></i> <span>Solicitacoes
                                ADS</span>
                        </a>
                    </li>

                </div>
            </ul>
        </li>
    </ul>

</aside>
