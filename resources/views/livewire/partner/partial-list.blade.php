@php
    use Carbon\Carbon;
@endphp
<div>

    <x-show-loading />
    <div class="card">
        <div class="card-header">
            Pesquisa
        </div>
        <div class="card-body">

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="searchText" class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="searchText" placeholder="Digite a Nota/OV/Ordem/DR"
                        wire:model.defer="search">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="startDate" class="form-label">Data de Início</label>
                    <input type="date" class="form-control" id="startDate" wire:model.defer="dt_in">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="endDate" class="form-label">Data de Fim</label>
                    <input type="date" class="form-control" id="endDate" wire:model.defer="dt_out">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" wire:click.prevent="pesquisar">Pesquisar</button>

        </div>
    </div>


    @if (!$lists)
        <div class="card mt-4">
            <h4 class="text-center">SEM INFORMES PARCIAL</h4>
        </div>
    @else
        <div class="card mt-4">
            <div class="card-header edp-bg-seoweedgreen-100 text-white">
                Lista de Obras Parciais Informadas
            </div>
            <table class="table table-sm table-striped table-hover">
                <thead>
                    <tr class="text-center">
                        <th scope="col">Nota/OV</th>
                        <th scope="col">Ordem</th>
                        <th scope="col">Files</th>
                        <th scope="col">Dta Envio</th>
                        <th scope="col">Dt Aprovação</th>
                        <th scope="col">Dt Fiscalizacao</th>
                        <th scope="col">Dt Pagamento</th>
                        <th scope="col">Valor Ads</th>
                        <th scope="col">Status</th>
                        <th scope="col">Finalizado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lists as $list)
                        <tr class="text-center" style="cursor: pointer;"
                            wire:dblclick.prevent="$emitTo('partner.show.show-partial-info', 'show_form', {{ $list->id }})"
                            wire:key="partial-hist{{ $list->id }}">
                            <td class="fw-bold">{{ $list->Note->note }}</td>
                            <td>
                                @if ($list->Orders)
                                    @foreach ($list->Orders as $order)
                                        <p class="my-0 py-0">{{ $order->ordem }}</p>
                                    @endforeach
                                @endif
                            </td>
                            <td> <x-files.select-download-list :files='$list->Note->Files' /></td>
                            <td>{{ Carbon::parse($list->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td class="@if (!$list->allow && !$list->deny) text-bg-info fw-bold @endif">
                                @if (!$list->allow && !$list->deny)
                                    {{ Carbon::parse($list->created_at)->diffInDays() }}
                                @else
                                    {{ $list->decision_at ? Carbon::parse($list->decision_at)->format('d/m/Y H:i:s') : '---' }}
                                @endif
                            </td>
                            <td class="@if ($list->allow && !$list->supervision) text-bg-info fw-bold @endif">
                                @if ($list->allow && !$list->supervision)
                                    {{ Carbon::parse($list->decision_at)->diffInDays() }}
                                @else
                                    {{ $list->supervision_at ? Carbon::parse($list->supervision_at)->format('d/m/Y H:i:s') : '---' }}
                                @endif
                            </td>
                            <td class="@if ($list->supervision && !$list->payment) text-bg-info fw-bold @endif">
                                @if ($list->supervision && !$list->payment)
                                    {{ Carbon::parse($list->supervision_at)->diffInDays() }}
                                @else
                                    {{ $list->payment_at ? Carbon::parse($list->payment_at)->format('d/m/Y H:i:s') : '---' }}
                                @endif
                            </td>
                            <td class="fs-6 fw-bold">
                                {{ 'R$ ' . number_format($list->value, 2, ',', '.') }}</td>
                            <td class="{{ $this->partialStatus($list)['color'] }} fs-6 fw-bold">
                                {{ $this->partialStatus($list)['status'] }}</td>
                            <td>{{ $list->complete ? 'SIM' : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
        {{ $lists->links() }}
    @endif

    @livewire('partner.show.show-partial-info', key('show_partial_info'))
</div>
