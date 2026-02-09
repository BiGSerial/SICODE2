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
                        <a href="{{ route('dispatch.main', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-list-ul fs-5 edp-text-verde-dark fw-normal"></i><span>LISTA PARA
                                {{ mb_strtoupper($service->service) }}</span>
                        </a>
                    </li>


                    <li>
                        <a href="{{ route('dispatch.stack', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-eye fs-5 edp-text-verde-dark fw-normal"></i><span>CONTROLE DE
                                {{ mb_strtoupper($service->service) }}</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('dispatch.transprod', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-arrow-left-right fs-5 edp-text-verde-dark fw-normal"></i><span>TRANSFERÊNCIA
                                DE
                                {{ mb_strtoupper($service->service) }}@livewire('components.count.counttransferdispatch', ['service' => $service->uuid], key('countTransfer' . $service->uuid))</span>
                        </a>
                    </li>

                    {{-- <li>
                        <a href="{{ route('dispatch.d5', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-arrow-return-left fs-5"></i><span>RETORNO INTERNO (RI) DE
                                {{ mb_strtoupper($service->service) }}</span> @livewire('components.count.count-return', ['service' => $service->uuid], key('count-return'))
                        </a>
                    </li> --}}

                    <li>
                        <a href="{{ route('dispatch.waitingFiveNote', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-hourglass-split fs-5 edp-text-verde-dark fw-normal"></i><span>AGUARDANDO
                                D5</span>
                        </a>
                    </li>
                </div>
            </ul>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#cancelamentos-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>CANCELAMENTO</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="cancelamentos-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('dispatch.cancellation.queue', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="ri-inbox-archive-line fs-5 edp-text-verde-dark fw-normal"></i><span>CONTROLE
                                CANCELAMENTO</span>
                            @livewire('components.count.cancellation-requests', ['mode' => 'unassigned'])
                            @livewire('components.count.cancellation-requests', ['mode' => 'in_progress'])
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('dispatch.cancellation.categories', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="ri-settings-3-line fs-5 edp-text-verde-dark fw-normal"></i><span>CONFIGURAÇÃO
                                DE CATEGORIAS</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('dispatch.cancellation.history', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="ri-history-line fs-5 edp-text-verde-dark fw-normal"></i><span>HISTÓRICO
                                DE CANCELAMENTO</span>
                        </a>
                    </li>
                </div>
            </ul>
        </li>
    </ul>

    <div class="col-12">
        @livewire('production.users.occupation', ['service_id' => $service->uuid], key('production-' . $service->uuid))
    </div>
</aside>
