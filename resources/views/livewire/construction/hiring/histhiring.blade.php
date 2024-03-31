<div>



    <div class="card">
        <div class="card-header  edp-bg-sprucegreen-100 edp-text-verde-dark">
            <h4 class="fs-4">OBRAS CONTRATADAS</h4>
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
                                                <x-hiring.status :badge="$list->Viabilities->count()
                                                    ? $list->Viabilities->first()->status
                                                    : 0" />
                                            </td>
                                            <td class="text-truncate"><i @click="isVisible = !isVisible"
                                                    class="bx bxs-plus-square text-danger fs-4"
                                                    style="cursor: pointer;"></i></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div x-show="isVisible" style="display: none;">
                                @if ($list->Viabilities->count() && isset($list->Viabilities->first()->Form))
                                    <div class="row">
                                        <div class="col-8">
                                            <p class="fw-bold fs-6 my-0 py-0">Motivo</p>
                                            <p class="mb-2 mb-0 py-0 text-justify p-3 border border-rounded">
                                                @if ($list->Viabilities->count())
                                                    {{ isset($list->Viabilities->first()->Form->reason) ? $list->Viabilities->first()->Form->reason : '---' }}
                                                @endif
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
                                                    {{-- <div class="card mb-1">
                                                        <h5
                                                            class="card-header edp-bg-sprucegreen-100 edp-text-verde-dark"">
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
                                                    @endforeach --}}

                                                    <div class="card">
                                                        <h4 class="card-header edp-bg-seoweedgreen-100 text-white">
                                                            Comentários</h4>
                                                        <div class="card-body">
                                                            <div class="clearfix">


                                                                @foreach ($list->Viabilities->last()->Comments as $comment)
                                                                    <div class="d-flex justify-content-start">
                                                                        <div
                                                                            class="border border-2 border-secondary rounded mb-3">

                                                                            <div
                                                                                class="text-bg-secondary p-2 text-justify">
                                                                                {{ $comment->message }}</div>
                                                                            <p class="text-start mt-2 mb-1 px-2"><span
                                                                                    class="fw-bold">Por:</span>
                                                                                {{ $comment->User->name }}
                                                                                <span class="fw-bold">as</span>
                                                                                {{ date('d/m/Y H:i:s') }}

                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                @endforeach




                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <p class="fw-bold fs-6 my-0 py-0">Arquivos:</p>
                                            @if ($list->Files->count())
                                                @foreach ($list->Files as $file)
                                                    <p class="mb-2 mb-0 py-0" style="cursor: pointer;">
                                                        <i class="bx bxs-file-{{ $file->ext }} text-danger"></i>
                                                        <span>{{ $file->file_name }}</span>
                                                    </p>
                                                @endforeach
                                            @endif

                                            @if ($list->Viabilities->count() && !$list->Viabilities->first()->approved)
                                                @livewire('construction.hiring.actions.hiring', ['list' => $list], key('returne-{{ $list->id }}'))
                                            @else
                                            @endif

                                        </div>
                                    </div>
                                @else
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="text-center">
                                                SEM INFORMAÇÕES
                                            </h4>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

    </div>




</div>
