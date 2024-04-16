@php
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
    </style>
@endpush

<div>
    <x-show-loading />
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


    <div class="card mb-2 edp-bg-gray">
        <h4 class="card-header  edp-bg-seoweedgreen-100 text-white">VIABILIDADE A EXECUTAR</h4>


        @if (!$lists->count())
            <div class="text-center my-5 py-3">
                <h3>NENHUMA ATIVIDADE ENCONTRADA</h3>
            </div>
        @else
           

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

                    if ($list->Viabilities->count()) {
                        $count = 0;

                        foreach ($list->Viabilities as $order) {
                            if ($order->approved) {
                                $count++;

                                $block = [
                                    'color' => 'success',
                                    'command' => true,
                                ];
                            }

                            if ($order->rejected) {
                                $count++;
                                $block = [
                                    'color' => 'danger',
                                    'command' => true,
                                ];
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



                    @for ($i = 0; $i < 10; $i++)
                        <div class="col" x-data="{ isShow: false }" style="overflow: hidden;"
                            wire:key="{{ $list->id }}">
                            <div class="align-items-center" x-show="!isShow"
                                style="animation-delay: {{ $index * 0.03 }}s">

                                <table
                                    class="table table-sm my-0 
                                @if ($block && $block) table-{{ $block['color'] }} @endif
                                ">
                                    <thead>
                                        <th scope="col" class="col-2">Nota/Ov</th>
                                        <th scope="col" class="col-2">Ordem</th>
                                        <th scope="col" class="col-1">Rubrica</th>
                                        <th scope="col" class="col-1">Regiao</th>
                                        <th scope="col" class="col-2">Municipio</th>
                                        <th scope="col" class="col-1">Recebido Em</th>
                                        <th scope="col" class="col-1">Prazo Estimado</th>
                                        <th scope="col" class="col-1">Status</th>
                                        <th scope="col" class="d-flex justify-content-end">
                                            <button class=" btn btn-sm btn-primary" @click="isShow=true">
                                                <i class="bx bx-caret-down-circle align-middle fs-5"></i>
                                            </button>
                                        </th>
                                    </thead>
                                    <tbody class="table-group-divider">
                                        <tr>
                                            <td class="fw-bold">{{ $list->note }}</td>
                                            <td class="">
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
                                            <td class="text-uppercase">{{ $list->rubrica }}</td>
                                            <td class="text-uppercase">
                                                {{ $cities->Where('rdMunicipio', $list->nexp)->first() ? $cities->Where('rdMunicipio', $list->nexp)->first()->regiao : '' }}
                                            </td>
                                            <td class="text-uppercase">{{ $list->lexp }}</td>
                                            <td class="fw-bold">
                                                {{ Carbon::parse($list->Viabilities->last()->sended_at)->format('d/m/Y') }}
                                            </td>
                                            <td class="fw-bold text-danger">
                                                {{ Carbon::parse($list->Viabilities->last()->sended_at)->addDays(7)->format('d/m/Y') }}
                                            </td>
                                            <td>
                                                @if ($status)
                                                    <span class="badge {{ $status['color'] }}">{{ $status['info'] }}
                                                    </span>
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
                    @endfor




                    {{--    Card Expanded --}}
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
                                    <div class="col-8">
                                        <div class="card">
                                            <h4 class="card-header edp-bg-seoweedgreen-100 text-white">Comentários</h4>
                                            <div class="card-body">
                                                <div class="clearfix">


                                                    @foreach ($list->Viabilities->last()->Comments as $comment)
                                                        @if ($comment->User->id !== auth()->User()->id)
                                                            <div class="d-flex justify-content-start">
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
                                                            </div>
                                                        @endif
                                                        @if ($comment->User->id === auth()->User()->id)
                                                            <div class="d-flex justify-content-end">
                                                                <div
                                                                    class="border border-2 border-primary rounded mb-3">

                                                                    <div class="text-bg-primary p-2 text-justify">
                                                                        {{ $comment->message }}</div>
                                                                    <p class="text-end mt-2"><span
                                                                            class="fw-bold">Por:</span>
                                                                        {{ $comment->User->name }}
                                                                        <span class="fw-bold">as</span>
                                                                        {{ date('d/m/Y H:i:s') }}

                                                                    </p>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endforeach




                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="exampleFormControlTextarea1"
                                                        class="form-label">Inserir
                                                        Comentário:</label>
                                                    <textarea class="form-control border border-secondary" id="exampleFormControlTextarea1" rows="3"></textarea>
                                                </div>
                                                <button class="btn btn-primary">Enviar</button>
                                            </div>
                                        </div>
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
                                                    <i class="bx bx-play-circle text-danger fs-4 me-2" role="group"
                                                        aria-label="Basic example" tabindex="0"
                                                        data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                        data-bs-placement="right"
                                                        data-bs-title="Iniciar Atividadeeeeeee"
                                                        data-bs-content="<p>Sinaliza inicio desta atividade para acompanhamento da gestão.</p>"></i>
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
            @endforeach

            <div class="row">
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


        @endif

    </div>





</div>
