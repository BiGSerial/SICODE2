@php
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />
    <div class="card">
        <div class="card-header edp-bg-seoweedgreen-100 text-white">
            <h4 class="my-1">DASHBOARD INFORMES DE CONCLUSÃO</h4>
        </div>
        <div class="card-body">
            <form class="form-inline">
                <div class="row">
                    <div class="col-md-4 col-xl-2 col-12 mb-2">
                        <label for="contractor" class="mr-2">Empreiteira</label>
                        <select id="contractor" class="form-select w-100" wire:model="company_id">
                            <option value="">Selecione uma empreiteira</option>
                            @if ($companies)
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-2 col-12 mb-2">
                        <label for="month" class="mr-2">Mês Referência</label>
                        <input type="month" id="month" class="form-control w-100" wire:model="month"
                            min="2023-01" max="{{ now()->format('Y-m') }}" value="{{ now()->format('Y-m') }}">
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
        <div class="col-md-6"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">INFORME DE CONCLUSÃO (Diário)</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="getDailyReceivedForms"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="getDailyReceivedForms" class="spinner-border spinner-border-sm"
                            role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>
                        Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                        <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                        @if ($company_id)
                            <br>Empreiteira: <strong>{{ $companies->find($company_id)->name }}</strong>
                        @endif
                    </em>
                </p>
                <div class="card-body">
                    <x-grafico.line-chart :chart-id="$dailyReceivedChartId" :labels="$dailyReceivedInform['labels']" :dataset="$dailyReceivedInform['data']" height="300px"
                        title="INFORME DE CONCLUSÃO" />
                </div>


            </div>
        </div>

        <div class="col-md-6"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
            <div class="card" wire:ignore.self>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">ENTREGA DE ADS FINAL (Diário)</h3>
                    <button class="btn btn-sm btn-secondary ml-auto" wire:click="getDailyADSForms"
                        wire:loading.attr="disabled">
                        <i class="ri-refresh-line" wire:loading.remove></i>
                        <span wire:loading wire:target="getDailyADSForms" class="spinner-border spinner-border-sm"
                            role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                    <em>
                        Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                        <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                        @if ($company_id)
                            <br>Empreiteira: <strong>{{ $companies->find($company_id)->name }}</strong>
                        @endif
                    </em>
                </p>
                <div class="card-body">
                    <x-grafico.line-chart :chart-id="$dailyADSChartId" :labels="$dailyADSInform['labels']" :dataset="$dailyADSInform['data']" height="300px"
                        title="INFORME DE CONCLUSÃO" />
                </div>
                <em class="ms-2">
                    <p class="text-start">
                        Para efeitos métricos, considera-se a data de entrega do informe a qual exista ADS
                    </p>
                </em>

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-8">
            <div class="row">
                <div class="col-md-6"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
                    <div class="card" wire:ignore.self>
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Motivos de Rejeição de Informes</h3>
                            <button class="btn btn-sm btn-secondary ml-auto" wire:click="getRejectionReason"
                                wire:loading.attr="disabled">
                                <i class="ri-refresh-line" wire:loading.remove></i>
                                <span wire:loading wire:target="getRejectionReason"
                                    class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                        <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                            <em>
                                Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                                <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                                @if ($company_id)
                                    <br>Empreiteira: <strong>{{ $companies->find($company_id)->name }}</strong>
                                @endif
                            </em>
                        </p>
                        <div class="card-body">
                            <x-grafico.pie-chart :chart-id="$returnInformChart1" :labels="$dataReturnInform['labels']" :dataset="$dataReturnInform['data']" height="300px" />
                        </div>

                        <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                            <em></em>
                        </p>
                    </div>
                </div>

                <div class="col-md-6"> <!-- Alterado para col-md-4 para ocupar 1/3 da largura em telas médias -->
                    <div class="card" wire:ignore.self>
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Proporção de Origem ADS</h3>
                            <button class="btn btn-sm btn-secondary ml-auto" wire:click="getTotalInformAdsOrigin"
                                wire:loading.attr="disabled">
                                <i class="ri-refresh-line" wire:loading.remove></i>
                                <span wire:loading wire:target="getTotalInformAdsOrigin"
                                    class="spinner-border spinner-border-sm" role="status"
                                    aria-hidden="true"></span>
                            </button>
                        </div>
                        <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                            <em>
                                Exibindo período: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até
                                <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
                                @if ($company_id)
                                    <br>Empreiteira: <strong>{{ $companies->find($company_id)->name }}</strong>
                                @endif
                            </em>
                        </p>
                        <div class="card-body">
                            <x-grafico.pie-chart :chart-id="$totalAdsOriginChartId" :labels="$totalAdsOriginData['labels']" :dataset="$totalAdsOriginData['data']"
                                height="300px" />
                        </div>

                        <p class="fs-6 my-0 py-2 fw-thin px-2" style="line-height: 1;">
                            <em></em>
                        </p>
                    </div>
                </div>
            </div>

        </div>
        <div class="col-4">
            <div class="card">


                @if ($workReportsVencidos->isNotEmpty())
                    <h5 class="card-header card-title">ADS ENTREGA VENCIDA</h5>
                    <table class="table table-sm table-condensed table-striped">
                        <thead>
                            <tr class="table-dark text-center">
                                <th>Obra</th>
                                <th>Empreiteira</th>
                                <th>Informe</th>
                                <th>Prazo</th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach ($workReportsVencidos as $ad)
                                @php

                                    $days = $ad->informed_at?->diffInDays(now(), true);

                                    if ($days > 6) {
                                        $color = 'text-bg-danger';
                                    } elseif ($days < 4) {
                                        $color = 'text-bg-success';
                                    } else {
                                        $color = 'text-bg-warning';
                                    }
                                @endphp

                                <tr class="text-center align-middle">
                                    <td>{{ $ad->note->note }}</td>
                                    <td>{{ $ad->company->name }}</td>
                                    <td>{{ $ad->informed_at?->format('d/m/Y H:i:s') }}</td>
                                    <td class="{{ $color }}">
                                        {{ $ad->informed_at?->diffInDays(now(), true) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="card-footer text-center">
                        {{ $workReportsVencidos->onEachSide(1)->links() }}
                    </div>
                @else
                    <h5 class="card-header card-title">ADS ENTREGA VENCIDA</h5>
                    <div class="card-body">
                        <h5 class="text-center fw-bold">SEM ADS VENCIDAS</h5>
                    </div>
                @endif
            </div>

            <div class="card">


                @if ($workReportsAdsVencidos->isNotEmpty())
                    <h5 class="card-header card-title">ADS ENTREGUE FORA DO PRAZO</h5>
                    <table class="table table-sm table-condensed table-striped">
                        <thead>
                            <tr class="table-dark text-center">
                                <th>Obra</th>
                                <th>Empreiteira</th>
                                <th>Informe</th>
                                <th>Prazo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($workReportsAdsVencidos as $ad)
                                @php
                                    $days;
                                    if ($ad->adsform) {
                                        $days = $ad->adsform->created_at->diffInDays($ad->informed_at, true);
                                    } elseif ($ad->note->oldAds->isNotEmpty()) {
                                        $days = $ad->note->oldAds?->last()?->date?->diffInDays($ad->informed_at, true);
                                    }

                                    if ($days > 6) {
                                        $color = 'text-bg-danger';
                                    } elseif ($days < 4) {
                                        $color = 'text-bg-success';
                                    } else {
                                        $color = 'text-bg-warning';
                                    }
                                @endphp

                                <tr class="text-center align-middle">
                                    <td>{{ $ad->note->note }}</td>
                                    <td>{{ $ad->company->name }}</td>
                                    <td>{{ $ad->informed_at?->format('d/m/Y H:i:s') }}</td>
                                    <td class="{{ $color }}">
                                        {{ $days }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="card-footer text-center">
                        {{ $workReportsAdsVencidos->onEachSide(1)->links() }}
                    </div>
                @else
                    <h5 class="card-header card-title">ADS ENTREGUE FORA DO PRAZO</h5>
                    <div class="card-body">
                        <h5 class="text-center fw-bold">SEM ADS ENTREGUE FORA DO PRAZO</h5>
                    </div>
                @endif
            </div>

            <div class="card">
                @if ($tacitOpenOverdue->isNotEmpty())
                    <h5 class="card-header card-title">ADS TÁCITA VENCIDA (SEM ENTREGA)</h5>
                    <table class="table table-sm table-condensed table-striped">
                        <thead>
                            <tr class="table-dark text-center">
                                <th>Obra</th>
                                <th>Empreiteira</th>
                                <th>Vencimento Tácito</th>
                                <th>Dias em atraso</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tacitOpenOverdue as $ad)
                                @php
                                    $dueAt = $ad->adsform?->tacit_due_at;
                                    $lateDays = $dueAt ? $dueAt->diffInDays(now()) : null;
                                @endphp
                                <tr class="text-center align-middle">
                                    <td>{{ $ad->note->note ?? '-' }}</td>
                                    <td>{{ $ad->company->name ?? '-' }}</td>
                                    <td>{{ $dueAt?->format('d/m/Y H:i:s') ?? '-' }}</td>
                                    <td class="text-bg-danger">{{ $lateDays ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="card-footer text-center">
                        {{ $tacitOpenOverdue->onEachSide(1)->links() }}
                    </div>
                @else
                    <h5 class="card-header card-title">ADS TÁCITA VENCIDA (SEM ENTREGA)</h5>
                    <div class="card-body">
                        <h5 class="text-center fw-bold">SEM ADS TÁCITA VENCIDA</h5>
                    </div>
                @endif
            </div>

            <div class="card">
                @if ($tacitDeliveredLate->isNotEmpty())
                    <h5 class="card-header card-title">ADS TÁCITA ENTREGUE EM ATRASO</h5>
                    <table class="table table-sm table-condensed table-striped">
                        <thead>
                            <tr class="table-dark text-center">
                                <th>Obra</th>
                                <th>Empreiteira</th>
                                <th>Entregue em</th>
                                <th>Dias após vencimento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tacitDeliveredLate as $ad)
                                @php
                                    $dueAt = $ad->adsform?->tacit_due_at;
                                    $deliveredAt = $ad->adsform?->tacit_delivered_at;
                                    $lateDays = $dueAt && $deliveredAt ? $dueAt->diffInDays($deliveredAt) : null;
                                @endphp
                                <tr class="text-center align-middle">
                                    <td>{{ $ad->note->note ?? '-' }}</td>
                                    <td>{{ $ad->company->name ?? '-' }}</td>
                                    <td>{{ $deliveredAt?->format('d/m/Y H:i:s') ?? '-' }}</td>
                                    <td class="text-bg-warning">{{ $lateDays ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="card-footer text-center">
                        {{ $tacitDeliveredLate->onEachSide(1)->links() }}
                    </div>
                @else
                    <h5 class="card-header card-title">ADS TÁCITA ENTREGUE EM ATRASO</h5>
                    <div class="card-body">
                        <h5 class="text-center fw-bold">SEM ADS TÁCITA ENTREGUE EM ATRASO</h5>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
