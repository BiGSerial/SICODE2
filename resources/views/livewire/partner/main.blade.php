@php
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />

    {{-- @dump($dueSoon) --}}

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header edp-bg-seoweedgreen-100 text-white">
                    <h4 class="my-1">DASHBOARD {{ mb_strToUpper(auth()->user()->company->name) }}</h4">
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
                            <div class="col-md-4 col-xl-4 col-12 mb-2">
                                <label for="month" class="mr-2">Mês Referência</label>
                                <input type="month" id="month" class="form-control w-100" wire:model="month"
                                    max="{{ now()->format('Y-m') }}" value="{{ now()->format('Y-m') }}">
                            </div>
                            <div class="col-md-4 col-xl-4 col-12 mb-2">
                                <label for="start_date" class="mr-2">Data de Início</label>
                                <input type="date" id="start_date" class="form-control w-100" wire:model="dt_ini">
                            </div>
                            <div class="col-md-4 col-xl-4 col-12 mb-2">
                                <label for="end_date" class="mr-2">Data de Fim</label>
                                <input type="date" id="end_date" class="form-control w-100" wire:model="dt_fim">
                            </div>
                        </div>
                    </form>
                </div>
            </div>


            <div class="row">
                <div class="col-md-6"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
                    <div class="card" wire:ignore.self>
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">VIABILIDADE</h3>
                            <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizarViabilityCounts"
                                wire:loading.attr="disabled">
                                <i class="ri-refresh-line" wire:loading.remove></i>
                                <span wire:loading wire:target="atualizarViabilityCounts"
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
                            <x-grafico.pie-chart :chart-id="$pizza1" :labels="$dadospizza1['labels']" :dataset="$dadospizza1['data']" height="300px" />
                        </div>
                        @if (!array_sum($dadospizza1['data']))
                            <div class="card py-3">
                                <h5 class="text-center fw-bold">SEM DADOS PARA O PERÍODO</h5>
                            </div>
                        @endif
                        <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                            <em></em>
                        </p>
                    </div>
                </div>


                <div class="col-md-6"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
                    <div class="card" wire:ignore.self>
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">MOTIVOS REJEIÇÃO INFORMES</h3>
                            <button class="btn btn-sm btn-secondary ml-auto" wire:click="atualizaReturnWorkReports"
                                wire:loading.attr="disabled">
                                <i class="ri-refresh-line" wire:loading.remove></i>
                                <span wire:loading wire:target="atualizaReturnWorkReports"
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
                            <x-grafico.pie-chart :chart-id="$pizza2" :labels="$dadospizza2['labels']" :dataset="$dadospizza2['data']" height="300px" />
                        </div>
                        @if (!array_sum($dadospizza2['data']))
                            <div class="card py-3">
                                <h5 class="text-center fw-bold">SEM DADOS PARA O PERÍODO</h5>
                            </div>
                        @endif
                        <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                            <em></em>
                        </p>
                    </div>
                </div>

            </div>
        </div>



        <div class="col-md-4">
            @if ($dueSoon)
                <div class="card">
                    <div class="card-header edp-bg-seoweedgreen-100 text-white">
                        <h4 class="my-1">VENCENDO EM BREVE (Viabilidade)</h4">
                    </div>
                    <table class="table-sm table-condensed table-striped-columns">
                        <thead>
                            <tr>
                                @can('engineer')
                                    <th class='text-center'>Empreiteira</th>
                                @endcan
                                <th class='text-center'>Nota</th>
                                <th class='text-center'>Recebido em</th>
                                <th class='text-center'>Viabilizar até</th>
                                <th class='text-center'>Dias Restantes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dueSoon as $item)
                                <tr>
                                    @can('engineer')
                                        <td class="text-center align-middle fw-bold">
                                            {{ $item->company->name }}</td>
                                    @endcan
                                    <td class="text-center align-middle fw-bold">{{ $item->note->note }}</td>
                                    <td class="text-center align-middle text-primary">
                                        {{ $item->sended_at->format('d/m/Y') }}</td>
                                    <td class="text-center align-middle text-danger">
                                        {{ $item->sended_at->addDays(7 + $item->getDays())->format('d/m/Y') }}</td>
                                    <td class="text-center align-middle fw-bold">
                                        {{ now()->startOfDay()->diffInDays($item->sended_at->copy()->addDays(7 + $item->getDays())->startOfDay()) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
