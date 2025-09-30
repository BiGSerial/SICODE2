<div>
    <aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

        <ul class="sidebar-nav" id="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#analises_nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-menu-button-wide"></i><span>VALIDAÇÂO DE PROJETOS</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="analises_nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('engineers.analises.dashboard') }}" class="nav-item text-white fw-normal">
                                <i class="ri-dashboard-line text-white fw-light fs-5"></i> <span> DASHBOARD</span>

                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.analises.toAnalise') }}" class="nav-item text-white fw-normal">
                                <i class="ri-eye-line text-white fw-light fs-5"></i> <span> PILHA À VALIDAR</span>
                                @livewire('engineers.counts.analises.to-approval-count', key('to-approval-count'))
                            </a>

                        </li>
                        <li>
                            <a href="{{ route('engineers.analises.inAnalise') }}" class="nav-item text-white fw-normal">
                                <i class="ri-run-fill text-white fw-light fs-5"></i> <span> EM
                                    VALIDAÇÃO</span>@livewire('engineers.counts.analises.in-approval-count', ['engineer' => true], key('in-approval-count'))
                                @livewire('engineers.counts.analises.in-approval-return', ['engineer' => true], key('in-approval-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.analises.analised') }}" class="nav-item text-white fw-normal">
                                <i class="ri-information-fill text-white fw-light fs-5"></i> <span> VALIDADOS</span>

                            </a>
                        </li>


                    </div>
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
                            <a href="{{ route('engineers.main') }}" class="nav-item text-white fw-normal">
                                <i class="ri-dashboard-line text-white fw-light fs-5"></i> <span> DASHBOARD</span>

                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.viability_waiting') }}" class="nav-item text-white fw-normal">
                                <i class="ri-eye-line text-white fw-light fs-5"></i> <span> VIABILIDADE EM ESPERA</span>
                                @livewire('engineers.counts.viab-in-waiting-count', key('viab-in-waiting-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.viab_list') }}" class="nav-item text-white fw-normal">
                                <i class="ri-run-fill text-white fw-light fs-5"></i> <span> EM VIABILIDADE</span>
                                @livewire('engineers.counts.in-viability', key('in-viability'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.rejecte_viab') }}" class="nav-item text-white fw-normal">
                                <i class="ri-information-fill text-white fw-light fs-5"></i> <span> EM TRATATIVA</span>
                                @livewire('engineers.counts.in-work-count', key('in-work-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.intern_return') }}" class="nav-item text-white fw-normal">
                                <i class="ri-arrow-go-back-line text-white fw-light fs-5"></i> <span> RETORNO
                                    INTERNO</span>
                                @livewire('engineers.counts.return-intern-count', key('return-intern-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.justified_viab') }}" class="nav-item text-white fw-normal">
                                <i class="ri-contacts-line text-white fw-light fs-5"></i> <span> AVALIAÇÃO DE
                                    JUSTIFICATIVA</span>
                                @livewire('engineers.counts.viab-justify-count', key('viab-justify-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.viab_hist') }}" class="nav-item text-white fw-normal">
                                <i class="ri-history-line text-white fw-light fs-5"></i> <span> HISTÓRICO</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.viabilityreports') }}" class="nav-item text-white fw-normal">
                                <i class="ri-funds-line text-white fw-light fs-5"></i> <span>RESUMO VIABILIDADE</span>
                                {{-- @livewire('construction.hiring.counts.count-return') --}}
                            </a>
                        </li>

                    </div>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#informes-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-menu-button-wide text-danger"></i><span>INFORMES CONCLUSÃO</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="informes-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('engineers.dashboard.conclusion_inform') }}"
                                class="nav-item text-white fw-normal">
                                <i class="ri-dashboard-fill text-white fw-light fs-5"></i> <span> DASHBOARD</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('engineers.inform_obra') }}" class="nav-item text-white fw-normal">
                                <i class="ri-user-voice-line text-white fw-light fs-5"></i> <span> INFORME DE
                                    OBRA</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.inform_list') }}" class="nav-item text-white fw-normal">
                                <i class="ri-history-line text-white fw-light fs-5"></i> <span> HISTÓRICO INFORME</span>
                            </a>
                        </li>
                    </div>
                </ul>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#informes-parc-nav" data-bs-toggle="collapse"
                    href="#">
                    <i class="bi bi-menu-button-wide text-primary"></i><span>INFORMES PARCIAIS</span>
                    @livewire('engineers.counts.count-parcial', ['menu' => true], key('count-parcial'))<i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="informes-parc-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">


                        <li>
                            <a href="{{ route('engineers.info.parcial') }}" class="nav-item text-white fw-normal">
                                <i class="ri-timer-flash-fill text-white fw-light fs-5"></i> <span> AGUARDANDO
                                    APROVAÇÂO</span> @livewire('engineers.counts.count-parcial', key('count-parcial'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engineers.hist.parcial') }}" class="nav-item text-white fw-normal">
                                <i class="ri-history-line text-white fw-light fs-5"></i> <span> HISTÓRICO INFORME
                                    PARCIAL</span>
                            </a>
                        </li>
                    </div>
                </ul>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#dfive-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-menu-button-wide text-primary"></i><span>NOTAS D5</span>
                    @livewire('engineers.counts.count-parcial', ['menu' => true], key('count-parcial'))<i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="dfive-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('engineers.dfive.waiting') }}" class="nav-item text-white fw-normal">
                                <i class="ri-timer-flash-fill text-white fw-light fs-5"></i> <span> NOTAS D5 -
                                    AGUARDANDO RESOLUÇÃO </span> @livewire('engineers.count.waiting-five-notes-count', key('waiting-five-notes-count'))</a>
                        </li>
                        {{-- <li>
                            <a href="{{ route('engineers.hist.parcial') }}" class="nav-item text-white fw-normal">
                                <i class="ri-history-line text-white fw-light fs-5"></i> <span> HISTÓRICO INFORME
                                    PARCIAL</span>
                            </a>
                        </li> --}}
                    </div>
                </ul>
            </li>
        </ul>



    </aside>
</div>
