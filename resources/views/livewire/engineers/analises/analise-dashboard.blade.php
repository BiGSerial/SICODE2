@php
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />
    <div class="card">
        <div class="card-header edp-bg-seoweedgreen-100 text-white">
            <h4 class="my-1">DASHBOARD VALIDAÇÃO DE PROJETOS</h4">
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
                    <h3 class="mb-0">Tempo em Pilha para Validar</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarDays"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarDays" class="spinner-border spinner-border-sm"
                            role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="card-body">
                    <x-grafico.stack-bar :chart-id="$chartId2" :labels="$dadosGrafico1['labels']" :dataset1-data="$dadosGrafico1['data1']"
                        dataset1-label="Atribuidos" :dataset2-data="$dadosGrafico1['data2']" dataset2-label="Sem Atribuição"
                        title="Dias em Pilha" y-axis-title="Qtd" />
                    <p class="fs-6 my-0 py-0 fw-thin" style="line-height: 1;"><em>Obs: Para efeitos de Estatística, os
                            dados representam as obras em condições de contratação, aguardando a validação de
                            projetos. Os aprovados e enviados para resolução são removidos dos dados, restando apenas os
                            AINDA sem atribuição, e os ATRIBUÍDOS sem solução ou destinação.</em></p>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <!-- Alterado para col-md-4 col-xl-2 para ocupar 1/3 da largura em telas médias -->
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Tempo em Pilha do Retorno Interno</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarDaysReclaimType"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarDaysReclaimType"
                            class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="card-body">
                    <x-grafico.multistackbar :chart-id="$chartId6" :labels="$multStackData['labels']" :datasets="$multStackData['datasets']"
                        title="Dias em Resolução" y-axis-title="Qtd" />
                    <p class="fs-6 my-0 py-0 fw-thin" style="line-height: 1;"><em>Obs: Para efeito visuala dos dados,
                            são considerados apenas o montante na pilha da resolução interna que ainda sem encontram em
                            resolução. Os Finalizados são removidos dos dados.</em></p>
                </div>
            </div>
        </div>


        <div class="col-md-4"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
            <div class="card" wire:ignore.self wire:key="{{ $chartId3 }}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Liberados para Contratação</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarAprovedCategory"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarAprovedCategory"
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
                    <x-grafico.pie-chart :chart-id="$chartId3" :labels="$dadosGrafico2['labels']" :dataset="$dadosGrafico2['data']" height="300px" />
                </div>

                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>Obs: É considerado apenas os aprovados.</em>
                </p>
            </div>
        </div>

        <div class="col-md-4"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
            <div class="card" wire:ignore.self wire:key="{{ $chartId1 }}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Categoria Retorno (Geral)</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="getReclaimsProperty"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="getReclaimsProperty" class="spinner-border spinner-border-sm"
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
                    <x-grafico.pie-chart :chart-id="$chartId1" :labels="$dadosGrafico['labels']" :dataset="$dadosGrafico['data']" height="300px" />
                </div>

                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>Obs: É considerado os aprovados e os que ainda estão em tratamento pelo RI.</em>
                </p>
            </div>
        </div>

        <div class="col-md-4" wire:ignore.self wire:key="{{ $chartId7 }}">
            <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Categoria Retorno (Em Aberto)</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarDados2"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarDados2" class="spinner-border spinner-border-sm"
                            role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    {{-- <em>
                        Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                        <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                    </em> --}}
                </p>
                <div class="card-body">
                    <x-grafico.pie-chart :chart-id="$chartId7" :labels="$dadosGrafico4['labels']" :dataset="$dadosGrafico4['data']" height="300px" />
                </div>

                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>Obs: É considerado apenas ainda em aberto em Retorno Interno.</em>
                </p>
            </div>
        </div>


        <div class="col-md-4"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
            <div class="card" wire:ignore.self wire:key="{{ $chartId5 }}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Retornos por Origem</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click.prevent="atualizarReclaimType"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarReclaimType" class="spinner-border spinner-border-sm"
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
                    <x-grafico.pie-chart :chart-id="$chartId5" :labels="$pizzaReturnInternData['labels']" :dataset="$pizzaReturnInternData['data']" height="300px" />
                </div>

                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>Obs: É considerado os aprovados e os que ainda estão em tratamento pelo RI.</em>
                </p>
            </div>
        </div>



        {{-- Tempo Médio de Analise por usuário --}}
        <div class="col-md-4">
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Tempo Médio por Validador</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarTicketMedio"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarTicketMedio" class="spinner-border spinner-border-sm"
                            role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>
                        Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                        <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                    </em>
                </p>
                @if ($usuariosStats->isNotEmpty())
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr class='table-dark '>
                                <th>Usuário</th>
                                <th>Tempo de Reação</th>
                                <th>Tempo de Execução</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($usuariosStats as $usuario)
                                <tr>
                                    <td>{{ $usuario->name }}</td>
                                    <td>{{ \Carbon\CarbonInterval::minutes($usuario->avg_reaction_time)->cascade()->forHumans() }}
                                    </td>
                                    <td>{{ \Carbon\CarbonInterval::minutes($usuario->avg_execution_time)->cascade()->forHumans() }}
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
                {{-- <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>Obs: Considerado apenas tempo de OV. Notas não possuem data no status.</em>
                </p> --}}

            </div>
        </div>

        {{-- Ticket Médio em Resolução Interna --}}
        <div class="col-md-4">
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Tempo Médio – Resoluçao Interna</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarTicketMedioReclaim"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarTicketMedioReclaim"
                            class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>
                        Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                        <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                    </em>
                </p>
                @if ($reclaimsGeral->avg_resolution + $reclaimsGeral->avg_reaction + $reclaimsGeral->avg_execution > 0)
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <strong>Tempo de Resolução:</strong>
                                {{ \Carbon\CarbonInterval::minutes($reclaimsGeral->avg_resolution)->cascade()->forHumans() }}
                            </li>
                            <li class="list-group-item">
                                <strong>Tempo de Reação:</strong>
                                {{ \Carbon\CarbonInterval::minutes($reclaimsGeral->avg_reaction)->cascade()->forHumans() }}
                            </li>
                            <li class="list-group-item">
                                <strong>Tempo de Execução:</strong>
                                {{ \Carbon\CarbonInterval::minutes($reclaimsGeral->avg_execution)->cascade()->forHumans() }}
                            </li>
                        </ul>
                        <p class="fs-6 my-0 py-2 fw-thin" style="line-height: 1;">
                            <em>Métricas gerais de Resolução Interna (apenas finalizados).</em>
                        </p>
                    </div>
                @else
                    <div class="card py-3">
                        <h5 class="text-center fw-bold">SEM DADOS PARA O PERÍODO</h5>
                    </div>
                @endif

            </div>
        </div>

        {{-- Tempo Médio de Resolução por Usuário --}}

        <div class="col-md-4">
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Tempo Médio de Resolução – Por Usuario</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarTicketMedioResolution"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="atualizarTicketMedioResolution"
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
                    @if ($productionsStats->isNotEmpty())
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr class="table-dark">
                                    <th>Usuário</th>
                                    <th>Tempo de Resolução (min)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($productionsStats as $userStat)
                                    <tr>
                                        <td>{{ $userStat->name }}</td>
                                        <td>{{ \Carbon\CarbonInterval::minutes($userStat->avg_resolution_production)->cascade()->forHumans() }}
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
                    <p class="fs-6 my-0 py-0 fw-thin" style="line-height: 1;">
                        <em>Tempo de resolução interna, considerado apenas os finalizados.</em>
                    </p>
                </div>
            </div>
        </div>


    </div>
</div>
</div>
