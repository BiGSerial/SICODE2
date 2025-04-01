<div>
    <aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

        <ul class="sidebar-nav" id="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#analise-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-menu-button-wide"></i><span>VALIDAÇÃO DE PROJETOS</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="analise-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('responsible.approve_list') }}" class="nav-item edp-text-verde-dark">
                                <i class="ri-list-unordered fw-light fs-5 text-white"></i> <span> À VALIDAR</span>
                                @livewire('engineers.counts.analises.to-approval-count', key('responsible-to-approval-count'))
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('responsible.approve_control') }}" class="nav-item edp-text-verde-dark">
                                <i class="ri-list-unordered fw-light fs-5 text-white"></i> <span> EM VALIDAÇÃO</span>
                                @livewire('engineers.counts.analises.in-approval-count', key('responsible-in-approval-count'))
                                @livewire('engineers.counts.analises.in-approval-return', key('responsible-in-approval-count'))
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('responsible.approve_hist') }}" class="nav-item edp-text-verde-dark">
                                <i class="ri-list-unordered fw-light fs-5 text-white"></i> <span> VALIDADOS</span>
                            </a>
                        </li>

                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#viabilidade-nav" data-bs-toggle="collapse"
                    href="#">
                    <i class="bi bi-menu-button-wide"></i><span>VIABILIDADE</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="viabilidade-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('responsible.viability_waiting') }}" class="nav-item edp-text-verde-dark">
                                <i class="ri view-list fw-light fs-5"></i> <span> VIABILIDADE EM ESPERA</span>
                                @livewire('responsible.counts.viab-in-waiting-count', key('viab-in-waiting-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('responsible.viab_list') }}" class="nav-item edp-text-verde-dark">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> EM VIABILIDADE</span>
                                @livewire('responsible.counts.in-viability', key('in-viability'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('responsible.rejecte_viab') }}" class="nav-item edp-text-verde-dark">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> EM TRATATIVA</span>
                                @livewire('responsible.counts.in-work-count', key('in-work-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('responsible.intern_return') }}" class="nav-item edp-text-verde-dark">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> RETORNO INTERNO</span>
                                @livewire('responsible.counts.return-intern-count', key('return-intern-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('responsible.justified_viab') }}" class="nav-item edp-text-verde-dark">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> AVALIAÇÃO DE
                                    JUSTIFICATIVA</span>
                                @livewire('responsible.counts.viab-justify-count', key('viab-justify-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('responsible.viab_hist') }}" class="nav-item edp-text-verde-dark">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> HISTÓRICO</span>
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
                <ul id="informes-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">


                        <li>
                            <a href="{{ route('responsible.inform_obra') }}" class="nav-item edp-text-verde-dark">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> INFORME DE OBRA</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('responsible.inform_list') }}" class="nav-item edp-text-verde-dark">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> HISTÓRICO INFORME</span>
                            </a>
                        </li>
                    </div>
                </ul>
            </li>
        </ul>
    </aside>
</div>
