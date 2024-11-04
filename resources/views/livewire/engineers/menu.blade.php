<div>
    <aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

        <ul class="sidebar-nav" id="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#viabilidade-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-menu-button-wide"></i><span>VIABILIDADE</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="viabilidade-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('engineers.viability_waiting') }}" class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span> VIABILIDADE EM ESPERA</span>
                                @livewire('engineers.counts.viab-in-waiting-count', key('viab-in-waiting-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.viab_list') }}" class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span> EM VIABILIDADE</span>

                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.rejecte_viab') }}" class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span> EM TRATATIVA</span>
                                @livewire('engineers.counts.in-work-count', key('in-work-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.intern_return') }}" class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span> RETORNO INTERNO</span>

                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.justified_viab') }}" class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span> AVALIAÇÃO DE JUSTIFICATIVA</span>
                                {{-- @livewire('engineers.counts.viab-justify-count', key('viab-justify-count')) --}}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.viab_hist') }}" class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span> HISTÓRICO</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.viabilityreports') }}" class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span>RESUMO VIABILIDADE</span>
                                {{-- @livewire('construction.hiring.counts.count-return') --}}
                            </a>
                        </li>

                    </div>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#informes-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-menu-button-wide"></i><span>INFORMES</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="informes-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">


                        <li>
                            <a href="{{ route('engineers.inform_obra') }}" class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span> INFORME DE OBRA</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.inform_list') }}" class="nav-item edp-text-verde-dark">
                                <i class="bi bi-circle"></i> <span> HISTÓRICO INFORME</span>
                            </a>
                        </li>
                    </div>
                </ul>
            </li>
        </ul>



    </aside>
</div>
