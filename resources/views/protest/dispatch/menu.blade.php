<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        {{-- <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#config_protest-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>CONFIGURAÇÕES</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="config_protest-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('protests.dispatch.config_users') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-file-earmark-text fs-5 edp-text-verde-dark"></i> <span>USER TRIGGERS</span>

                        </a>
                    </li>
                </div>
            </ul>
        </li> --}}
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#protests-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>RECLAMAÇÃO</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="protests-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('protests.dashboard') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-speedometer2 fs-5 edp-text-verde-dark"></i> <span>DASHBOARD</span>

                        </a>
                    </li>
                    <li>
                        <a href="{{ route('protests.dispatch.lists') }}"
                            class="nav-item text-white fw-normal d-flex align-items-center">
                            <i class="bi bi-inbox fs-5 edp-text-verde-dark me-1"></i> <span>EM ABERTO</span>
                            @livewire('protests.dispatch.menu-open-badge', ['type' => 'normal'], key('menu-open-normal'))
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('protests.dispatch.monitoring') }}"
                            class="nav-item text-white fw-normal d-flex align-items-center">
                            <i class="bi bi-arrow-repeat fs-5 edp-text-verde-dark me-1"></i> <span>EM ANDAMENTO</span>
                            @livewire('protests.dispatch.menu-monitoring-badge', ['type' => 'normal'], key('menu-monitoring-normal'))
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('protests.dispatch.closeds') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-check-circle fs-5 edp-text-verde-dark"></i> <span>FECHADOS</span>
                        </a>
                    </li>

                    {{-- <li>
                        <a href="{{ route('protests.dispatch.per_user') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-person fs-5 text-white"></i> <span>POR USUÁRIO</span>
                        </a>
                    </li> --}}

                    <li>
                        <a href="{{ route('protests.dispatch_btzero.lists') }}"
                            class="nav-item text-white fw-normal d-flex align-items-center">
                            <i class="bi bi-bullseye fs-5 edp-text-verde-dark me-1"></i> <span>BT ZERO ABERTO</span>
                            @livewire('protests.dispatch.menu-open-badge', ['type' => 'btzero'], key('menu-open-btzero'))
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('protests.dispatch_btzero.monitoring') }}"
                            class="nav-item text-white fw-normal d-flex align-items-center">
                            <i class="bi bi-bullseye fs-5 edp-text-verde-dark me-1"></i> <span>BT ZERO ANDAMENTO</span>
                            @livewire('protests.dispatch.menu-monitoring-badge', ['type' => 'btzero'], key('menu-monitoring-btzero'))
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('protests.dispatch_btzero.closeds') }}"
                            class="nav-item text-white fw-normal">
                            <i class="bi bi-bullseye fs-5 edp-text-verde-dark"></i> <span>BT ZERO FECHADOS</span>

                        </a>
                    </li>
                </div>
            </ul>
        </li>

    </ul>

    {{-- <div class="col-12">
        @livewire('production.users.occupation-protests', key('production-protests-' . $service->uuid))
    </div> --}}


</aside>
