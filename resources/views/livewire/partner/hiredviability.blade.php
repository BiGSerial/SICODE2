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
            <h4 class="card-header  edp-bg-seoweedgreen-100 text-white">VIABILIDADE CONTRATADAS A EXECUTAR</h4>


            @foreach ($lists as $index => $list)
                {{-- Start Line Item --}}
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


                <div x-data="{ isShow: false }" style="overflow: hidden;" wire:key="{{ $list->id }}">
                    <div class="align-items-center mb-2" x-show="!isShow" style="animation-delay: {{ $index * 0.03 }}s">

                        <div class="clear-fix" style="border-left: 15px solid {{ $color }}">
                            <table class="table table-sm my-0 table-striped-columns">
                                <thead>
                                    <th scope="col" class="col-2 text-center">Nota/Ov</th>
                                    <th scope="col" class="col-2 text-center">Ordem</th>
                                    <th scope="col" class="col-1 text-center">Rubrica</th>
                                    <th scope="col" class="col-1 text-center">Regiao</th>
                                    <th scope="col" class="col-2 text-center">Municipio</th>
                                    <th scope="col" class="col-1 text-center">Recebido Em</th>
                                    <th scope="col" class="col-1 text-center">Prazo Estimado</th>
                                    <th scope="col" class="col-1 text-center">Pze Restante</th>
                                    <th scope="col" class="col-1 text-center">Status</th>
                                    <th scope="col" class="col-1 text-center">Ação</th>
                                    <th scope="col" class="d-flex justify-content-end">
                                        <button class=" btn btn-sm btn-primary" @click="isShow=true">
                                            <i class="bx bx-caret-down-circle align-middle fs-5"></i>
                                        </button>
                                    </th>
                                </thead>
                                <tbody class="">
                                    <tr>
                                        <td class="fw-bold text-center">{{ $list->note }}</td>
                                        <td class=" text-center">
                                            @if ($list->Viabilities->count())
                                                <p class="p-0 m-0">
                                                    {{ $list->Viabilities->first()->Order->ordem }}
                                                    @if ($list->Viabilities->count() > 1)
                                                        <span
                                                            class="badge text-bg-primary">+{{ $list->Viabilities->count() - 1 }}</span>
                                                    @endif
                                                </p>
                                            @endif
                                        </td>
                                        <td class="text-uppercase text-center">{{ $list->rubrica }}</td>
                                        <td class="text-uppercase text-center">
                                            {{ $cities->Where('rdMunicipio', $list->nexp)->first() ? $cities->Where('rdMunicipio', $list->nexp)->first()->regiao : '' }}
                                        </td>
                                        <td class="text-uppercase text-center">{{ $list->lexp }}</td>
                                        <td class="fw-bold text-center">
                                            {{ Carbon::parse($list->Viabilities->last()->sended_at)->format('d/m/Y') }}
                                        </td>
                                        <td class="fw-bold text-danger text-center">
                                            {{ Carbon::parse($list->Viabilities->last()->sended_at)->addDays(7)->format('d/m/Y') }}
                                        </td>
                                        <td class=" text-center">
                                            {{ $days_left }}
                                        </td>
                                        <td class=" text-center"><span
                                                class="badge {{ Viabilitiesstatus::status($list->Viabilities->last()->status)->colorbg }}">{{ Viabilitiesstatus::status($list->Viabilities->last()->status)->status }}</span>
                                        </td>

                                        <td class=" text-center">
                                            @if ($list->Viabilities->last()->status == 5)
                                                <span class="badge text-bg-danger blink">Requer Ação</span>
                                            @endif
                                        </td>
                                        <td class="d-flex justify-content-end">
                                            <i class="bx bx-printer text-primary fs-4 me-2" role="group"
                                                aria-label="Basic example" tabindex="0" data-bs-toggle="popover"
                                                data-bs-trigger="hover focus" data-bs-placement="right"
                                                data-bs-title="Imprimir Checklist (NÃO IMPLEMENTADO)"
                                                data-bs-content="<p>Gera o PDF para impressão da ORDEM/NOTA.</p>"></i>

                                            @if (!$block || $block['command'])
                                                <i class="bx bxs-badge-check text-success fs-4 me-2"
                                                    style="cursor: pointer;"
                                                    wire:click.prevent="openForms({{ $list->id }})" role="group"
                                                    aria-label="Basic example" tabindex="0" data-bs-toggle="popover"
                                                    data-bs-trigger="hover focus" data-bs-placement="right"
                                                    data-bs-title="Encerrar Atividaede"
                                                    data-bs-content="<p>Entrega os informes da Obra.</p>"></i>
                                            @endif



                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>

                    {{-- CARD EXTENDED --}}
                    <div class="card mb-5 shadow" style="display: none;" x-show="isShow" @click.away="isShow=false">
                        <div class="card-body">
                            <table class="table table-sm my-0">
                                <thead>
                                    <th scope="col">Nota/Ov</th>
                                    <th scope="col">Ordem</th>
                                    <th scope="col">Rubrica</th>
                                    <th scope="col">Arquivos</th>
                                    <th scope="col">Regiao</th>
                                    <th scope="col">Centro</th>
                                    <th scope="col">Municipio</th>
                                    <th scope="col" class="d-flex justify-content-end"><button
                                            class=" btn btn-sm btn-primary" @click="isShow=false">
                                            <i class="bx bx-caret-up-circle align-middle fs-5"></i>
                                        </button></th>

                                </thead>
                                <tbody class="table-group-divider">

                                    <tr>
                                        <td class="fw-bold">{{ $list->note }}</td>
                                        <td>
                                            @if ($list->Viabilities->count())
                                                @foreach ($list->Viabilities as $order)
                                                    <p class="p-0 m-0">{{ $order->Order->ordem }}
                                                        @if ($order->approved && !$order->rejected)
                                                            <i class="bx bxs-badge-check text-success"></i>
                                                        @endif
                                                        @if (!$order->approved && $order->rejected)
                                                            <i class="bx bxs-badge-check text-danger"></i>
                                                        @endif
                                                    </p>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td class="text-uppercase">{{ $list->rubrica }}</td>
                                        <td>
                                            @if ($list->Files->count())
                                                @foreach ($list->Files as $file)
                                                    <p class="p-0 m-0"><input
                                                            class="form-check-input border border-secondary"
                                                            type="checkbox" value="{{ $file->id }}"
                                                            wire:model.defer="files_selected">
                                                        <i class="bx bxs-file-{{ $file->ext }} text-danger"></i>
                                                        <span wire:click.prevent="downloadFile({{ $file->id }})"
                                                            style="cursor: pointer;">{{ $file->file_name }}</span>
                                                    </p>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td class="text-uppercase">
                                            {{ $cities->Where('rdMunicipio', $list->nexp)->first() ? $cities->Where('rdMunicipio', $list->nexp)->first()->regiao : '' }}
                                        </td>
                                        <td class="text-uppercase">
                                            {{ $cities->Where('rdMunicipio', $list->nexp)->first() ? $cities->Where('rdMunicipio', $list->nexp)->first()->centroHana : '' }}
                                        </td>
                                        <td class="text-uppercase">{{ $list->lexp }}</td>


                                    </tr>

                                </tbody>
                                @if ($list->Files->count())
                                    <tfoot>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>

                                            <span wire:click.prevent="downloadZip" style="cursor: pointer;"><i
                                                    class="bx bx-cloud-download text-primary fs-5 align-middle"></i>
                                                Baixar
                                            </span>

                                        </td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tfoot>
                                @endif
                            </table>
                        </div>

                        @if ($list->Viabilities->last()->Comments->count())
                            <div class="container-fluid">
                                <div class="row g-3">
                                    <div class="col-7">
                                        <div class="card">
                                            <h4 class="card-header edp-bg-seoweedgreen-100 text-white">Comentários
                                            </h4>
                                            <div class="card-body">

                                                <div class="clearfix">


                                                    @foreach ($list->Viabilities->last()->Comments->sortByDesc('created_at') as $comment)
                                                        @if ($comment->User->id !== auth()->User()->id)
                                                            {{-- <div class="d-flex justify-content-start">
                                                                <div
                                                                    class="border border-2 border-secondary rounded mb-3">

                                                                    <div class="text-bg-secondary p-2 text-justify">
                                                                        {{ $comment->message }}</div>
                                                                    <p class="text-start mt-2"><span
                                                                            class="fw-bold">Por:</span>
                                                                        {{ $comment->User->name }}
                                                                        <span class="fw-bold">as</span>
                                                                        {{ date('d/m/Y H:i:s') }}

                                                                    </p>
                                                                </div>
                                                            </div> --}}
                                                            <div class="border-start border-5 mb-3 border-primary">
                                                                <p
                                                                    class="text-start border-2 border-bottom px-2 border-primary">
                                                                    <span class="fw-bold">Por:</span>
                                                                    {{ $comment->User->name }}
                                                                    <span class="fw-bold">as</span>
                                                                    {{ date('d/m/Y H:i:s', strToTime($comment->created_at)) }}

                                                                </p>
                                                                <p class="text-start p-2">
                                                                    {{ $comment->message }}
                                                                </p>
                                                            </div>
                                                        @endif

                                                        @if ($comment->User->id === auth()->User()->id)
                                                            {{-- <div class="d-flex justify-content-end">
                                                                <div
                                                                    class="border border-2 border-primary rounded mb-3">

                                                                    <div class="text-bg-primary p-3 text-justify">
                                                                        {{ $comment->message }}</div>
                                                                    <p class="text-end"><span
                                                                            class="fw-bold">Por:</span>
                                                                        {{ $comment->User->name }}
                                                                        <span class="fw-bold">as</span>
                                                                        {{ date('d/m/Y H:i:s') }}

                                                                    </p>
                                                                </div>
                                                            </div> --}}

                                                            <div class="border-start border-5 mb-3 border-secondary">
                                                                <p
                                                                    class="text-start border-2 border-bottom border-secondary px-2">
                                                                    <span class="fw-bold">Por:</span>
                                                                    {{ $comment->User->name }}
                                                                    <span class="fw-bold">as</span>
                                                                    {{ date('d/m/Y H:i:s', strToTime($comment->created_at)) }}

                                                                </p>
                                                                <p class="text-start p-2">
                                                                    {{ $comment->message }}
                                                                </p>
                                                            </div>
                                                        @endif
                                                    @endforeach




                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-5">


                                        @if ($list->Viabilities->last()->status == 5 && $list->Viabilities->last()->replica)
                                            @livewire('partner.actions.approveaction', ['list' => $list], key('aproveactions-{{ $list->id }}'))
                                        @endif




                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="card-footer d-flex justify-content-end border-top border-2 border-secondary">
                            <div class="col-6">
                                <table class="table table-sm my-0">
                                    <thead style="font-size: 10px;">
                                        <th scope="col">Recebido Em</th>
                                        <th scope="col">Prazo Estimado</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Ação</th>
                                    </thead>
                                    <tbody>
                                        @php
                                            $status = null;
                                            $dueDate = $list->Viabilities->count()
                                                ? Carbon::parse($list->Viabilities->first()->sended_at)->addDays(7)
                                                : null;
                                            $today = Carbon::now();

                                            if ($dueDate) {
                                                $daysDifference = $dueDate ? $today->diffInDays($dueDate) : null;

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
                                        @endphp
                                        <tr>
                                            <td class="fw-bold">
                                                {{ Carbon::parse($list->Viabilities->first()->sended_at)->format('d/m/Y') }}
                                            </td>
                                            <td class="fw-bold text-danger">
                                                {{ Carbon::parse($list->Viabilities->first()->sended_at)->addDays(7)->format('d/m/Y') }}
                                            </td>
                                            <td>
                                                @if ($status)
                                                    <span
                                                        class="badge {{ $status['color'] }}">{{ $status['info'] }}</span>
                                                @endif
                                            </td>
                                            <td> <i class="bx bx-printer text-primary fs-4 me-2" role="group"
                                                    aria-label="Basic example" tabindex="0"
                                                    data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                    data-bs-placement="right"
                                                    data-bs-title="Imprimir Checklist (NÃO IMPLEMENTADO)"
                                                    data-bs-content="<p>Gera o PDF para impressão da ORDEM/NOTA.</p>"></i>

                                                @if (!$block || $block['command'])
                                                    <i class="bx bxs-badge-check text-success fs-4 me-2"
                                                        style="cursor: pointer;"
                                                        wire:click.prevent="openForms({{ $list->id }})"
                                                        role="group" aria-label="Basic example" tabindex="0"
                                                        data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                        data-bs-placement="right" data-bs-title="Encerrar Atividaede"
                                                        data-bs-content="<p>Entrega os informes da Obra.</p>"></i>
                                                @endif
                                            </td>
                                        </tr>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- END CARD EXTEDED --}}
                {{-- End Line Item --}}
            @endforeach

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
</div>
