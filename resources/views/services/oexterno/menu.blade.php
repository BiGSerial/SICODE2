<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#externals-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>ENTIDADE EXTERNA</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="externals-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('services.oexterno.dashboard', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-speedometer2 edp-text-verde-dark fs-5"></i> <span>DASHBOARD</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('services.main', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-file-earmark-text edp-text-verde-dark fs-5"></i> <span>À PROTOCOLAR</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('services.oexterno.undefined', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-question-circle fs-5 text-warning"></i> <span>STATUS INDEFINIDOS</span>
                            @php
                                $value = ['INDEFINIDO'];
                            @endphp
                            @livewire('services.oexterno.counts.count-status', ['values' => $value, 'null' => true], key('count-status-undefined'))
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('services.oexterno.waiting_payment', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-credit-card fs-5 text-warning"></i> <span>AGUARDANDO PAGAMENTO</span>
                            @php
                                $value = ['AGUARDANDO_PAGAMENTO'];
                            @endphp
                            @livewire('services.oexterno.counts.count-status', ['values' => $value, 'null' => false], key('count-status-aguardando-pagamento'))
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('services.oexterno.waiting_taxa', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-receipt fs-5 text-warning"></i> <span>AGUARDANDO TAXA</span>
                            @php
                                $value = ['AGUARDANDO_TAXA'];
                            @endphp
                            @livewire('services.oexterno.counts.count-status', ['values' => $value, 'null' => false], key('count-status-aguardando-pagamento'))
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('services.oexterno.waiting_orgao', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-building fs-5 text-warning"></i> <span>AGUARDANDO ÓRGÃO
                                EXTERNO</span>
                            @php
                                $value = ['AGUARDANDO_ORGAO'];
                            @endphp
                            @livewire('services.oexterno.counts.count-status', ['values' => $value, 'null' => false], key('count-status-aguardando-pagamento'))
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('services.waiting_return', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-hourglass-split edp-text-verde-dark fs-5"></i> <span>AGUARDANDO RETORNO
                                INTERNO</span> @livewire('components.count.oexterno.count-return', key('return-'))
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('services.historic', ['service' => $service->uuid]) }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-clock-history edp-text-verde-dark fs-5"></i> <span>MEU HISTÓRICO</span>
                        </a>
                    </li>

                </div>
            </ul>
        </li>

    </ul>



</aside>
