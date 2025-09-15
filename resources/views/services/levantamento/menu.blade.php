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

                    <li>
                        <a href="{{ route('services.hiringsurvey', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="ri-eye-fill fw-light edp-text-verde-dark fs-5"></i> <span>EM VIABILIDADE</span>
                            {{-- @livewire('components.count.countnotes', ['service' => $service->uuid], key($service->uuid)) --}}
                        </a>
                    </li>

                    {{-- <li>
                        <a href="{{ route('services.waiting', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="ri-eye-fill fw-light text-white fs-5"></i></i> <span>LISTA DE ESPERA</span> @livewire('components.count.countwaiting', ['service' => $service->uuid], key('waiting' . $service->uuid))
                        </a>
                    </li> --}}
                    <li>
                        <a href="{{ route('services.historic', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="ri-history-line fw-light edp-text-verde-dark fs-5"></i> <span>MEU HISTÓRICO</span>
                        </a>
                    </li>

                </div>
            </ul>
        </li>
    </ul>

</aside>
