@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
    use App\Helpers\DaysLeft;
@endphp

<div>
    <x-show-loading />


    {{-- START SearchBar and Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-1">
                    <select name="" id="" class="form-select border border-secondary" wire:model="perPage">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <div class="col-2">
                    <input type="text" class="form-control border border-secondary" placeholder="Buscar"
                        wire:model.debounce.2s="search">
                </div>

                <div class="col-3">

                </div>

                <div class="col-6 d-flex justify-content-end">
                    @livewire('components.filter.filter', ['myKey' => 'company', 'sendFilter' => '', 'model' => 'App\Models\Company', 'column' => 'id', 'filter' => 'Empresa', 'group_filter' => 'engineer', 'values' => 'name', 'direction' => 'ASC', 'query' => ''], key('company'))
                    @livewire('components.filter.filter', ['myKey' => 'responsible', 'sendFilter' => '', 'model' => 'App\Models\User', 'column' => 'id', 'filter' => 'Responsável', 'group_filter' => 'engineer', 'values' => 'name', 'direction' => 'ASC', 'query' => 'responsible = true'], key('responsavel'))
                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'engineer', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'engineer', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                    @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'engineer', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'engineer'], key('removeAll'))
                </div>
            </div>
        </div>
    </div>


    {{-- START LIST --}}
    @if (!$myLists->count())
        <div class="text-center my-5 py-3">
            <h3>NENHUM RETORNO INTERNO EM ESPERA</h3>
        </div>
    @endif

    @if ($myLists->count())
        <div class="card mb-2 edp-bg-gray">
            <div class="card-header edp-bg-seoweedgreen-100 text-white">
                <div class="row">
                    <div class="col">
                        <h4 class="my-0">RETORNO INTERNO CONCLUÍDO</h4>
                    </div>
                    <div class="col-3 d-flex justify-content-end">

                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'><i
                                class="ri-file-excel-2-line align-middle"></i> Exportar</button>

                    </div>
                </div>
            </div>
            <div class="table-responsible">
                <table class="table table-sm table-condensed table-hover table-striped">
                    <thead>
                        <tr>

                            <th scope="col" class="text-center align-middle">Nota/OV</th>
                            <th scope="col" class="text-center align-middle">Arquivos</th>
                            <th scope="col" class="text-center align-middle">Ordem</th>
                            <th scope="col" class="text-center align-middle">Contratado</th>
                            <th scope="col" class="text-center align-middle">Viabilizado Em</th>
                            <th scope="col" class="text-center align-middle">Motivo</th>
                            <th scope="col" class="text-center align-middle">Responsável</th>
                            <th scope="col" class="text-center align-middle">Rubrica</th>
                            <th scope="col" class="text-center align-middle">Municipio</th>
                            <th scope="col" class="text-center align-middle">Status</th>
                            <th scope="col" class="text-center align-middle">Empreiteira</th>
                            <th scope="col" class="text-center align-middle">Tempo</th>
                            <th scope="col" class="text-center align-middle"></th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($myLists as $myViab)
                            @php
                                $status = null;

                                $dueDate = Carbon::parse($myViab->sended_at)->addDays($myViab->getDays() + 7);

                                $today = Carbon::now();
                                $daysDifference = 0;

                                if ($dueDate) {
                                    $daysDifference = $today->diffInDays($dueDate);

                                    if ($dueDate->isBefore($today)) {
                                        $daysDifference *= -1;
                                    }

                                    if ($daysDifference < 1) {
                                        $status = [
                                            'color' => 'text-bg-danger',
                                            'info' => 'VENCIDO',
                                        ];
                                    } elseif ($daysDifference >= 1 && $daysDifference < 3) {
                                        $status = [
                                            'color' => 'text-bg-warning',
                                            'info' => 'VENCENDO',
                                        ];
                                    } elseif ($daysDifference >= 3) {
                                        $status = [
                                            'color' => 'text-bg-success',
                                            'info' => 'NO PRAZO',
                                        ];
                                    }
                                }

                                $block = null;
                                $color = 'grey';
                                $days_left = (new DaysLeft($myViab->Note))->getDaysLeft();
                                $count = 0;

                                if ($myViab->approved) {
                                    $count++;
                                    $block = [
                                        'color' => 'success',
                                        'command' => true,
                                    ];

                                    $color = 'green';
                                } elseif ($myViab->rejected) {
                                    $count++;
                                    $block = [
                                        'color' => 'danger',
                                        'command' => true,
                                    ];

                                    $color = 'red';
                                }

                                if (($myViab->rejected || $myViab->approved) && !$myViab->completed) {
                                    $status = [
                                        'color' => 'text-bg-primary',
                                        'info' => 'EM AVALIAÇÂO',
                                    ];
                                }

                                $color = '';

                                if ($myViab->approved && !$myViab->rejected && !$myViab->tacit) {
                                    $color = 'green';
                                } elseif (!$myViab->approved && $myViab->rejected && !$myViab->tacit) {
                                    $color = 'red';
                                } elseif ($myViab->tacit) {
                                    $color = 'yellow';
                                }

                                $tcolor = '';

                                if ($myViab->hired) {
                                    $tcolor = 'table-success';
                                }
                            @endphp

                            <tr wire:key='Myviab_{{ $myViab->id }}'>
                                <td class="text-center align-middle fw-bold">{{ $myViab->Note->note }}</td>
                                <td class="text-center align-middle"> <x-files.select-download-list :files='$myViab->Note->Files' />
                                </td>
                                <td class="text-center align-middle">
                                    @if ($myViab->Orders->isNotEmpty())
                                        @foreach ($myViab->Orders as $order)
                                            <p class="p-0 m-1">
                                                {{ $order->ordem }}
                                            </p>
                                        @endforeach
                                    @elseif ($myViab->Note->Orders->isNotEmpty())
                                        @foreach ($myViab->Note->Orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                            <p class="p-0 m-1">
                                                {{ $order->ordem }}
                                            </p>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="text-center align-middle">{{ $myViab->hired ? 'SIM' : 'NÃO' }}</td>
                                <td class="text-center align-middle">
                                    {{ Carbon::parse($myViab->returned_at)->format('d/m/Y') }}</td>
                                <td class="text-center align-middle fw-bold">
                                    @if ($myViab->Form)
                                        {{ $myViab->Form->reason }}
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if ($myViab->Engineer)
                                        <p class="my-0 py-0">{{ $myViab->Engineer->name }}</p>
                                        <p class="my-0 py-0 text-primary">{{ $myViab->Engineer->email }}</p>
                                    @endif
                                </td>
                                <td class="text-center align-middle">{{ $myViab->Note->rubrica }}</td>
                                <td class="text-center align-middle">{{ $myViab->Note->lexp }}</td>
                                <td class="text-center align-middle"><span
                                        class="badge {{ Viabilitiesstatus::status($myViab->status)->colorbg }} word-wrap">{{ Viabilitiesstatus::status($myViab->status)->status }}</span>
                                </td>
                                <td class="text-center align-middle">{{ $myViab->Company->name }}</td>
                                <td class="text-center align-middle text-danger">
                                    {{ Carbon::parse($myViab->updated_at)->diffForHumans() }}</td>
                                <td class="text-center align-middle"> <i
                                        class="ri-play-circle-line @if ($myViab->treplica) text-danger @else text-success @endif fs-4 me-2"
                                        style="cursor: pointer;"
                                        wire:click.prevent="$emitTo('responsible.actions.return-intern-response', 'getInfoResponse', '{{ $myViab->id }}')"
                                        role="group" aria-label="Basic example" tabindex="0"
                                        data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="right"
                                        data-bs-title="Responder Viabilidade"
                                        data-bs-content="<p>Réplica ao questionamento da Viabilidade.</p>"></i></td>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        {{-- Paginador --}}
        <div class="row mt-3">
            <div class="col-6">
                {{ $myLists->links() }}
            </div>
            <div class="col-6 d-flex justify-content-end align-middle">
                <span class="align-middle"> Exibindo {{ $myLists->firstItem() }} até
                    {{ $myLists->lastItem() }}
                    de {{ $myLists->total() }}
                    registros.</span>
            </div>
        </div>
        {{-- FIM Paginador --}}

        <hr>
    @endif






    {{-- Componentes Livewire --}}
    @livewire('responsible.actions.return-intern-response', key('return-intern-response'))
</div>
