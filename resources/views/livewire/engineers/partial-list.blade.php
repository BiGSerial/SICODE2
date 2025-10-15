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
                <div class="col">
                    <label for="searchText" class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="searchText" placeholder="Digite a Nota/OV/Ordem/DR"
                        wire:model.defer="search">
                </div>
                <div class="col">
                    <label for="startDate" class="form-label">Mês Referencia</label>
                    <input type="month" class="form-control" id="startDate" wire:model="month">
                </div>
                <div class="col">
                    <label for="startDate" class="form-label">Data de Início</label>
                    <input type="date" class="form-control" id="startDate" wire:model.defer="dt_in">
                </div>
                <div class="col">
                    <label for="endDate" class="form-label">Data de Fim</label>
                    <input type="date" class="form-control" id="endDate" wire:model.defer="dt_out">
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary" wire:click.prevent="pesquisar"><i
                            class="bi bi-search"></i> Pesquisar</button>
                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'partial', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'partial'], key('removeAll'))
                </div>
            </div>
        </div>
    </div>


    @if (!$lists->count())
        <div class="card my-2 py-4">
            <h4 class="text-center my-0 py-0">SEM INFORMES PARCIAL PARA APROVAÇÃO</h4>
        </div>
    @else
        <div class="card mt-4">
            <div class="card-header edp-bg-seoweedgreen-100 text-white">
                LISTA DE OBRAS PARCIAIS AGUARDANDO APROVAÇÂO.

            </div>
            <table class="table table-sm table-striped table-hover">
                <thead>
                    <tr class="text-center">
                        <th scope="col">Nota/OV</th>
                        <th scope="col">Ordem</th>
                        <th scope="col">Rubrica</th>
                        <th scope="col">Valor</th>
                        <th scope="col">Empreiteira</th>
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
                        <tr class="text-center" style="cursor: pointer;" data-bs-toggle="popover"
                            data-bs-placement="left" data-bs-trigger="hover"
                            data-bs-content="duplo clique para mais detalhes"
                            wire:dblclick.prevent="$emitTo('engineers.actions.check-partial', 'show_form', {{ $list->id }})"
                            wire:click="$set('selectedRow', {{ $list->id }})"
                            class="{{ $selectedRow === $list->id ? 'table-primary' : '' }}">
                            <td class="fw-bold">{{ $list->Note->note }}</td>
                            <td>
                                @if ($list->Orders)
                                    @foreach ($list->Orders as $order)
                                        <p class="my-0 py-0">{{ $order->ordem }}</p>
                                    @endforeach
                                @endif
                            </td>
                            <td class="fw-bold">{{ $list->Note->rubrica }}</td>
                            <td class="fw-bold">R$ {{ number_format($list->value, 2, ',', '.') }}</td>
                            @php
                                $company = $list->Company ? $list->Company->name : 'Desconhecido';
                            @endphp
                            <td>{{ $company }}</td>
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
                            <td class="{{ $this->partialStatus($list)['color'] }} fs-6 fw-bold">
                                {{ $this->partialStatus($list)['status'] }}</td>
                            <td>{{ $list->complete ? 'SIM' : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <div>{{ $lists->links() }}</div>
            <small>
                Mostrando {{ $lists->firstItem() ?? 0 }} até {{ $lists->lastItem() ?? 0 }} de
                {{ $lists->total() }} registros
            </small>
        </div>
    @endif

    @livewire('engineers.actions.check-partial', key('check_partial'))
</div>
