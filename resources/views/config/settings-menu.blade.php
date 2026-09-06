<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#config-settings-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>CONFIGURAÇÕES</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="config-settings-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('config.services') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-circle"></i> <span>CONFIGURAR SERVIÇOS</span>
                        </a>
                    </li>
                    @can('superadm')
                        <li>
                            <a href="{{ route('config.acceptance_terms') }}" class="nav-item text-white fw-normal">
                                <i class="bi bi-circle"></i> <span>TERMO DE ACEITE - INFORME</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('config.partner_dashboard_legal_notes') }}" class="nav-item text-white fw-normal">
                                <i class="bi bi-circle"></i> <span>AVISOS LEGAIS - PAINEL PARCEIRO</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('config.analysis_closure_rules') }}" class="nav-item text-white fw-normal">
                                <i class="bi bi-circle"></i> <span>ENCERRAMENTO DE ANÁLISE</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('config.wall.index') }}" class="nav-item text-white fw-normal">
                                <i class="bi bi-circle"></i> <span>WALL DE PRODUÇÃO</span>
                            </a>
                        </li>
                    @endcan
                </div>
            </ul>
        </li>
    </ul>

</aside>
