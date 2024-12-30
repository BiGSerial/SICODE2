@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />
    <div class="card edp-bg-gray">
        <div class="card-header  edp-bg-sprucegreen-100 edp-text-verde-dark">
            <h4 class="fs-4">OBRAS AGUARDANDO VIABILIDADE</h4>
        </div>
        <div class="card-body py-0 mt-3">
            <div class="mb-3 d-flex flex-wrap align-items-center justify-content-end">
                <!-- Grupo de Entrada de Texto -->
                <div class="input-group input-group-sm my-2 flex-nowrap col">
                    <input type="text" class="form-control" placeholder="Buscar Multi-Notas"
                        aria-label="Buscar Multi-Notas" aria-describedby="button-addon2" wire:model='search'>
                    <button class="btn btn-outline-secondary" wire:click.prevent="openMultiNotas" type="button"
                        id="button-addon2" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Buscar Multi-notas">
                        <i class="ri-checkbox-multiple-blank-line"></i>
                    </button>
                </div>

                <!-- Radios Inline -->
                <div class="form-check form-check-inline my-2 ms-2">
                    <input class="form-check-input" type="radio" name="typeNote" wire:model="typeNote" value="1">
                    <label class="form-check-label" for="inlineRadio1">Nota</label>
                </div>
                <div class="form-check form-check-inline my-2">
                    <input class="form-check-input" type="radio" name="typeNote" wire:model="typeNote" value="2">
                    <label class="form-check-label" for="inlineRadio2">OV</label>
                </div>
                <div class="form-check form-check-inline my-2">
                    <input class="form-check-input" type="radio" name="typeNote" wire:model="typeNote" value="">
                    <label class="form-check-label" for="inlineRadio3">Ambos</label>
                </div>

                <!-- Select Centro Trabalho -->
                <div class="my-2 col-12 col-md-auto ms-2">

                    <select name="" id="" class="form-select form-select-sm" wire:model="cjobes">
                        <option value="" selected>Centro Trabalho</option>


                        @if ($centerJobers)
                            @foreach ($centerJobers as $cjob)
                                <option value="{{ $cjob->cenTrab }}">{{ $cjob->cenTrab }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Select Ação -->
                <div class="my-2 col-12 col-md-auto ms-2">
                    <select name="" id="" class="form-select form-select-sm" wire:model="action">
                        <option value="" selected>Selecione uma Ação</option>
                        <option value="1">Contratar</option>
                    </select>
                </div>

                <!-- Botão Executar -->
                <div class="my-2 col-md-auto ms-2">
                    <button class="btn btn-sm btn-primary" wire:click.prevent='go_att_mass' @disabled(!$action)
                        wire:target="go_att_mass" wire:loading.attr="disabled" data-bs-toggle="tooltip"
                        data-bs-placement="top" data-bs-title="Executar">
                        <i class="bx bx-send fs-4 m-0 align-middle" wire:target="go_att_mass" wire:loading.remove></i>
                        <div class="spinner-border spinner-border-sm" role="status" wire:target="go_att_mass"
                            wire:loading>
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </button>
                </div>

                <!-- Botão Copiar Selecionados -->
                <div class="my-2 col-md-auto ms-2">
                    <button class="btn btn-sm btn-primary" wire:click.prevent='copyClipboard'
                        wire:target="copyClipboard" wire:loading.attr="disabled" data-bs-toggle="tooltip"
                        data-bs-placement="top" data-bs-title="Copiar Selecionados para área de Transferência">
                        <i class="bx bxs-copy-alt fs-4 m-0 align-middle" wire:target="copyClipboard"
                            wire:loading.remove></i>
                        <div class="spinner-border spinner-border-sm" role="status" wire:target="copyClipboard"
                            wire:loading>
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </button>
                </div>
            </div>


        </div>
        <div class="row mx-3">
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
        <table class="table table-sm table-condensed table-striped-columns table-hover">
            <thead>
                <th class="text-center">
                    <input class="form-check-input border border-secondary" type="checkbox" wire:model.defer="selectAll"
                        wire:click="setSelectAll()" @checked($this->checkAllSelect($lists))>
                </th>
                <th scope="col" class="text-center">Nota</th>
                <th scope="col" class="text-center">Ordens</th>
                <th scope="col" class="text-center">Files</th>
                <th scope="col" class="text-center">Rubrica</th>
                <th scope="col" class="text-center">Municipio</th>
                <th scope="col" class="text-center">CentroTrab</th>
                <th scope="col" class="text-center">Empreiteira</th>
                <th scope="col" class="text-center">Responsável</th>
                <th scope="col" class="text-center">Data Envio</th>
                <th scope="col" class="text-center">Data Esperado</th>
                <th scope="col" class="text-center">Data Retorno</th>
                <th scope="col" class="text-center">Em Atividade</th>
                <th scope="col" class="text-center">Status</th>
                <th scope="col" class="text-center"></th>
            </thead>
            <tbody class="table-group-divider">
                @if ($lists)
                    @foreach ($lists as $list)
                        @php
                            $dueDate = Carbon::parse($list->sended_at)->addDays($list->getDays() + 7);
                        @endphp
                        <tr wire:key="row-{{ $list->id }}" class='align-middle'>
                            <td class="text-center aling-middle">

                                @if ($list->approved && !$list->rejected)
                                    <input class="form-check-input border border-secondary" type="checkbox"
                                        wire:model.defer="selected" value="{{ $list->id }}">
                                @endif


                            </td>
                            <td class="text-center aling-middle fw-bold">{{ $list->Note->note }}</td>
                            <td class="text-center aling-middle fw-bold">
                                @if ($list->Orders->count())
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
                            <td class="text-center align-middle">
                                {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                {{-- <x-files.select-download-list :files='$list->Note->Files' /> --}}
                                <x-select-download-project-only :files='$list->Note->Files' :filtro="'PROJETO'" />

                            </td>
                            <td class="text-center aling-middle">{{ $list->Note->rubrica }}</td>
                            <td class="text-center aling-middle">{{ $list->Note->lexp }}</td>
                            <td class="text-center aling-middle">
                                @if (isset($list->Note->Orders->first()->Operations->first()->cenTrab))
                                    {{ $list->Note->Orders->first()->Operations->first()->cenTrab }}
                                @else
                                    ---
                                @endif
                            </td>
                            <td class="text-center aling-middle">
                                {{ $list->Company ? $list->Company->name : '---' }}
                            </td>
                            <td class="text-center aling-middle">
                                {{ $list->Engineer ? $list->Engineer->name : '---' }}
                            </td>
                            <td class="text-center aling-middle">
                                {{ Carbon::parse($list->sended_at)->format('d/m/Y H:i') }}
                            </td>
                            <td class="text-center aling-middle">
                                {{ Carbon::parse($dueDate)->format('d/m/Y H:i') }}
                            </td>
                            <td class="text-center aling-middle">
                                @if ($list->returned_at)
                                    {{ Carbon::parse($list->returned_at)->format('d/m/Y H:i') }}
                                @endif
                            </td>
                            <td class="text-center aling-middle">
                                {{ Carbon::parse($list->sended_at)->diffForHumans(Carbon::now(), ['locale' => 'pt_br', 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                            </td>

                            <td class="text-center aling-middle">
                                <span
                                    class="badge text-wrap aling-middle {{ Viabilitiesstatus::status($list->status)->colorbg }}">{{ Viabilitiesstatus::status($list->status)->status }}</span>
                            </td>
                            {{-- <td class="text-center aling-middle">
                                @if ($list->Reclaim && $list->Reclaim->completed)
                                    <i class="ri-arrow-go-back-fill text-primary" tabindex="0"
                                        data-bs-toggle="popover" data-bs-trigger="hover focus"
                                        data-bs-placement="top" data-bs-title="DEVOLUÇÃO"
                                        data-bs-content="Devolver para o Responsável a Nota/Ov"
                                        style="cursor: pointer;"
                                        wire:click.prevent="go_giveBack({{ $list->id }})"></i>
                                @endif
                                <i class="ri-delete-bin-2-line text-danger" tabindex="0" data-bs-toggle="popover"
                                    data-bs-trigger="hover focus" data-bs-placement="top" data-bs-title="EXCLUIR"
                                    data-bs-content="Excluir registro de Espera" style="cursor: pointer;"
                                    wire:click.prevent="cancelWaiting({{ $list->id }})"></i>
                            </td> --}}
                            <td class="align-middle text-center">
                                <i class="ri-pencil-fill text-primary fs-5" style="cursor: pointer;"
                                    wire:click.prevent="$emitTo('construction.hiring.actions.edit', 'edit_hiring', {{ $list->id }})"></i>
                            </td>
                        </tr>
                    @endforeach
                @endif

            </tbody>
        </table>
        <div class="row mx-3">
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
    </div>

    {{-- MODALS --}}
    <div wire:ignore.self class="modal fade" id="modal_multi_notas" tabindex="-1"
        aria-labelledby="exampleModalLabel" aria-hidden="true">


        <div class="modal-dialog">

            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    Buscar Multi-Notas
                </div>
                <div>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" cols="50" rows="10"
                        wire:model.defer="advanceSearch"></textarea>
                </div>
                <div class="modal-footer">

                    <button type="button" class="btn btn-primary" wire:click="buscarMulti">OK</button>
                </div>
            </div>

        </div>

    </div>






    {{-- Fim Modals --}}
    {{--
    @livewire('construction.hiring.actions.waitinghiring', key('waitinghiring')); --}}
    @livewire('construction.hiring.actions.edit', key('hiring-edit'))


    <!-- Exibir os dados do clipboard com formatação para Excel -->
    <textarea id="clipboard-data" style="display: none;">
    @if (count($clipboardData))
@foreach ($clipboardData as $row)
{{ implode("\t", $row) }}
@endforeach
@else
SEM DADOS
@endif
</textarea>


</div>
