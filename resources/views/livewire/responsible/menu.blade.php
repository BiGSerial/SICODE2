<div>
    <aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

        <ul class="sidebar-nav" id="sidebar-nav">
            @if (!$onlySection || $onlySection === 'analises')
            <li class="nav-item">
                <a class="nav-link {{ $onlySection === 'analises' ? '' : 'collapsed' }}" data-bs-target="#analise-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-menu-button-wide"></i><span>VALIDAÇÃO DE PROJETOS</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="analise-nav" class="nav-content collapse {{ $onlySection === 'analises' ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('responsible.approve_list') }}" class="nav-item text-white fw-normal">
                                <i class="ri-list-unordered fw-light fs-5 edp-text-verde-dark"></i> <span> À
                                    VALIDAR</span>
                                @livewire('engineers.counts.analises.to-approval-count', key('responsible-to-approval-count'))
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('responsible.approve_control') }}" class="nav-item text-white fw-normal">
                                <i class="ri-list-unordered fw-light fs-5 edp-text-verde-dark"></i> <span> EM
                                    VALIDAÇÃO</span>
                                @livewire('engineers.counts.analises.in-approval-count', key('responsible-in-approval-count'))
                                @livewire('engineers.counts.analises.in-approval-return', key('responsible-in-approval-count'))
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('responsible.approve_hist') }}" class="nav-item text-white fw-normal">
                                <i class="ri-list-unordered fw-light fs-5 edp-text-verde-dark"></i> <span>
                                    VALIDADOS</span>
                            </a>
                        </li>

                </ul>
            </li>
            @endif
            @if (!$onlySection || $onlySection === 'viabilidade')
            <li class="nav-item">
                <a class="nav-link {{ $onlySection === 'viabilidade' ? '' : 'collapsed' }}" data-bs-target="#viabilidade-nav" data-bs-toggle="collapse"
                    href="#">
                    <i class="bi bi-menu-button-wide"></i><span>VIABILIDADE</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="viabilidade-nav" class="nav-content collapse {{ $onlySection === 'viabilidade' ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('responsible.viab_list') }}" class="nav-item text-white fw-normal">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> EM VIABILIDADE</span>
                                @livewire('responsible.counts.in-viability', key('in-viability'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('responsible.rejecte_viab') }}" class="nav-item text-white fw-normal">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> EM TRATATIVA</span>
                                @livewire('responsible.counts.in-work-count', key('in-work-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('responsible.intern_return') }}" class="nav-item text-white fw-normal">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> RETORNO INTERNO</span>
                                @livewire('responsible.counts.return-intern-count', key('return-intern-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('responsible.justified_viab') }}" class="nav-item text-white fw-normal">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> AVALIAÇÃO DE
                                    JUSTIFICATIVA</span>
                                @livewire('responsible.counts.viab-justify-count', key('viab-justify-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('responsible.viab_hist') }}" class="nav-item text-white fw-normal">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> HISTÓRICO</span>
                            </a>
                        </li>

                    </div>
                </ul>
            </li>
            @endif
            @if (!$onlySection || $onlySection === 'informes')
            <li class="nav-item">
                <a class="nav-link {{ $onlySection === 'informes' ? '' : 'collapsed' }}" data-bs-target="#informes-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-menu-button-wide"></i><span>INFORMES</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="informes-nav" class="nav-content collapse {{ $onlySection === 'informes' ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">


                        <li>
                            <a href="{{ route('responsible.inform_obra') }}" class="nav-item text-white fw-normal">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> INFORME DE OBRA</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('responsible.inform_list') }}" class="nav-item text-white fw-normal">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> HISTÓRICO INFORME</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('responsible.ads.requests') }}" class="nav-item text-white fw-normal">
                                <i class="ri-file-add-line fw-light fs-5"></i> <span>Solicitacoes ADS</span>
                            </a>
                        </li>                    </div>
                </ul>
            </li>
            @endif

            @if (!$onlySection || $onlySection === 'parciais')
            <li class="nav-item">
                <a class="nav-link {{ $onlySection === 'parciais' ? '' : 'collapsed' }}" data-bs-target="#informes_parcial-nav" data-bs-toggle="collapse"
                    href="#">
                    <i class="bi bi-menu-button-wide"></i><span>INFORMES PARCIAIS</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="informes_parcial-nav" class="nav-content collapse {{ $onlySection === 'parciais' ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">


                        {{-- <li>
                            <a href="{{ route('responsible.inform_obra') }}" class="nav-item text-white fw-normal">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> INFORME DE OBRA</span>
                            </a>
                        </li> --}}
                        <li>
                            <a href="{{ route('responsible.partial_hist') }}" class="nav-item text-white fw-normal">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span> HISTÓRICO INFORME</span>
                            </a>
                        </li>
                    </div>
                </ul>
            </li>
            @endif

            @if (!$onlySection || $onlySection === 'd5')
            <li class="nav-item">
                <a class="nav-link {{ $onlySection === 'd5' ? '' : 'collapsed' }}" data-bs-target="#dfive-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-menu-button-wide text-primary"></i><span>NOTAS D5</span>

                </a>
                <ul id="dfive-nav" class="nav-content collapse {{ $onlySection === 'd5' ? 'show' : '' }}" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">


                        <li>
                            <a href="{{ route('responsible.dfive.waiting') }}" class="nav-item text-white fw-normal">
                                <i class="ri-timer-flash-fill text-white fw-light fs-5"></i> <span> NOTAS D5 -
                                    AGUARDANDO RESOLUÇÃO </span> @livewire('engineers.count.waiting-five-notes-count', key('waiting-five-notes-count'))
                            </a>
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
            @endif
        </ul>
    </aside>
</div>
