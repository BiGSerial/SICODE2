<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#despachos-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>USUARIO</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="despachos-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('admin.user.list') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-people fs-5 text-edp-verde"></i> <span>USUÁRIOS</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('admin.user.hierarchy') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-diagram-3 fs-5 text-edp-verde"></i> <span>HIERARQUIA DE USUÁRIOS</span>
                        </a>
                    </li>

                </div>
            </ul>
        </li>
    </ul>

</aside>
