@php
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />
    <div class="card mt-2">
        <div class="card-header edp-bg-seoweedgreen-100 text-white">
            <h4 class="my-1">DASHBOARD {{ mb_strtoupper($service->service) }}</h4>
        </div>
        <div class="card-body">
            <form class="form-inline">
                <div class="row">
                    {{-- <div class="col-md-4 col-xl-2 col-12 mb-2">
                        <label for="contractor" class="mr-2">Empreiteira</label>
                        <select id="contractor" class="form-select w-100" wire:model="company_id">
                            <option value="">Selecione uma empreiteira</option>
                            @if ($companies)
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div> --}}
                    <div class="col-md-4 col-xl-2 col-12 mb-2">
                        <label for="month" class="mr-2">Mês Referência</label>
                        <input type="month" id="month" class="form-control w-100" wire:model="month"
                            max="{{ now()->format('Y-m') }}" value="{{ now()->format('Y-m') }}">
                    </div>
                    <div class="col-md-4 col-xl-2 col-12 mb-2">
                        <label for="start_date" class="mr-2">Data de Início</label>
                        <input type="date" id="start_date" class="form-control w-100" wire:model="dt_ini">
                    </div>
                    <div class="col-md-4 col-xl-2 col-12 mb-2">
                        <label for="end_date" class="mr-2">Data de Fim</label>
                        <input type="date" id="end_date" class="form-control w-100" wire:model="dt_fim">
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">

        <div class="col-md-12">
            <!-- Alterado para col-md-4 col-xl-2 para ocupar 1/3 da largura em telas médias -->
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Tempo em pilha em {{ $service->service }}</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarStackOv"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarStackOv" class="spinner-border spinner-border-sm"
                            role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="card-body">
                    <x-grafico.stack-bar :chart-id="$chartId2" :labels="$dadosGrafico2['labels']" :dataset1-data="$dadosGrafico2['data1']"
                        dataset1-label="Atribuidos" :dataset2-data="$dadosGrafico2['data2']" dataset2-label="Sem Atribuição"
                        title="Dias em Pilha" y-axis-title="Qtd" />
                    <p class="fs-6 my-0 py-0 fw-thin" style="line-height: 1;"><em><strong> Observação:</strong> Os dias
                            em pilha englobam
                            tanto Notas quanto OVs, o que pode ocasionar variações inesperadas no quantitativo diário de
                            notas. Isso se deve à adoção de uma nova regra que atualizou toda a base de NOTAS em um
                            mesmo dia. Com o tempo, à medida que os status forem alterados naturalmente, essa
                            discrepância se ajustará. </em></p>
                </div>
            </div>
        </div>


        {{-- <div class="col-md-8"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Produção Diária Ativos x Notas</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarProducaoDiaria"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarProducaoDiaria"
                            class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>
                        Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                        <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                    </em>
                </p>
                <div class="card-body">
                    <x-grafico.mixed-chart chart-id="{{ $chartId1 }}" :labels="$mixedChartData['labels']" :data="$mixedChartData['data']"
                        title="{{ $mixedChartData['title'] }}" height="{{ $mixedChartData['height'] }}" />
                </div>
                @if (!array_sum($mixedChartData['data']))
                    <div class="card py-3">
                        <h5 class="text-center fw-bold">SEM DADOS PARA O PERÍODO</h5>
                    </div>
                @endif

            </div>
        </div> --}}

        <div class="col-md-6"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Produção Diária</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarProducaoDiaria"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarProducaoDiaria"
                            class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>
                        Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                        <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                    </em>
                </p>
                <div class="card-body">
                    <x-grafico.line-chart :chart-id="$chartId1" :labels="$dadosGrafico1['labels']" :dataset="$dadosGrafico1['data']" height="300px"
                        title="Produção Diária" />
                </div>
                @if (!array_sum($dadosGrafico1['data']))
                    <div class="card py-3">
                        <h5 class="text-center fw-bold">SEM DADOS PARA O PERÍODO</h5>
                    </div>
                @endif

            </div>
        </div>

        <div class="col-md-6"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Produção Ativos/Postes Diário</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarProducaoAtivosDiario"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarProducaoAtivosDiario"
                            class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>
                        Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                        <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                    </em>
                </p>
                <div class="card-body">
                    <x-grafico.line-chart :chart-id="$chartId6" :labels="$dadosGrafico6['labels']" :dataset="$dadosGrafico6['data']" height="300px"
                        title="Produção Diária" />
                </div>
                @if (!array_sum($dadosGrafico6['data']))
                    <div class="card py-3">
                        <h5 class="text-center fw-bold">SEM DADOS PARA O PERÍODO</h5>
                    </div>
                @endif

            </div>
        </div>

        <div class="col-md-4"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Relaçao Normal x RI</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarD5Proporcao"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarD5Proporcao" class="spinner-border spinner-border-sm"
                            role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>
                        Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                        <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                    </em>
                </p>
                <div class="card-body">
                    <x-grafico.pie-chart :chart-id="$chartId" :labels="$dadosGrafico['labels']" :dataset="$dadosGrafico['data']" height="300px" />
                </div>
                @if (!array_sum($dadosGrafico['data']))
                    <div class="card py-3">
                        <h5 class="text-center fw-bold">SEM DADOS PARA O PERÍODO</h5>
                    </div>
                @endif

            </div>
        </div>

        <div class="col-md-4"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Motivos Retorno Contratação</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarDados"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarDados" class="spinner-border spinner-border-sm"
                            role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>
                        Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                        <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                    </em>
                </p>
                <div class="card-body">
                    <x-grafico.pie-chart :chart-id="$chartId3" :labels="$dadosGrafico3['labels']" :dataset="$dadosGrafico3['data']" height="300px" />
                </div>
                @if (!array_sum($dadosGrafico3['data']))
                    <div class="card py-3">
                        <h5 class="text-center fw-bold">SEM DADOS PARA O PERÍODO</h5>
                    </div>
                @endif
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>Obs: É considerado os aprovados e os que ainda estão em tratamento pelo RI.</em>
                </p>
            </div>
        </div>

        <div class="col-md-4"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Motivos Retorno Analise</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarDados2"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarDados2" class="spinner-border spinner-border-sm"
                            role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>
                        Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                        <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                    </em>
                </p>
                <div class="card-body">
                    <x-grafico.pie-chart :chart-id="$chartId5" :labels="$dadosGrafico5['labels']" :dataset="$dadosGrafico5['data']" height="300px" />
                </div>
                @if (!array_sum($dadosGrafico5['data']))
                    <div class="card py-3">
                        <h5 class="text-center fw-bold">SEM DADOS PARA O PERÍODO</h5>
                    </div>
                @endif
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>Obs: É considerado os aprovados e os que ainda estão em tratamento pelo RI.</em>
                </p>
            </div>
        </div>

        <div class="col-md-4"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Motivos Retorno Viabilidade</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarDados1"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarDados1" class="spinner-border spinner-border-sm"
                            role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>
                        Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                        <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                    </em>
                </p>
                <div class="card-body">
                    <x-grafico.pie-chart :chart-id="$chartId4" :labels="$dadosGrafico4['labels']" :dataset="$dadosGrafico4['data']" height="300px" />
                </div>
                @if (!array_sum($dadosGrafico4['data']))
                    <div class="card py-3">
                        <h5 class="text-center fw-bold">SEM DADOS PARA O PERÍODO</h5>
                    </div>
                @endif
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>Obs: É considerado os aprovados e os que ainda estão em tratamento pelo RI.</em>
                </p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Ticket Médio Geral – {{ $service->service }}</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarTicketMedioServico"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarTicketMedioServico"
                            class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>
                        Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                        <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                    </em>
                </p>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <strong>Tempo de Reação:</strong>
                            {{ \Carbon\CarbonInterval::minutes($ticketGeral->avg_reaction_time)->cascade()->forHumans() }}
                        </li>
                        <li class="list-group-item">
                            <strong>Tempo de Execução:</strong>
                            {{ \Carbon\CarbonInterval::minutes($ticketGeral->avg_execution_time)->cascade()->forHumans() }}
                        </li>
                    </ul>
                    <p class="fs-6 my-0 py-2 fw-thin">
                        <em>Médias calculadas com base na diferença entre dispatch_at &rarr; att_at e att_at &rarr;
                            completed_at.</em>
                    </p>
                </div>
            </div>
        </div>


        <div class="col-md-6">
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Tempo Médio para Conclusão – Por Usuário</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarTicketMedioPorUsuario"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarTicketMedioPorUsuario"
                            class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </button>
                </div>
                {{-- @php
                    $productionStats = $this->getProductionStatsByUserProperty();
                @endphp --}}
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>
                        Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                        <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                    </em>
                </p>
                <div class="card-body">
                    @if ($productionStats->isNotEmpty())
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr class="table-dark">
                                    <th>Usuário</th>
                                    <th>Empresa</th>
                                    <th>Retorno Interno</th>
                                    <th>Normal</th>
                                    <th>Total de Obras</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($productionStats as $stat)
                                    <tr>
                                        <td>{{ $stat->name }}</td>
                                        <td>{{ $stat->User && $stat->User->Company ? explode(' ', $stat->User->Company->name)[0] : '---' }}
                                        </td>
                                        <td>
                                            @if ($stat->avg_resolution_d5)
                                                {{ \Carbon\CarbonInterval::minutes($stat->avg_resolution_d5)->cascade()->forHumans() }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if ($stat->avg_resolution_no_d5)
                                                {{ \Carbon\CarbonInterval::minutes($stat->avg_resolution_no_d5)->cascade()->forHumans() }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            {{ $stat->total }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="card py-3">
                            <h5 class="text-center fw-bold">SEM DADOS PARA O PERÍODO</h5>
                        </div>
                    @endif
                    <p class="fs-6 my-0 py-2 fw-thin">
                        <em>Tempo de resolução (att_at &rarr; completed_at) por usuário. É diferenciando por Retono
                            Interno e
                            Normal. <br>
                            <strong>OBS: A média de
                                tempo pode ser maior pela quantidade na pilha do usuário.</strong><br> </em>
                    </p>
                </div>
            </div>
        </div>


    </div>


</div>
