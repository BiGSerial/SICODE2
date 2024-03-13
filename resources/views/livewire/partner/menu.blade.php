<div>
    <aside id="sidebar" class="sidebar edp-bg-cobaltblue-100">

        <ul class="sidebar-nav" id="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#viability-nav" data-bs-toggle="collapse" href="#">
                    <i class="ri-eye-line"></i><span>Viabilidade</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                {{-- Para deixar o Dropdown Aberto, acrescemte 'show' na classe --}}
                <ul id="viability-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('partner.todo.viability') }}" class="nav-item text-white">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span>A Fazer</span>
                            </a>
                        </li>
                    </div>

                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="" class="nav-item text-white">
                                <i class="ri-history-line fw-light fs-5"></i> <span>Concluídos</span>
                            </a>
                        </li>
                    </div>
                </ul>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#contracts-nav" data-bs-toggle="collapse" href="#">
                    <i class="ri-award-fill"></i><span>Contratação</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                {{-- Para deixar o Dropdown Aberto, acrescemte 'show' na classe --}}
                <ul id="contracts-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="" class="nav-item text-white">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span>Obras Conntratadas</span>
                            </a>
                        </li>
                    </div>


                </ul>
            </li>
        </ul>

    </aside>
</div>
