<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('config.wall.index') }}">
                <i class="bi bi-display"></i><span>WALL Produção</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('reports.wall.production_v2', ['wall' => 1]) }}" target="_blank">
                <i class="bi bi-arrows-fullscreen"></i><span>Abrir WALL (V2)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('config.main') }}">
                <i class="bi bi-gear"></i><span>Voltar Configurações</span>
            </a>
        </li>
    </ul>
</aside>
