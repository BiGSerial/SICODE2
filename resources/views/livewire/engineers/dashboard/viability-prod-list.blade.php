<div>
    <x-show-loading />

    <div class="card" wire:ignore.self>
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Lista Produtividade <span class="fs-6 fw-bold">(De
                    {{ date('d/m/Y', strToTime($startDate)) }} à
                    {{ date('d/m/Y', strToTime($endDate)) }})</span></h3>
            <button class="btn btn-sm btn-secondary ml-auto" wire:click="$refresh" wire:loading.attr="disabled">
                <i class="ri-refresh-line" wire:loading.remove></i>
                <span wire:loading wire:target="$refresh" class="spinner-border spinner-border-sm" role="status"
                    aria-hidden="true"></span>
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="startDate">Data Inicio:</label>
                    <input type="date" id="startDate" class="form-control" wire:model="startDate">
                </div>
                <div class="col-md-4">
                    <label for="endDate">Data Fim:</label>
                    <input type="date" id="endDate" class="form-control" wire:model="endDate">
                </div>

            </div>
        </div>
        @if ($lists->isNotEmpty())
            <table class="table table-striped table-hover">
                <thead>
                    <tr class="text-center">
                        <th>Empreiteira</th>
                        <th>Não Realizado</th>
                        <th>Aprovados</th>
                        <th>Rejeitados</th>
                        <th>Em Viabilidade</th>
                        <th>Total</th>
                        {{-- <th>Valor_MOAberto</th> --}}
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lists as $list)
                        <tr class="text-center align-middle">
                            <td class='text-end'>{{ $list->Company->name }}</td>
                            <td>{{ $list->tacit_total }}</td>
                            <td>{{ $list->approved_completed }}</td>
                            <td>{{ $list->rejected_total }}</td>
                            <td>{{ $list->status_1_total }}</td>
                            <td class="fw-bold">{{ $list->total_records }}</td>
                            {{-- <td><span class="fw-bold">~R$</span>
                                {{ number_format($list->orders_sum_moaberto, 2, ',', '.') }}</td> --}}
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @endif

    </div>


</div>
