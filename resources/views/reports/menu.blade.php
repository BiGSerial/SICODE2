<aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#informes-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>INFORMES</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="informes-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('reports.workreport') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-list-ul fs-5 edp-text-verde-dark fw-normal"></i><span>OBRAS INFORMADAS</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.informe_ads_tacita') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-link-45deg fs-5 edp-text-verde-dark fw-normal"></i><span>INFORME X ADS TÁCITA</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.five_notes') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-card-list fs-5 edp-text-verde-dark fw-normal"></i><span>RELATÓRIO NOTAS D5</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.complaints_mede') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-file-earmark-excel fs-5 edp-text-verde-dark fw-normal"></i><span>RELATÓRIO DE RECLAMAÇÃO</span>
                        </a>
                    </li>
                    <li>
                </div>
            </ul>
        </li>

        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#despachos-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-menu-button-wide"></i><span>INFORMES REJEITADOS</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="despachos-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                <div class="border-start border-3 mb-1 py-0">
                    <li>
                        <a href="{{ route('reports.rejecetedWorkreport') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-x-circle fs-5 edp-text-verde-dark fw-normal"></i><span>INFORMES
                                REJEITADOS</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('reports.historicRejectReports') }}" class="nav-item text-white fw-normal">
                            <i class="bi bi-clock-history fs-5 edp-text-Verde-dark fw-normal"></i><span>HISTÓRICO
                                INFORMES
                                REJEITADOS</span>
                        </a>
                    </li>
                </div>
            </ul>
        </li>

    </ul>


</aside>
