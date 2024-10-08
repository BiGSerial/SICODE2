<div>
    <aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

        <ul class="sidebar-nav" id="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#despachos-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-menu-button-wide"></i><span>VIABILIDADE</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="despachos-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('responsible.viability_waiting') }}" class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span> VIABILIDADE EM ESPERA</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('responsible.viab_list') }}" class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span> EM VIABILIDADE</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('responsible.rejecte_viab') }}" class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span> EM TRATATIVA</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('responsible.justified_viab') }}" class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span> AVALIAÇÃO DE JUSTIFICATIVA</span>
                            </a>
                        </li>
                        <li>
                            <a href="" class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span> HISTÓRICO</span>
                            </a>
                        </li>
                    </div>
                </ul>
            </li>
        </ul>



    </aside>
</div>
