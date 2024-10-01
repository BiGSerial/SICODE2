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
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span>Em Tratativa </span>
                                @livewire('partner.count.rejectedanswercount', key('answer-count-viab'))
                            </a>
                        </li>
                    </div>

                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('partner.tacit.viability') }}" class="nav-item text-white">
                                <i class="ri-play-circle-line fw-light fs-5"></i> <span>Tácitas a Justificar </span>
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
                <a class="nav-link collapsed" data-bs-target="#contracts-nav" data-bs-toggle="collapse" href="#">
                    <i class="ri-award-fill"></i><span>Informes</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>

                <ul id="contracts-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
                    <div class="border-start border-3 mb-1 py-0">
                        <li>
                            <a href="{{ route('partner.report.workreport') }}" class="nav-item text-white">
                                <i class="ri-user-voice-line fw-light fs-5"></i> <span>Informar Obras</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('partner.report.rejectedWorked') }}" class="nav-item text-white">
                                <i class="ri-user-voice-line fw-light fs-5"></i> <span>Informe
                                    Rejeitados</span>@livewire('partner.count.returnworkforms', key('returnWorkForm-count'))
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('partner.report.workedlist') }}" class="nav-item text-white">
                                <i class="ri-user-star-line fw-light fs-5"></i> <span>Obras Informadas</span>
                            </a>
                        </li>
                    </div>


                </ul>
            </li>
        </ul>

    </aside>
</div>
