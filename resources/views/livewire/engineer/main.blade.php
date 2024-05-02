@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
@endphp

@push('css')
    <style>
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

    <div class="row justify-content-between">
        <div class="col-12">

            <div class="card">
                <div class="card-header  edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h4 class="fs-4">Aguardando Avaliação Viabilidade</h4>
                </div>
                <div class="card-body edp-bg-gray">
                    @if ($lists->count())
                        @foreach ($lists as $list)
                            <div class="card my-2" x-data="{ isVisible: false }" @click.away="isVisible = false"
                                wire:key='list-{{ $list->id }}'>
                                <div class="card-body my-0 py-0">
                                    <div class="table-responsive">
                                        <table class="table table-condensed table-sm">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Note/Ov</th>
                                                    <th scope="col">Order</th>
                                                    <th scope="col">Rubrica</th>
                                                    <th scope="col">Municipio</th>
                                                    <th scope="col">Empreiteira</th>
                                                    <th scope="col">Data Envio</th>
                                                    <th scope="col">Data Viabilidade</th>
                                                    <th scope="col">Resultado</th>
                                                    <th scope="col">Ação</th>
                                                    <th scope="col"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>{{ $list->note }}</td>
                                                    <td>
                                                        @if ($list->Orders->count())
                                                            @foreach ($list->Orders as $order)
                                                                <p class="py-0 my-0">{{ $order->ordem }}</p>
                                                            @endforeach
                                                        @endif
                                                    </td>
                                                    <td>{{ $list->rubrica }}</td>
                                                    <td>{{ $list->lexp }}</td>
                                                    <td>{{ $list->Viabilities->count() ? $list->Viabilities->first()->Company->name : '' }}
                                                    </td>
                                                    <td>{{ $list->Viabilities->count() ? date('d/m/Y H:i:s', strToTime($list->Viabilities->first()->sended_at)) : '' }}
                                                    </td>
                                                    <td>{{ $list->Viabilities->count() ? date('d/m/Y H:i:s', strToTime($list->Viabilities->first()->returned_at)) : '' }}
                                                    </td>

                                                    <td><span
                                                            class="badge {{ Viabilitiesstatus::status($list->Viabilities->first()->status)->colorbg }}">{{ Viabilitiesstatus::status($list->Viabilities->first()->status)->status }}</span>
                                                    </td>
                                                    <td>
                                                        @if ($list->Viabilities->last()->status == 4)
                                                            <span class="badge text-bg-danger blink">Requer Ação</span>
                                                        @endif
                                                    </td>
                                                    <td><i @click="isVisible = !isVisible"
                                                            class="bx bxs-plus-square text-danger fs-4"
                                                            style="cursor: pointer;"></i></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div x-show="isVisible" style="display: none;">
                                        <div class="row">
                                            <div class="col-7">
                                                <p class="fw-bold fs-6 my-0 py-0">Motivo</p>
                                                <p class="mb-2 mb-0 py-0 text-justify p-3"
                                                    style="
                                                        border-left: 5px solid #143f47;
                                                        border-bottom: 1px solid #143f47;
                                                        ">
                                                    {{ $list->Viabilities->count() ? $list->Viabilities->first()->Form->reason : '---' }}
                                                </p>
                                                <p class="fw-bold fs-6 my-0 py-0">Percentual de Modificação:</p>
                                                <p class="mb-2 mb-0 py-0 text-justify p-3 fw-bold
                                                @if ($list->Viabilities->count() && $list->Viabilities->first()->Form->changes > 1) text-white @endif"
                                                    style="
                                                    border-left: 5px solid #143f47;
                                                    border-bottom: 1px solid #143f47;
                                                    background: linear-gradient(90deg, rgba(231,12,38,1) 0%, rgba(9,9,121,0) {{ $list->Viabilities->count() ? $list->Viabilities->first()->Form->changes * 10 . '%' : '' }});
                                                    ">

                                                    {{ $list->Viabilities->count() ? $list->Viabilities->first()->Form->changes * 10 . '%' : '' }}
                                                </p>

                                                <p class="fw-bold fs-6 my-0 py-0">Resultado Viabilidade:</p>
                                                <p class="mb-2 mb-0 py-0 text-justify p-3"
                                                    style="
                                                border-left: 5px solid #143f47;
                                                border-bottom: 1px solid #143f47;
                                                ">
                                                    {{ $list->Viabilities->count() ? $list->Viabilities->first()->Form->description : '' }}
                                                </p>
                                                <p class="fw-bold fs-6 my-0 py-0">Responsável pelo Informe:</p>
                                                <p class="mb-2 mb-0 py-0 text-justify p-3"
                                                    style="
                                                border-left: 5px solid #143f47;
                                                border-bottom: 1px solid #143f47;
                                                ">
                                                    {{ $list->Viabilities->count() ? $list->Viabilities->first()->Form->responsible : '' }}
                                                </p>

                                                @if ($list->Viabilities->last()->Comments->count())
                                                    <div class="card">
                                                        <h4 class="card-header edp-bg-seoweedgreen-100 text-white">
                                                            Comentários
                                                        </h4>
                                                        <div class="card-body">

                                                            <div class="clearfix">


                                                                @foreach ($list->Viabilities->last()->Comments as $comment)
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
                                                                        <div
                                                                            class="border-start border-5 mb-3 border-primary">
                                                                            <p
                                                                                class="text-start border-2 border-bottom px-2 border-primary">
                                                                                <span class="fw-bold">Por:</span>
                                                                                {{ $comment->User->name }}
                                                                                <span class="fw-bold">as</span>
                                                                                {{ date('d/m/Y H:i:s') }}

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

                                                                        <div
                                                                            class="border-start border-5 mb-3 border-secondary">
                                                                            <p
                                                                                class="text-start border-2 border-bottom border-secondary px-2">
                                                                                <span class="fw-bold">Por:</span>
                                                                                {{ $comment->User->name }}
                                                                                <span class="fw-bold">as</span>
                                                                                {{ date('d/m/Y H:i:s') }}

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
                                                @endif
                                            </div>
                                            <div class="col-5">
                                                <div class="mb-3">
                                                    <p class="fw-bold fs-6 my-0 py-0">Arquivos:</p>
                                                    @if ($list->Files->count())
                                                        <div class="pe-3 py-0 m-0"
                                                            style="
                                                        border-left: 5px solid #143f47;
                                                        border-bottom: 1px solid #143f47;
                                                        ">
                                                            @foreach ($list->Files as $file)
                                                                <p class="mb-2 mb-0 py-0" style="cursor: pointer;">
                                                                    <i
                                                                        class="bx bxs-file-{{ $file->ext }} text-danger"></i>
                                                                    <span>{{ $file->file_name }}</span>
                                                                </p>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="mb-3">
                                                    @if ($list->Viabilities->count())
                                                        <p class="fw-bold fs-6 my-0 py-0">Retorno Interno:</p>
                                                        <div class="border-2 rounded border-secondary shadow">
                                                            <table class="table table-condensed table-stripped">
                                                                <thead>
                                                                    <th>Data</th>
                                                                    <th>Serviço</th>
                                                                    <th>Completado</th>
                                                                    <th>Produção</th>
                                                                    <th>Status</th>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($list->Viabilities as $viab)
                                                                        @if ($viab->Reclaims->count())
                                                                            @foreach ($viab->Reclaims as $reclaim)
                                                                                {{-- @dump($reclaim->Note) --}}
                                                                                <tr>
                                                                                    <td>{{ date('d/m/Y H:i:s', strToTime($reclaim->created_at)) }}
                                                                                    </td>
                                                                                    <td>{{ $reclaim->Service->service }}
                                                                                    </td>
                                                                                    <td>{{ $reclaim->completed_at ? date('d/m/Y H:i:s', strToTime($reclaim->completed_at)) : '---' }}
                                                                                    </td>
                                                                                    <td>
                                                                                        @if (isset($reclaim->Production) && $reclaim->Production->count())
                                                                                            <span
                                                                                                class="badge {{ Notestatus::status($reclaim->Production->status)->colorbg }}">{{ Notestatus::status($reclaim->Production->status)->status }}</span>
                                                                                        @else
                                                                                            <span
                                                                                                class="badge text-bg-secondary">Não
                                                                                                Despachado</span>
                                                                                        @endif
                                                                                    </td>
                                                                                    <td>
                                                                                        @if ($reclaim->completed)
                                                                                            <span
                                                                                                class="badge text-bg-success">Finalizado</span>
                                                                                        @else
                                                                                            <span
                                                                                                class="badge text-bg-primary">Criado</span>
                                                                                        @endif
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                        @endif
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @endif
                                                </div>

                                                @php
                                                    $block = false;
                                                    $blkResponse = false;
                                                    $blkReturn = false;

                                                    if (
                                                        $list->Viabilities->count() &&
                                                        $list->Viabilities->where('completed', false)->last()->status ==
                                                            4
                                                    ) {
                                                        $block = false;
                                                    }

                                                    if (
                                                        $list->Viabilities->count() &&
                                                        $list->Viabilities
                                                            ->where('completed', false)
                                                            ->last()
                                                            ->Reclaims->count() &&
                                                        $list->Viabilities
                                                            ->where('completed', false)
                                                            ->last()
                                                            ->Reclaims->where('completed', false)
                                                            ->count()
                                                    ) {
                                                        $block = true;
                                                    }

                                                    if (
                                                        $list->Viabilities->count() &&
                                                        $list->Viabilities->where('completed', false)->last()->treplica
                                                    ) {
                                                        $blkResponse = true;
                                                    }

                                                    if ($list->Viabilities->count()) {
                                                        foreach ($list->Viabilities as $viab) {
                                                            if (isset($viab->Reclaims) && $viab->Reclaims->count()) {
                                                                $blkReturn = true;
                                                            }
                                                        }
                                                    }

                                                @endphp

                                                @if (!$block)
                                                    @livewire('engineer.actions.approveaction', ['list' => $list, 'blkResponse' => $blkResponse, 'blkReturn' => $blkReturn], key('aproveactions-{{ $list->id }}'))
                                                @endif

                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

            </div>


        </div>
        <div class="col-4">

        </div>
    </div>
</div>
