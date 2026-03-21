<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('project_review.categories') ? '' : 'collapsed' }}"
               href="{{ route('project_review.categories') }}">
                <i class="ri-price-tag-3-line"></i><span>CATEGORIAS</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('project_review.list') ? '' : 'collapsed' }}"
               href="{{ route('project_review.list') }}">
                <i class="ri-task-line"></i><span>LISTA PARA ANALISAR</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('project_review.dashboard') ? '' : 'collapsed' }}"
               href="{{ route('project_review.dashboard') }}">
                <i class="ri-dashboard-line"></i><span>DASHBOARD GOVERNANÇA</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('project_review.history') ? '' : 'collapsed' }}"
               href="{{ route('project_review.history') }}">
                <i class="ri-history-line"></i><span>HISTÓRICO DAS ANÁLISES</span>
            </a>
        </li>
    </ul>
</aside>
