<div>
    <x-show-loading />
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
                                class="nav-item text-white fw-normal">
                                <i class="ri-money-dollar-box-line fs-5 align-middle edp-text-verde-dark fw-normal"></i>
                                <span>LISTA
                                    {{ mb_strToUpper($service->service) }}</span>
                                @livewire('components.count.countnotes', ['service' => $service->uuid], key($service->uuid))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('construction.accompany', ['service' => $service->uuid]) }}"
                                class="nav-item text-white fw-normal">
                                <i class="ri-timer-fill fs-5 align-middle edp-text-verde-dark fw-normal"></i> <span>À
                                    CONTRATAR</span>
                                @livewire('construction.hiring.counts.countmycontrol', key('count-control'))
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('construction.historic', ['service' => $service->uuid]) }}"
                                class="nav-item text-white fw-normal">
                                <i class="ri-money-dollar-box-fill fs-5 align-middle edp-text-verde-dark fw-normal"></i>
                                <span>OBRAS CONTRATADAS</span>
                                @livewire('construction.hiring.counts.check-hiring', key('count-hiring'))
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('construction.lookatnotes', ['service' => $service->uuid]) }}"
                                class="nav-item text-white fw-normal">
                                <i class="ri-search-eye-line fs-5 align-middle edp-text-verde-dark fw-normal"></i>
                                <span>CONSULTA NOTAS/OV</span>

                            </a>
                        </li>
                        {{-- <li>
                            <a href="{{ route('construction.returned', ['service' => $service->uuid]) }}"
                                class="nav-item text-white fw-normal">
                                <i class="ri-money-dollar-box-line fs-5 align-middle text-white fw-thin"></i> <span>RETORNO VIABILIDADE
                                    {{ mb_strToUpper($service->service) }}</span>
                                @livewire('construction.hiring.counts.returnviab', key('count-return'))
                            </a>
                        </li> --}}

                        <li>
                            <a href="{{ route('construction.waiting', ['service' => $service->uuid]) }}"
                                class="nav-item text-white fw-normal">
                                <i class="ri-timer-line fs-5 align-middle text-white fw-thin"></i>
                                <span>AGUARDANDO RETORNO INTERNO</span>
                                @livewire('construction.hiring.counts.count-return')
                            </a>
                        </li>



                    </div>
                </ul>
            </li>
        </ul>

    </aside>

</div>
