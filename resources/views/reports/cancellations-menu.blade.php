<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#cancelamentos-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>CANCELAMENTOS</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="cancelamentos-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('reports.cancellations_dashboard') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-graph-up-arrow fs-5 edp-text-verde-dark fw-normal"></i><span>DASHBOARD
                                GERENCIAL</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.cancellations_list') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-table fs-5 edp-text-verde-dark fw-normal"></i><span>LISTA DE
                                CANCELAMENTOS</span>
                        </a>
                    </li>
                </div>
            </ul>
        </li>
    </ul>
</aside>
