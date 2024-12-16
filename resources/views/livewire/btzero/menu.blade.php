<div>
    <aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

        <ul class="sidebar-nav" id="sidebar-nav">
            <li class="nav-item ">
                <a class="nav-link collapsed" data-bs-target="#viability-nav" data-bs-toggle="collapse" href="#">
                    <i class="ri-eye-line"></i><span>Bt Zero</span>
                    <i class="bi bi-chevron-down ms-auto"></i>
                </a>
                {{-- Para deixar o Dropdown Aberto, acrescemte 'show' na classe --}}
                <ul id="viability-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('btzero.main') }}" class="nav-item edp-text-verde-dark">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span>Principal</span>

                            </a>
                        </li>
                    </div>

                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('btzero.btzeroReport') }}" class="nav-item edp-text-verde-dark">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span>Informar Patrimônio SMC</span>

                            </a>
                        </li>
                    </div>

                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('btzero.histInform') }}" class="nav-item edp-text-verde-dark">
                                <i class="ri-play-circle-line fw-light text-white fs-5"></i> <span>Patrimonios SMC
                                    Informados
                                </span>

                            </a>
                        </li>
                    </div>


                </ul>

    </aside>
</div>
