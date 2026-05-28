<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#project-review-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>ANÁLISE PROJETOS</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="project-review-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('reports.project_review_dashboard') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-speedometer2 fs-5 edp-text-verde-dark fw-normal"></i><span>DASHBOARD</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.project_review_history') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-clock-history fs-5 edp-text-verde-dark fw-normal"></i><span>HISTÓRICO</span>
                        </a>
                    </li>
                </div>
            </ul>
        </li>
    </ul>
</aside>
