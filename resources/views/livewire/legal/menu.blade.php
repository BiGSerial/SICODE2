<div>
    <aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

        <ul class="sidebar-nav" id="sidebar-nav">

            {{-- ===== CONTROLADOR ===== --}}
            @can('legal.demands.triage')
                <li class="nav-item">
                    <a class="nav-link {{ $activeSection === 'controlador' ? '' : 'collapsed' }}"
                       data-bs-target="#legal-controlador-nav"
                       data-bs-toggle="collapse" href="#">
                        <i class="bi bi-inbox-fill"></i>
                        <span>CONTROLADOR</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <ul id="legal-controlador-nav"
                        class="nav-content collapse {{ $activeSection === 'controlador' ? 'show' : '' }}"
                        data-bs-parent="#sidebar-nav">
                        <div class="border-start border-3 mb-1 py-0">
                            <li>
                                <a href="{{ route('legal.queue') }}"
                                   class="nav-item fw-normal {{ request()->routeIs('legal.queue') ? 'text-warning' : 'text-white' }}">
                                    <i class="bi bi-list-task fw-light fs-5"></i>
                                    <span> FILA DE DEMANDAS</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('legal.triage') }}"
                                   class="nav-item fw-normal {{ request()->routeIs('legal.triage') ? 'text-warning' : 'text-white' }}">
                                    <i class="bi bi-funnel fw-light fs-5"></i>
                                    <span> INBOX DE TRIAGEM</span>
                                </a>
                            </li>
                        </div>
                    </ul>
                </li>
            @endcan

            {{-- ===== EXECUTANTE (CAMPO) ===== --}}
            @can('legal.demands.answer')
                <li class="nav-item">
                    <a class="nav-link {{ $activeSection === 'campo' ? '' : 'collapsed' }}"
                       data-bs-target="#legal-campo-nav"
                       data-bs-toggle="collapse" href="#">
                        <i class="bi bi-person-check-fill"></i>
                        <span>EXECUTANTE</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <ul id="legal-campo-nav"
                        class="nav-content collapse {{ $activeSection === 'campo' ? 'show' : '' }}"
                        data-bs-parent="#sidebar-nav">
                        <div class="border-start border-3 mb-1 py-0">
                            <li>
                                <a href="{{ route('legal.field.queue') }}"
                                   class="nav-item fw-normal {{ request()->routeIs('legal.field.*') ? 'text-warning' : 'text-white' }}">
                                    <i class="bi bi-list-check fw-light fs-5"></i>
                                    <span> MINHAS ATRIBUIÇÕES</span>
                                </a>
                            </li>
                        </div>
                    </ul>
                </li>
            @endcan

            {{-- ===== GESTÃO ===== --}}
            @can('legal.manager')
                <li class="nav-item">
                    <a class="nav-link {{ $activeSection === 'gestao' ? '' : 'collapsed' }}"
                       data-bs-target="#legal-gestao-nav"
                       data-bs-toggle="collapse" href="#">
                        <i class="bi bi-bar-chart-fill"></i>
                        <span>GESTÃO</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <ul id="legal-gestao-nav"
                        class="nav-content collapse {{ $activeSection === 'gestao' ? 'show' : '' }}"
                        data-bs-parent="#sidebar-nav">
                        <div class="border-start border-3 mb-1 py-0">
                            <li>
                                <a href="{{ route('legal.dashboard') }}"
                                   class="nav-item fw-normal {{ request()->routeIs('legal.dashboard') ? 'text-warning' : 'text-white' }}">
                                    <i class="bi bi-speedometer2 fw-light fs-5"></i>
                                    <span> DASHBOARD</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('legal.cases') }}"
                                   class="nav-item fw-normal {{ request()->routeIs('legal.cases') ? 'text-warning' : 'text-white' }}">
                                    <i class="bi bi-search fw-light fs-5"></i>
                                    <span> BUSCA DE CASOS</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('legal.reports') }}"
                                   class="nav-item fw-normal {{ request()->routeIs('legal.reports') ? 'text-warning' : 'text-white' }}">
                                    <i class="bi bi-file-earmark-bar-graph fw-light fs-5"></i>
                                    <span> RELATÓRIOS</span>
                                </a>
                            </li>
                        </div>
                    </ul>
                </li>
            @endcan

        </ul>

    </aside>
</div>
