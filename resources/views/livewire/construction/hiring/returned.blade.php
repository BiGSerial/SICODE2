@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
@endphp
<div>
    <x-show-loading />
    <div class="card">
        <div class="card-header  edp-bg-sprucegreen-100 edp-text-verde-dark">
            <h4 class="fs-4">Retorno de Viabilidade</h4>
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
                                            <th scope="col" style="max-width: 10%">Note/Ov</th>
                                            <th scope="col" style="max-width: 10%">Order</th>
                                            <th scope="col" style="max-width: 10%">Rubrica</th>
                                            <th scope="col" style="max-width: 15%">Municipio</th>
                                            <th scope="col" style="max-width: 10%">Empreiteira</th>
                                            <th scope="col" style="max-width: 10%">Data Envio</th>
                                            <th scope="col" style="max-width: 10%">Data Viabilidade</th>
                                            <th scope="col" style="max-width: 10%">Resultado</th>
                                            <th scope="col" style="max-width: 10%">Status</th>
                                            <th scope="col" style="max-width: 5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-truncate">{{ $list->note }}</td>
                                            <td class="text-truncate">
                                                @if ($list->Orders->count())
                                                    @foreach ($list->Orders as $order)
                                                        <p class="py-0 my-0">{{ $order->ordem }}</p>
                                                    @endforeach
                                                @endif
                                            </td>
                                            <td class="text-truncate">{{ $list->rubrica }}</td>
                                            <td class="text-truncate">{{ $list->lexp }}</td>
                                            <td class="text-truncate">
                                                {{ $list->Viabilities->count() ? $list->Viabilities->first()->Company->name : '' }}
                                            </td>
                                            <td class="text-truncate">
                                                {{ $list->Viabilities->count() ? date('d/m/Y H:i:s', strToTime($list->Viabilities->first()->sended_at)) : '' }}
                                            </td>
                                            <td class="text-truncate">
                                                {{ $list->Viabilities->count() ? date('d/m/Y H:i:s', strToTime($list->Viabilities->first()->returned_at)) : '' }}
                                            </td>
                                            <td class="text-truncate">
                                                @if ($list->Viabilities->count() && $list->Viabilities->first()->approved)
                                                    <span class="badge text-bg-primary">A Contratar</span>
                                                @else
                                                    <span class="badge text-bg-danger">Procedente</span>
                                                @endif
                                            </td>
                                            <td class="text-truncate">

                                                @php
                                                    if (
                                                        $list->Viabilities->count() &&
                                                        $list->Viabilities->first()->status
                                                    ) {
                                                        $status = $list->Viabilities->first()->status;
                                                    } else {
                                                        $status = 0;
                                                    }
                                                @endphp
                                                <x-hiring.status :badge="$status" />
                                            </td>
                                            <td class="text-truncate"><i @click="isVisible = !isVisible"
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
                                        <p class="mb-2 mb-0 py-0 text-justify p-3 border border-rounded">
                                            {{ $list->Viabilities->count() ? $list->Viabilities->first()->Form->reason : '---' }}
                                        </p>
                                        <p class="fw-bold fs-6 my-0 py-0">Percentual de Modificação:</p>
                                        <p class="mb-2 mb-0 py-0 text-justify p-3 border border-rounded fw-bold
                                                @if ($list->Viabilities->count() && $list->Viabilities->first()->Form->changes > 1) text-white @endif"
                                            style="
                                                    
                                                    background: linear-gradient(90deg, rgba(231,12,38,1) 0%, rgba(9,9,121,0) {{ $list->Viabilities->count() ? $list->Viabilities->first()->Form->changes * 10 . '%' : '' }});
                                                    ">

                                            {{ $list->Viabilities->count() ? $list->Viabilities->first()->Form->changes * 10 . '%' : '' }}
                                        </p>

                                        <p class="fw-bold fs-6 my-0 py-0">Resultado Viabilidade:</p>
                                        <p class="mb-2 mb-0 py-0 text-justify p-3 border border-rounded">
                                            {{ $list->Viabilities->count() ? $list->Viabilities->first()->Form->description : '' }}
                                        </p>
                                        <p class="fw-bold fs-6 my-0 py-0">Responsável pelo Informe:</p>
                                        <p class="mb-2 mb-0 py-0 text-justify p-3 border border-rounded">
                                            {{ $list->Viabilities->count() ? $list->Viabilities->first()->Form->responsible : '' }}
                                        </p>

                                        <div class="my-3">
                                            @if ($list->Viabilities->count() && $list->Viabilities->first()->Comments->count())
                                                <div class="card mb-1">
                                                    <h5 class="card-header edp-bg-sprucegreen-100 edp-text-verde-dark"">
                                                        FEEDBACK</h5>
                                                </div>
                                                @foreach ($list->Viabilities->first()->Comments as $comment)
                                                    <div class="card my-1">
                                                        <div class="card-body">
                                                            <p>
                                                                {{ $comment->message }}
                                                            </p>
                                                        </div>
                                                        <div class="card-footer">
                                                            <span class="fw-bold fs-5 align-middle"><i
                                                                    class="bx bxs-user-voice text-danger"></i></span>
                                                            <span class="">{{ $comment->User->name }}</span>
                                                            <span class="fw-bold fs-5 align-middle"><i
                                                                    class="bx bx-time-five"></i></span>
                                                            <span
                                                                class="">{{ Carbon\Carbon::parse($comment->created_at)->format('d/m/Y H:i:s') }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-5">

                                        @if ($list->Files->count())
                                            <p class="fw-bold fs-6 my-0 py-0">Arquivos:</p>
                                            @foreach ($list->Files as $file)
                                                <p class="mb-2 mb-0 py-0" style="cursor: pointer;"
                                                    wire:click.prevent="downloadFile({{ $file->id }})"
                                                    wire:key='files-{{ $file->id }}'>
                                                    <i class="bx bxs-file-{{ $file->ext }} text-danger"></i>
                                                    <span>{{ $file->file_name }}</span>
                                                </p>
                                            @endforeach
                                        @endif

                                        @if ($list->Viabilities->count())
                                            <p class="fw-bold fs-6 my-0 py-0">Retorno RI:</p>
                                            <table class="table table-condensed table-stripped">
                                                <thead>
                                                    <th>Data</th>
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
                                                                    <td>{{ $reclaim->completed_at ? date('d/m/Y H:i:s', strToTime($reclaim->completed_at)) : '---' }}
                                                                    </td>
                                                                    <td>
                                                                        @if (isset($reclaim->Production) && $reclaim->Production->count())
                                                                            <span
                                                                                class="badge {{ Notestatus::status($reclaim->Production->status)->colorbg }}">{{ Notestatus::status($reclaim->Production->status)->status }}</span>
                                                                        @else
                                                                            <span class="badge text-bg-secondary">Não
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
                                        @endif


                                        @if ($list->Viabilities->count() && !$list->Viabilities->first()->approved)
                                            @livewire('construction.hiring.actions.return-d5', ['list' => $list], key('returnD5-{{ $list->id }}'))
                                        @else
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
