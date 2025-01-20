<div>
    <aside id="sidebar" class="sidebar edp-bg-cobaltblue-100">

        <ul class="sidebar-nav" id="sidebar-nav">
            <li class="nav-item ">
                <a class="nav-link collapsed" data-bs-target="#viability-nav" data-bs-toggle="collapse" href="#">
                    <i class="ri-eye-line"></i><span>Viabilidade</span>
                    <i class="bi bi-chevron-down ms-auto"></i>
                </a>
                {{-- Para deixar o Dropdown Aberto, acrescemte 'show' na classe --}}
                <ul id="viability-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('partner.todo.viability') }}" class="nav-item text-white">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span>À Viabilizar </span>
                                @livewire('partner.count.todoviabilitycount', key('count-viab'))
                            </a>
                        </li>
                    </div>

                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('partner.rejected.viability') }}" class="nav-item text-white">
                                <i class="ri-tools-fill fw-light fs-5"></i> <span>Em Tratativa </span>
                                @livewire('partner.count.rejectedanswercount', key('answer-count-viab'))
                            </a>
                        </li>
                    </div>

                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('partner.tacit.viability') }}" class="nav-item text-white">
                                <i class="ri-timer-flash-fill fw-light fs-5"></i> <span>Tácitas a Justificar </span>
                                @livewire('partner.count.tacitcount', key('answer-count-tacit'))
                            </a>
                        </li>
                    </div>

                    {{-- <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('partner.hired.viability') }}" class="nav-item text-white">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span>Contratadas à Viabilizar</span>
                                @livewire('partner.count.hiredviability', key('count-hired-viab'))
                            </a>
                        </li>
                    </div> --}}

                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('partner.hist.viability') }}" class="nav-item text-white">
                                <i class="ri-history-line fw-light fs-5"></i> <span>Histórico</span>
                            </a>
                        </li>
                    </div>
                </ul>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#conclusion-nav" data-bs-toggle="collapse" href="#">
                    <i class="ri-information-fill text-success"></i><span>Informes Conclusão</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>

                <ul id="conclusion-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('partner.report.workreport') }}" class="nav-item text-white">
                                <i class="ri-user-voice-line fw-light fs-5"></i> <span>Informar Conclusão de
                                    Obras</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('partner.report.rejectedWorked') }}" class="nav-item text-white">
                                <i class="ri-user-voice-fill fw-light fs-5"></i> <span>Informe Conclusão
                                    Rejeitados</span>@livewire('partner.count.returnworkforms', key('returnWorkForm-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('partner.report.workedlist') }}" class="nav-item text-white">
                                <i class="ri-user-star-line fw-light fs-5"></i> <span>Obras Concluídas Informadas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('partner.declared.equipment') }}" class="nav-item text-white">
                                <i class="ri-device-line fw-light fs-5"></i> <span>Equipamentos Obras Concluídas
                                    Declarados</span>
                            </a>
                        </li>
                    </div>


                </ul>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#partial-nav" data-bs-toggle="collapse" href="#">
                    <i class="ri-information-line text-danger"></i><span>Informes Parcial</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>

                <ul id="partial-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('partner.report.partial') }}" class="nav-item text-white">
                                <i class="ri-user-voice-line fw-light fs-5 text-warning"></i> <span>Informar
                                    Parcialmente
                                    Obras</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('partner.report.partiallist') }}" class="nav-item text-white">
                                <i class="ri-user-star-line fw-light fs-5"></i> <span>Obras Parciais Informadas</span>
                            </a>
                        </li>

                    </div>
                </ul>
            </li>
        </ul>

    </aside>
</div>
