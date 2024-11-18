<div>
    <x-show-loading />
    <div class="card">
        <div class="card-header edp-bg-seoweedgreen-100 text-white">
            <h4 class="my-1">RESUMO VIABILIDADE</h4>
        </div>
        <div class="card-body">
            <form class="form-inline">
                <div class="row">
                    <div class="col-md-4 col-12 mb-2">
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
                    <div class="col-md-4 col-12 mb-2">
                        <label for="start_date" class="mr-2">Data de Início</label>
                        <input type="date" id="start_date" class="form-control w-100" wire:model="dt_in">
                    </div>
                    <div class="col-md-4 col-12 mb-2">
                        <label for="end_date" class="mr-2">Data de Fim</label>
                        <input type="date" id="end_date" class="form-control w-100" wire:model="dt_out">
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-6 col-lg-4">@livewire('engineers.dashboard.viability-pizza', key('viability-pizza'))</div>
        <div class="col-12 col-md-6 col-lg-4">@livewire('engineers.dashboard.rejected-pizza', key('rejected-pizza'))</div>

    </div>

    <div class="row">
        <div class="col-xxl-4 col-md-4">
            <div class="card info-card revenue-card">
                <div class="card-header py-0 text-bg-success">
                    <h5 class="my-1">REALIZADO <span>| {{ $dt_in ? date('d M', strtotime($dt_in)) : '' }} -
                            {{ $dt_out ? date('d M', strtotime($dt_out)) : '' }}</span></h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <h3><i class="ri-checkbox-circle-line"></i></h3>
                        </div>
                        <div class="ps-3">
                            <h3>&asymp;R$ {{ number_format($resume['realized'], 2, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-md-4">
            <div class="card info-card revenue-card">
                <div class="card-header py-0 text-bg-warning">
                    <h5 class="my-1">NÃO REALIZADO <span>| {{ $dt_in ? date('d M', strtotime($dt_in)) : '' }} -
                            {{ $dt_out ? date('d M', strtotime($dt_out)) : '' }}</span></h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <h3><i class="ri-checkbox-circle-line"></i></h3>
                        </div>
                        <div class="ps-3">
                            <h3>&asymp;R$ {{ number_format($resume['notRealized'], 2, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-md-4">
            <div class="card info-card revenue-card">
                <div class="card-header py-0 text-bg-danger">
                    <h5 class="my-1">PENALIDADE PREVISTA <span>| {{ $dt_in ? date('d M', strtotime($dt_in)) : '' }} -
                            {{ $dt_out ? date('d M', strtotime($dt_out)) : '' }}</span></h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <h3><i class="ri-checkbox-circle-line"></i></h3>
                        </div>
                        <div class="ps-3">
                            <h3>&asymp;R$ {{ number_format($resume['penalty'], 2, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-6">
            <div class="card">
                <div class="card-header py-0 text-bg-success">
                    <h5 class="my-1">Realizado <span>| {{ $dt_in ? date('d M', strtotime($dt_in)) : '' }} -
                            {{ $dt_out ? date('d M', strtotime($dt_out)) : '' }}</span></h5>
                </div>
                <div class="card-body">
                    @if ($realizeds->isNotEmpty())
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Nota</th>
                                    <th>Empreiteira</th>
                                    <th>Valor MMO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($realizeds as $realized)
                                    <tr>
                                        <td>{{ $realized->Note->note }}</td>
                                        <td>{{ $realized->Company->name }}</td>
                                        <td>R$ {{ number_format($realized->value, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="alert alert-warning">Nenhum registro encontrado para o período</div>
                    @endif
                </div>
            </div>
            {{ $realizeds->links() }}
        </div>

        <div class="col-6">
            <div class="card">
                <div class="card-header py-0 text-bg-warning d-flex justify-content-between align-items-center">
                    <h5 class="my-1">Não Realizado <span>| {{ $dt_in ? date('d M', strtotime($dt_in)) : '' }} -
                            {{ $dt_out ? date('d M', strtotime($dt_out)) : '' }}</span></h5>
                    <button wire:click="exportExcel" class="btn btn-primary btn-sm">
                        <i class="ri-file-excel-2-line"></i>
                    </button>
                </div>
                <div class="card-body">
                    @if ($notRealizeds->isNotEmpty())
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Nota</th>
                                    <th>Empreiteira</th>
                                    <th>Valor MMO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($notRealizeds as $item)
                                    <tr>
                                        <td>{{ $item->Note->note }}</td>
                                        <td>{{ $item->Company->name }}</td>
                                        <td>R$ {{ number_format($item->value, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="alert alert-warning">Nenhum registro encontrado para o período</div>
                    @endif
                </div>
            </div>
            {{ $notRealizeds->links() }}
        </div>
    </div>
</div>
