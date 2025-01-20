@php
    use Carbon\Carbon;
@endphp
<div>
    {{-- If your happiness depends on money, you will never be happy with yourself. --}}
    <div class="card">
        <div class="card-header">
            Pesquisa
        </div>
        <div class="card-body">
            <form>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="searchText" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="searchText" placeholder="Digite a Nota/OV/Ordem/DR">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="startDate" class="form-label">Data de Início</label>
                        <input type="date" class="form-control" id="startDate">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="endDate" class="form-label">Data de Fim</label>
                        <input type="date" class="form-control" id="endDate">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Pesquisar</button>
            </form>
        </div>
    </div>


    @if (!$lists)
        <div class="card mt-4">
            <h4 class="text-center">SEM INFORMES PARCIAL</h4>
        </div>
    @else
        <div class="card mt-4">
            <div class="card-header">
                Lista de Obras Parciais Informadas
            </div>
            <table class="table">
                <thead>
                    <tr class="text-center">
                        <th scope="col">Nota/OV</th>
                        <th scope="col">Ordem</th>
                        <th scope="col">Dta Envio</th>
                        <th scope="col">Dt Aprovação</th>
                        <th scope="col">Dt Fiscalizacao</th>
                        <th scope="col">Dt Pagamento</th>
                        <th scope="col">Status</th>
                        <th scope="col">Finalizado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lists as $list)
                        <tr class="text-center">
                            <td class="fw-bold">{{ $list->Note->note }}</td>
                            <td>
                                @if ($list->Orders)
                                    @foreach ($list->Orders as $order)
                                        <p class="my-0 py-0">{{ $order->order }}</p>
                                    @endforeach
                                @endif
                            </td>
                            <td>{{ Carbon::parse($list->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $list->  }}</td>
                            <td>{{ $list->dta_fiscalizacao }}</td>
                            <td>{{ $list->dta_pagamento }}</td>
                            <td>{{ $list->status }}</td>
                            <td>{{ $list->finalizado }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
