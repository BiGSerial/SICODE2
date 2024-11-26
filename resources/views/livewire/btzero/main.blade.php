<div>
    <x-show-loading />

    <section class="section dashboard">
        <div class="row">
            <div class="col-lg-8">
                <div class="clear-fix">
                    <div class="row">
                        <div class="py-1 mb-3 edp-bg-seoweedgreen-100 text-white">
                            <h4 class="my-0">{{ Auth()->User()->Employee->Contract->Company->name }}</h4>
                        </div>
                        <div class="col-xxl-4 col-md-6">
                            <div class="card info-card revenue-card">
                                <div class="card-body">
                                    <h5 class="card-title">Viabilidades Concluidas <span>| {{ date('M') }}</span>
                                    </h5>

                                    <div class="d-flex align-items-center">
                                        <div
                                            class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ri-checkbox-circle-line"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6>{{ $completedNow }}</h6>

                                            @if ($completedBefore == 0)
                                                <span class="text-primary small pt-1 fw-bold"><i
                                                        class="bx bx-square fs-4 align-middle"></i>{{ $completedBefore }}%</span>
                                                <span class="text-muted small pt-2 ps-1">--</span>
                                            @elseif ($completedBefore > 0)
                                                <span class="text-success small pt-1 fw-bold"><i
                                                        class="bx bxs-up-arrow-circle fs-4 align-middle"></i>
                                                    {{ $completedBefore }}%</span>
                                                <span class="text-muted small pt-2 ps-1">Maior ao mês anterior</span>
                                            @else
                                                <span class="text-danger small pt-1 fw-bold"><i
                                                        class="bx bxs-down-arrow-circle fs-4 align-middle"></i>
                                                    {{ $completedBefore * -1 }}%</span>
                                                <span class="text-muted small pt-2 ps-1">Menor ao mês anterior</span>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xxl-4 col-md-6">
                            <div class="card info-card sales-card">
                                <div class="card-body">
                                    <h5 class="card-title">Viabilidades em Aberto <span>| {{ date('M') }}</span>
                                    </h5>

                                    <div class="d-flex align-items-center">
                                        <div
                                            class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bx bxs-car-mechanic"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6>{{ $vaibilityOpen }}</h6>



                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col-xxl-4 col-md-6">
                            <div class="card info-card customers-card">
                                <div class="card-body">
                                    <h5 class="card-title">Aprovadas Tácitamente <span>| {{ date('M') }}</span></h5>

                                    <div class="d-flex align-items-center">
                                        <div
                                            class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ri-calendar-check-fill"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6>{{ $tacitNow }}</h6>

                                            @if ($tacitBefore == 0)
                                                <span class="text-primary small pt-1 fw-bold"><i
                                                        class="bx bx-square fs-4 align-middle"></i>{{ $tacitBefore }}%</span>
                                                <span class="text-muted small pt-2 ps-1">--</span>
                                            @elseif ($tacitBefore > 0)
                                                <span class="text-success small pt-1 fw-bold"><i
                                                        class="bx bxs-up-arrow-circle fs-4 align-middle"></i>
                                                    {{ $tacitBefore }}%</span>
                                                <span class="text-muted small pt-2 ps-1">Maior ao mês anterior</span>
                                            @else
                                                <span class="text-danger small pt-1 fw-bold"><i
                                                        class="bx bxs-down-arrow-circle fs-4 align-middle"></i>
                                                    {{ $tacitBefore * -1 }}%</span>
                                                <span class="text-muted small pt-2 ps-1">Menor ao mês anterior</span>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col-xxl-4 col-md-6">
                            <div class="card info-card customers-card">
                                <div class="card-body">
                                    <h5 class="card-title">Aguardando Contratante <span>| {{ date('M') }}</span>
                                    </h5>

                                    <div class="d-flex align-items-center">
                                        <div
                                            class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ri-timer-line"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6>{{ $responsers }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

    </section>

</div>
