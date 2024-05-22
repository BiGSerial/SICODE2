@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
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
                /* Escala vertical inicial: 0 */
            }

            to {

                transform: scaleY(1);
                /* Escala vertical final: 1 (sem mudança de tamanho) */
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
                    <select name="" id="" class="form-select border border-secondary">
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
                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'partner', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'partner', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                    @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'partner', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'partner'], key('removeAll'))
                </div>
            </div>
        </div>
    </div>
    {{-- END SearchBar and Filters --}}

    {{-- START LIST --}}
    @if (!$lists->count())
        <div class="text-center my-5 py-3">
            <h3>NENHUMA ATIVIDADE ENCONTRADA</h3>
        </div>
    @endif

    @if ($lists->count())
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
            <h4 class="card-header  edp-bg-seoweedgreen-100 text-white">VIABILIDADE A EXECUTAR</h4>

            <div class="table-responsive">
                <table class="table table-sm table-condensed table-striped table-hover">
                    <thead>
                        <th scope="col" class="text-center align-middle"></th>
                        <th scope="col" class="text-center align-middle">Nota/OV</th>
                        <th scope="col" class="text-center align-middle">Arquivos</th>
                        <th scope="col" class="text-center align-middle">Ordem</th>
                        <th scope="col" class="text-center align-middle">Contratado</th>
                        <th scope="col" class="text-center align-middle">Recebido</th>
                        <th scope="col" class="text-center align-middle">Prazo Viab</th>
                        <th scope="col" class="text-center align-middle">Prazo Obra</th>
                        <th scope="col" class="text-center align-middle">Rubrica</th>
                        <th scope="col" class="text-center align-middle">Regiao</th>
                        <th scope="col" class="text-center align-middle">Municipio</th>
                        <th scope="col" class="text-center align-middle">Status</th>
                        <th scope="col" class="text-center align-middle">Acao</th>
                        <th scope="col" class="text-center align-middle"></th>
                        <th scope="col" class="text-center align-middle"></th>
                    </thead>
                    <tbody class="table-group-divider">
                        @foreach ($lists as $index => $list)
                            @php
                                $status = null;

                                $dueDate = $list->Viabilities->count()
                                    ? Carbon::parse($list->Viabilities->last()->sended_at)->addDays(7)
                                    : null;
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

                                // Dias Restantes
                                if ($list->type_note == 1) {
                                    if ($list->mesalization && $list->mesalization != 'erro') {
                                        preg_match('/\d+\/\d+/', $list->mesalization, $matches);

                                        if (!empty($matches)) {
                                            [$mes, $ano] = explode('/', $matches[0]);

                                            if ($mes >= 1) {
                                                $data = "{$ano}-{$mes}-28 23:59:59";

                                                $hoje = Carbon::now();

                                                $dataCarbon = Carbon::createFromFormat('Y-m-d H:i:s', $data);

                                                $days_left = $hoje->diffInDays($dataCarbon, false);
                                            } else {
                                                $data = "{$ano}-12-28 23:59:59";

                                                $hoje = Carbon::now();

                                                $dataCarbon = Carbon::createFromFormat('Y-m-d H:i:s', $data);

                                                $days_left = $hoje->diffInDays($dataCarbon, false);
                                            }
                                        }
                                    }
                                } elseif ($list->type_note == 2) {
                                    $days_left = $list->days_left;
                                }

                                if ($list->Viabilities->count()) {
                                    $count = 0;

                                    foreach ($list->Viabilities as $order) {
                                        if ($order->approved) {
                                            $count++;

                                            $block = [
                                                'color' => 'green',
                                                'command' => true,
                                            ];

                                            $color = 'green';
                                        } elseif ($order->rejected) {
                                            $count++;

                                            $block = [
                                                'color' => 'danger',
                                                'command' => true,
                                            ];

                                            $color = 'red';
                                        }

                                        if (($order->rejected || $order->approved) && !$order->completed) {
                                            $status = [
                                                'color' => 'text-bg-primary',
                                                'info' => 'EM AVALIAÇÂO',
                                            ];
                                        }
                                    }

                                    if ($count == $list->Viabilities->count()) {
                                        $block = array_merge($block, ['command' => false]);
                                    }
                                }

                            @endphp
                            <tr style="cursor: pointer;"
                                wire:dblclick="$emitTo('partner.actions.responserviab','getInfoResponse', {{ $list }})">
                                <td></td>
                                <td class="text-center align-middle">{{ $list->note }}</td>
                                <td class="text-center align-middle">
                                    {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                    <x-files.select-download-list :files='$list->Files' />
                                </td>
                                <td class="text-center align-middle">
                                    @if ($list->Viabilities->count())
                                        @foreach ($list->Viabilities as $viab)
                                            <p class="p-0 m-1">
                                                {{ $viab->Order->ordem }}
                                            </p>
                                        @endforeach
                                    @endif
                                </td>

                                <td class="text-center align-middle">
                                    {{ $list->Viabilities->first()->hired ? 'SIM' : 'NÃO' }}</td>
                                <td class="text-center align-middle fw-bold">
                                    {{ Carbon::parse($list->Viabilities->last()->sended_at)->format('d/m/Y') }}
                                </td>
                                <td class="text-center align-middle text-danger fw-bold">
                                    {{ Carbon::parse($list->Viabilities->last()->sended_at)->addDays(7)->format('d/m/Y') }}
                                </td>
                                <td class="text-center align-middle text-primary fw-bold">
                                    {{ Carbon::parse($list->Viabilities->last()->sended_at)->addDays($days_left)->format('d/m/Y') }}
                                </td>
                                <td class="text-center align-middle">{{ $list->rubrica }}</td>
                                <td class="text-center align-middle">
                                    {{ $cities->Where('rdMunicipio', $list->nexp)->first() ? $cities->Where('rdMunicipio', $list->nexp)->first()->regiao : '' }}
                                </td>
                                <td class="text-center align-middle">{{ $list->lexp }}</td>

                                <td class="text-center align-middle"><span
                                        class="badge {{ Viabilitiesstatus::status($list->Viabilities->last()->status)->colorbg }} word-wrap">{{ Viabilitiesstatus::status($list->Viabilities->last()->status)->status }}</span>
                                </td>
                                <td class="text-center align-middle">
                                    @if ($list->Viabilities->last()->status == 5)
                                        <span class="badge text-bg-danger blink">Requer Ação</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">

                                    <i class="bx bx-printer text-primary fs-4 me-2" role="group"
                                        aria-label="Basic example" tabindex="0" data-bs-toggle="popover"
                                        data-bs-trigger="hover focus" data-bs-placement="right"
                                        data-bs-title="Imprimir Checklist (NÃO IMPLEMENTADO)"
                                        data-bs-content="<p>Gera o PDF para impressão da ORDEM/NOTA.</p>"></i>

                                    @if (!$block || $block['command'])
                                        <i class="bx bxs-badge-check text-success fs-4 me-2" style="cursor: pointer;"
                                            wire:click.prevent="openForms({{ $list->id }})" role="group"
                                            aria-label="Basic example" tabindex="0" data-bs-toggle="popover"
                                            data-bs-trigger="hover focus" data-bs-placement="right"
                                            data-bs-title="Encerrar Atividaede"
                                            data-bs-content="<p>Entrega os informes da Obra.</p>"></i>
                                    @endif

                                </td>
                                <td class="align-middle">
                                    
                                    <input class="form-check-input border border-secondary" type="checkbox"
                                        value="{{ $list->id }}" wire:model="inActivity.{{ $list->id }}"
                                        wire:click.prevent="putInActvity({{ $list->id }})">
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
</div>
