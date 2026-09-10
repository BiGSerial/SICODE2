<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#sistema-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>SISTEMA</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="sistema-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('config.system.status') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-circle"></i> <span>STATUS DO SERVIDOR</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('config.system.history') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-circle"></i> <span>HISTÓRICO DE EXECUÇÕES</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('config.system.jobs_view') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-circle"></i> <span>JOBS</span>
                        </a>
                    </li>
                    @can('superadm')
                        <li>
                            <a href="{{ route('config.system.schedule') }}" class="nav-item text-white fw-normal">
                                <i class="bi bi-circle"></i> <span>SCHEDULER</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('config.system.sqlsrv_health') }}" class="nav-item text-white fw-normal">
                                <i class="bi bi-circle"></i> <span>SAÚDE SQL SERVER</span>
                            </a>
                        </li>
                    @endcan
                </div>
            </ul>
        </li>
    </ul>

</aside>
