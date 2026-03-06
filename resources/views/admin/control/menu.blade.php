<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#controle-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>CONTROLE</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="controle-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('admin.control.d5') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-list-ul fs-5 text-edp-verde"></i> <span>CONTROLE D5</span>
                        </a>
                    </li>
                    <li class="mt-1">
                        <a href="{{ route('admin.control.viability') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-list-ul fs-5 text-edp-verde"></i> <span>CONTROLE VIABILIDADE</span>
                        </a>
                    </li>
                    <li class="mt-1">
                        <a href="{{ route('admin.control.notes') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-list-ul fs-5 text-edp-verde"></i> <span>CONTROLE NOTES</span>
                        </a>
                    </li>
                    <li class="mt-1">
                        <a href="{{ route('admin.control.workreports') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-list-ul fs-5 text-edp-verde"></i> <span>CONTROLE INFORME OBRA</span>
                        </a>
                    </li>
                    <li class="mt-1">
                        <a href="{{ route('admin.control.ads_requests') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-list-ul fs-5 text-edp-verde"></i> <span>GERENCIAMENTO ADS</span>
                        </a>
                    </li>
                </div>
            </ul>
        </li>
    </ul>

</aside>
