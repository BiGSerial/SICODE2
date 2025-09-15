<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#despachos-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>CONFIGURAÇÕES</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="despachos-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('config.services') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-circle"></i> <span>SERVIÇOS</span>
                        </a>
                    </li>


                </div>
            </ul>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#jobs-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>JOBS</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="jobs-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('config.system.jobs_view') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-circle"></i> <span>JOBS VIEW</span>
                        </a>
                    </li>


                </div>
            </ul>
        </li>
    </ul>

</aside>
