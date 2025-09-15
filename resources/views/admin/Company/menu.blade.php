<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#despachos-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>EMPRESAS</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="despachos-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('admin.company.list') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-circle"></i> <span>EMPRESAS</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.company.contracts_list') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-circle"></i> <span>CONTRATOS</span>
                        </a>
                    </li>
                    <li>
                        <a href="" class="nav-item text-white fw-normal">
                            <i class="bi bi-circle"></i> <span>DEPÓSITOS</span>
                        </a>
                    </li>

                </div>
            </ul>
        </li>
    </ul>

</aside>
