@php
    use App\Helpers\FileIcon;
    use App\Custom\Notestatus;
    use App\Helpers\SelectOptions;
    use Carbon\Carbon;
@endphp



<div>
    <style>
        .scrollable-div {
            overflow-y: auto;
            scrollbar-width: thin;
            /* Firefox */
            scrollbar-color: #888 #f1f1f1;
            /* Firefox */
        }

        .scrollable-div::-webkit-scrollbar {
            width: 8px;
        }

        .scrollable-div::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .scrollable-div::-webkit-scrollbar-thumb {
            background-color: #888;
            border-radius: 10px;
            border: 2px solid #f1f1f1;
        }

        .scrollable-div::-webkit-scrollbar-thumb:hover {
            background-color: #555;
        }
    </style>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="modal_protocols" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h4 class="my-auto fw-bold">
                        PROTOCOLOS
                    </h4>
                </div>
                <div class="modal-body">
                    @if ($note)
                        <div class="row">
                            <div class="col-6">
                                <div class="card">
                                    <h5 class="card-header my-0 py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                        Dados da Nota/OV
                                    </h5>
                                    <table class="table table-sm table-condensed table-striped-columns">
                                        <tbody>
                                            <tr>
                                                <td class="text-end fw-bold col-3">Note/Ov</td>
                                                <td class="text-start">{{ $note->note }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-end fw-bold col-3">Rubrica</td>
                                                <td class="text-start">{{ $note->rubrica }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-end fw-bold col-3">Municipio</td>
                                                <td class="text-start">{{ $note->lexp }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-end fw-bold col-3">Descrição</td>
                                                <td class="text-start">{{ $note->material }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-end fw-bold col-3">Status</td>
                                                <td class="text-start">{{ $note->nstats }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-end fw-bold col-3">Centro de Trabalho</td>
                                                <td class="text-start">{{ $note->centerjob }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="card">
                                    <h5 class="card-header my-0 py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                        ENTIDADE PROTOCOLAR
                                    </h5>
                                    <table class="table table-sm table-condensed table-striped-columns">
                                        <tbody>
                                            <tr>
                                                <td class="text-end fw-bold col-3">Tipo</td>
                                                <td class="text-start">
                                                    @if (!$note->External)
                                                        <select class="form-select form-select-sm"
                                                            aria-label="Small select example" style="max-width: 150px"
                                                            wire:model="selType">
                                                            <option selected>Selecione</option>
                                                            @foreach (SelectOptions::getUniqueExternalTypes() as $type)
                                                                <option value="{{ $type }}">{{ $type }}
                                                                </option>
                                                            @endforeach

                                                        </select>
                                                    @else
                                                        {{ SelectOptions::getExternalsByTypeOrNick(null, $note->External->entidade)->type }}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-end fw-bold col-3">Entidade</td>
                                                <td class="text-start">
                                                    @if (!$note->External)
                                                        <select class="form-select form-select-sm"
                                                            aria-label="Small select example"
                                                            wire:model.defer="selAgency">
                                                            <option selected>Selecione</option>
                                                            @foreach (SelectOptions::getExternals($selType) as $agency)
                                                                <option value="{{ $agency->nick }}">
                                                                    {{ $agency->nick }}
                                                                </option>
                                                            @endforeach

                                                        </select>
                                                    @else
                                                        {{ SelectOptions::getExternalsByTypeOrNick(null, $note->External->entidade)->agency }}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-end fw-bold col-3">Protocolo</td>
                                                <td class="text-start fw-bold pe-1">

                                                    {{ $note->External && $note->External->Protocols->count() ? $note->External->Protocols->last()->protocol : ' --- ' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-end fw-bold col-3 align-middle">Novo Protocolo</td>
                                                <td class="text-start">

                                                    <input type="text" class="form-control"
                                                        aria-label="Sizing example input"
                                                        aria-describedby="inputGroup-sizing-sm"
                                                        wire:model.defer="protocol.protocol">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-end fw-bold col-3">Descrição</td>
                                                <td class="text-start">
                                                    <textarea class="form-control" placeholder="Ex. Protocolo de Entrada de Documentação" id="floatingTextarea2"
                                                        style="height: 100px; resize: none;" wire:model.defer="protocol.description"></textarea>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-end fw-bold col-3">Motivo</td>
                                                <td class="text-start">
                                                    <select class="form-select form-select-sm"
                                                        aria-label="Small select example"
                                                        wire:model.defer="comment.title">
                                                        <option selected>Selecione</option>
                                                        @foreach (SelectOptions::getProtocolReasons() as $reason)
                                                            <option value="{{ $reason->value }}">{{ $reason->reason }}
                                                            </option>
                                                        @endforeach

                                                    </select>

                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-end fw-bold col-3">Comentários</td>
                                                <td class="text-start">
                                                    <textarea class="form-control" placeholder="Ex. Protocolo de Entrada de Documentação" id="floatingTextarea2"
                                                        style="height: 100px; resize: none;" wire:model.defer="comment.comment"></textarea>
                                                </td>
                                            </tr>

                                        </tbody>
                                    </table>
                                    <div class="card-footer">
                                        <div class="d-flex justify-content-end">
                                            <button class="btn btn-primary btn-sm" wire:click="save">SALVAR</button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="col-6">
                                <div class="card">
                                    <div class="card-header py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                        <h4 class="fs-5 my-0 py-0">Arquivos</h4>
                                    </div>
                                    <div class="card-body py-1 my-0">
                                        @if ($note->Files->count())
                                            <table class="table table-sm table-condensed table-striped table-hover">
                                                <thead class="">
                                                    <th class="text-center">
                                                        {{-- <input class="form-check-input border border-1 border-secondary"
                                                            type="checkbox"></td> --}}
                                                    </th>
                                                    <th class="text-center col-1">Serviço</th>
                                                    <th class="text-center">Tipo</th>
                                                    <th class="text-center">Arquivo</th>
                                                </thead>
                                                <tbody>
                                                    @foreach ($note->Files->sortBy('file_name') as $file)
                                                        {{-- @dump($file->ext) --}}
                                                        <tr>
                                                            <td class="text-center align-middle"><input
                                                                    class="form-check-input border border-1 border-secondary"
                                                                    type="checkbox" value="{{ $file->id }}"
                                                                    wire:model.defer="selectedFiles"></td>
                                                            <td class="text-center align-middle">
                                                                {{ isset($file->Service->service) ? $file->Service->service : '' }}
                                                            </td>
                                                            <td class="text-center align-middle"><i
                                                                    class="{{ FileIcon::getIcon($file->ext)->icon }} fs-4 align-middle"></i>
                                                            </td>
                                                            <td class="text-center align-middle"><span
                                                                    wire:click.prenvet="downloadFile({{ $file->id }})"
                                                                    style="cursor: pointer;">{{ $file->file_name }}</span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>

                                            </table>
                                            <button class="btn btn-sm btn-primary" wire:click.prevent="zipFiles"><i
                                                    class="bx bxs-cloud-download"></i> Baixar
                                                Selecionados</button>
                                        @else
                                            <div class="card">
                                                <div class="card-body">
                                                    <h4 class="text-center">SEM ARQUIVOS</h4>
                                                </div>
                                            </div>
                                        @endif


                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                        <div class="d-flex justify-content-between align-middle">
                                            <h5 class="fs-5 my-0 py-0 align-middle">Protocolos</h5>
                                        </div>
                                    </div>
                                    @if ($note->External && $note->External->Protocols->count())
                                        <table class="table table-sm table-condensed table-striped-columns">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class="text-center">DtHora</th>
                                                    <th scope="col" class="text-center">Protocolo</th>
                                                    <th scope="col" class="text-center">Descrição</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($note->External->Protocols->sortByDesc('created_at') as $protocol)
                                                    <tr>
                                                        <td class="text-center text-wrap">
                                                            {{ date('d/m/Y H:i:s', strToTime($protocol->created_at)) }}
                                                        </td>
                                                        <td class="text-center"> {{ $protocol->protocol }}</td>
                                                        <td class="text-center text-wrap">{{ $protocol->description }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="card-body">
                                            <h5 class="text-center my-4 fw-bold">SEM PROTOCOLO REGISTRADO</h5>
                                        </div>
                                    @endif
                                </div>

                                <div class="card edp-bg-stategrey-50">
                                    <div class="card-header py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                        <div class="d-flex justify-content-between align-middle">
                                            <h5 class="fs-5 my-0 py-0 align-middle">Comentários</h5>
                                        </div>
                                    </div>
                                    @if ($note->External && $note->External->Comments->count())
                                        <div class="scrollable-div rounded" style="max-height: 500px;">
                                            @foreach ($note->External->Comments->sortByDesc('created_at') as $comment)
                                                <table class="table table-sm table-condensed table-striped-columns">
                                                    <tbody>
                                                        <tr>
                                                            <td class="text-end fw-bold" style="width: 100px;">
                                                                Motivo
                                                            </td>
                                                            <td class="text-uppercase">{{ $comment->title }}</td>

                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold" style="width: 100px;">
                                                                Usuario
                                                            </td>
                                                            <td class="fw-bold text-uppercase">
                                                                {{ $comment->User->name }}
                                                            </td>


                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold" style="width: 100px;">
                                                                Comentário
                                                            </td>
                                                            <td class="text-wrap">
                                                                {{ $comment->comment }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold" style="width: 100px;">
                                                                Data
                                                            </td>
                                                            <td class="">
                                                                {{ date('d/m/Y H:i', strToTime($comment->created_at)) }}
                                                            </td>


                                                        </tr>
                                                    </tbody>
                                                </table>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="card-body">
                                            <h5 class="text-center my-4 fw-bold">SEM COMENTÁRIOS REGISTRADO</h5>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Capturando o evento de fechamento do modal
    document.getElementById('modal_protocols').addEventListener('hidden.bs.modal', () => {

        Livewire.emitTo('services.oexterno.actions.protocols', 'cleanAll');
    });
</script>
