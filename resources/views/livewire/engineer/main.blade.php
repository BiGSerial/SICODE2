<div>

    <div class="row justify-content-between">
        <div class="col-8">

            <div class="card">
                <div class="card-header  edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h4 class="fs-4">Aguardando Aprovação Viabilidade</h4>
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
                                                    <td>Rejeitado</td>
                                                    <td><i @click="isVisible = !isVisible"
                                                            class="bx bxs-plus-square text-danger fs-4"
                                                            style="cursor: pointer;"></i></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div x-show="isVisible" style="display: none;">
                                        <div class="row">
                                            <div class="col-8">
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


                                                @livewire('engineer.actions.approveaction', ['list' => $list], key('aproveactions-{{ $list->id }}'))
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
