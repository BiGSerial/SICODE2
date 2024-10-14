@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
    use App\Helpers\DaysLeft;

@endphp

@push('css')
    <style>
        .item {
            animation: slideIn 0.5s forwards;
            opacity: 0;
        }

        .item.hidden {
            animation: slideOut 0.5s forwards;
        }

        .detail-item {
            opacity: 0;
            animation: growDown 0.5s forwards;
            transform-origin: top;
        }

        @keyframes growDown {
            from {
                transform: scaleY(0);
            }

            to {
                transform: scaleY(1);
            }
        }

        @keyframes slideIn {
            0% {
                opacity: 0;
                transform: translateX(100%);
            }

            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes blink {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }

        .blink {
            animation: blink 2s infinite;
        }
    </style>
@endpush

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

                <div class="col-1">
                    <input type="date" id="date_in" class="form-control border border-secondary"
                        wire:model="date_in">
                </div>

                <div class="col-1">
                    <input type="date" id="date_out" class="form-control border border-secondary"
                        wire:model="date_out">
                </div>

                <div class="col-1">
                    <select name="" id="" class="form-select border border-secondary"
                        wire:model="dateBy">
                        <option value="sended_at">Recebido</option>
                        <option value="returned_at">Viabilizado</option>
                        <option value="completed_at">Completado</option>
                    </select>
                </div>
                <div class='col align-middle'><button class="btn btn-danger btn-sm align-middle"
                        wire:click.prevent='cleanAll()'><i class="ri-find-replace-line fs-5"></i></button></div>
                <div class="col-5 d-flex justify-content-end">
                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'hiring_hist', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'hiring_hist', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                    @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'hiring_hist', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'hiring_hist'], key('removeAll'))
                </div>
            </div>
        </div>
    </div>
    {{-- END SearchBar and Filters --}}

    {{-- START LIST --}}
    @if ($lists->isEmpty())
        <div class="text-center my-5 py-3">
            <h3>NENHUMA ATIVIDADE ENCONTRADA</h3>
        </div>
    @endif

    @if ($lists->isNotEmpty())
        {{-- Paginador --}}
        <div class="row mt-3">
            <div class="col-6">
                {{ $lists->links() }}
            </div>
            <div class="col-6 d-flex justify-content-end align-middle">
                <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                    {{ $lists->lastItem() }}
                    de {{ $lists->total() }}
                    registros.</span>
            </div>
        </div>
        {{-- FIM Paginador --}}
        <div class="card mb-2 edp-bg-gray">
            <h4 class="card-header  edp-bg-seoweedgreen-100 text-white">HISTÓRICO DE VIABILIDADE</h4>

            <div class="table-responsive">
                <table class="table table-sm table-condensed table-striped table-hover">
                    <thead>
                        <th scope="col" class="text-center align-middle"></th>
                        <th scope="col" class="text-center align-middle">Nota/OV</th>
                        <th scope="col" class="text-center align-middle">Arquivos</th>
                        <th scope="col" class="text-center align-middle">Ordem</th>
                        <th scope="col" class="text-center align-middle">Enviado</th>
                        <th scope="col" class="text-center align-middle">Contratado</th>
                        <th scope="col" class="text-center align-middle">Empreiteira</th>
                        <th scope="col" class="text-center align-middle">Responsável</th>
                        <th scope="col" class="text-center align-middle">Rubrica</th>
                        <th scope="col" class="text-center align-middle">Regiao</th>
                        <th scope="col" class="text-center align-middle">Municipio</th>
                        <th scope="col" class="text-center align-middle">Status</th>
                        <th scope="col" class="text-center align-middle"></th>
                    </thead>
                    <tbody class="table-group-divider">
                        @foreach ($lists as $index => $list)
                            @php
                                $status = null;

                                $dueDate = Carbon::parse($list->sended_at)->addDays($list->getDays() + 7);
                                $today = Carbon::now();
                                $daysDifference = 0;

                                if ($dueDate) {
                                    $daysDifference = $dueDate ? $today->diffInDays($dueDate) : null;

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
                                $days_left = 0;
                                $days_left = (new DaysLeft($list->Note))->getDaysLeft();
                                // Dias Restantes

                                if ($list->count()) {
                                    $count = 0;

                                    if ($list->approved) {
                                        $count++;

                                        $block = [
                                            'color' => 'green',
                                            'command' => true,
                                        ];

                                        $color = 'green';
                                    } elseif ($list->rejected) {
                                        $count++;

                                        $block = [
                                            'color' => 'danger',
                                            'command' => true,
                                        ];

                                        $color = 'red';
                                    }

                                    if (($list->rejected || $list->approved) && !$list->completed) {
                                        $status = [
                                            'color' => 'text-bg-primary',
                                            'info' => 'EM AVALIAÇÂO',
                                        ];
                                    }

                                    if ($count) {
                                        $block = array_merge($block, ['command' => false]);
                                    }
                                }

                                $color = '';

                                if ($list->approved && !$list->rejected && !$list->tacit) {
                                    $color = 'green';
                                } elseif (!$list->approved && $list->rejected && !$list->tacit) {
                                    $color = 'red';
                                } elseif ($list->tacit) {
                                    $color = 'yellow';
                                }

                                $tcolor = '';

                                if ($list->hired) {
                                    $tcolor = 'table-success';
                                }

                            @endphp
                            <tr wire:key="viability-{{ $list->id }}"
                                wire:dblclick="$emitTo('partner.actions.responserviab','getInfoResponse', {{ $list }})"
                                style="cursor: pointer; border-left: 8px solid {{ $color }};">
                                <td>
                                </td>
                                <td class="text-center align-middle">{{ $list->Note->note }}</td>
                                <td class="text-center align-middle">
                                    {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                    <x-files.select-download-list :files='$list->Note->Files' />
                                </td>
                                <td class="text-center align-middle">
                                    @if ($list->Orders->isNotEmpty())
                                        @foreach ($list->Orders as $order)
                                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                                        @endforeach
                                    @else
                                        @if ($list->Note->Orders->isNotEmpty())
                                            @foreach ($list->Note->Orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                                <p class="my-0 py-0">{{ $order->ordem }}</p>
                                            @endforeach
                                        @endif
                                    @endif
                                </td>

                                <td class="text-center align-middle fw-bold">
                                    {{ Carbon::parse($list->sended_at)->format('d/m/Y') }}
                                </td>
                                <td class="text-center align-middle text-success fw-bold">
                                    {{ isset($list->hired_at) ? Carbon::parse($list->hired_at)->format('d/m/Y') : '---' }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ isset($list->Company) ? $list->Company->name : '---' }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ isset($list->Engineer) ? $list->Engineer->name : '---' }}
                                </td>
                                <td class="text-center align-middle">{{ $list->Note->rubrica }}</td>
                                <td class="text-center align-middle">
                                    {{ $cities->Where('rdMunicipio', $list->Note->nexp)->first() ? $cities->Where('rdMunicipio', $list->Note->nexp)->first()->regiao : '' }}
                                </td>
                                <td class="text-center align-middle">{{ $list->Note->lexp }}</td>

                                <td class="text-center align-middle"><span
                                        class="badge {{ Viabilitiesstatus::status($list->status)->colorbg }} word-wrap">{{ Viabilitiesstatus::status($list->status)->status }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <i class="ri-pencil-fill text-primary fs-5" style="cursor: pointer;"
                                        wire:click.prevent="$emitTo('construction.hiring.actions.edit', 'edit_hiring', {{ $list->id }})"></i>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginador --}}
        <div class="row mt-3">
            <div class="col-6">
                {{ $lists->links() }}
            </div>
            <div class="col-6 d-flex justify-content-end align-middle">
                <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                    {{ $lists->lastItem() }}
                    de {{ $lists->total() }}
                    registros.</span>
            </div>
        </div>
        {{-- FIM Paginador --}}
    @endif
    {{-- END LIST --}}

    {{-- Livewire Components --}}
    @livewire('partner.actions.responserviab', key('reesponser_modal_viab'))
    @livewire('construction.hiring.actions.edit', key('hiring-edit'))

    {{-- Scripts --}}
    <script>
        document.addEventListener('livewire:load', function() {
            const dateIn = document.getElementById('date_in');
            const dateOut = document.getElementById('date_out');

            dateIn.addEventListener('change', function() {
                dateOut.min = dateIn.value;
            });

            // Optionally, you can also set the initial state on page load
            if (dateIn.value) {
                dateOut.min = dateIn.value;
            }

            // Prevent manual date entry
            dateIn.addEventListener('keydown', function(e) {
                e.preventDefault();
            });

            dateOut.addEventListener('keydown', function(e) {
                e.preventDefault();
            });
        });
    </script>
</div>
