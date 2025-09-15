<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#externals-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>RECLAMAÇÃO</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="externals-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('protests.services.main') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-file-earmark-text fs-5 edp-text-verde-dark"></i> <span>AGUARDANDO
                                RESOLUÇÃO</span>
                            @livewire('components.count.protest.count-protests', ['type' => 'U'], key('menu_protests_has_protests_user'))
                        </a>
                    </li>
                    {{-- <li>
                        <a href="{{ route('protests.services.accompany') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-file-earmark-text fs-5 text-warning"></i> <span>ACOMPANHAMENTO
                                RECLAMAÇÃO</span> @livewire('components.count.protest.count-protests', ['type' => 'M'], key('menu_protests_has_protests_monitoring'))
                        </a>
                    </li> --}}

                    <li>
                        <a href="{{ route('protests.services.history') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-clock-history fs-5 edp-text-verde-dark"></i> <span>MEU HISTÓRICO</span>
                        </a>
                    </li>

                </div>
            </ul>
        </li>

    </ul>



</aside>
