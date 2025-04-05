@php
    use App\Helpers\SelectOptions;
    use Illuminate\Support\Facades\Storage;
    use Carbon\Carbon;
@endphp

<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="modalReturnWorked" tabindex="-1">
        <div class="modal-dialog modal-xl">
            @if ($workReport)
                <div class="modal-content edp-bg-gray">
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        EDITAR INFORME {{ $workReport->Note->note }}
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-7">
                                <div class="card mb-3">
                                    <h5 class="card-header py-0 my-0 edp-bg-sprucegreen-70 text-edp-verde">Dados Nota
                                    </h5>
                                    <table class="table table-condensed table-sm table-striped-columns">
                                        <tbody>
                                            <tr>
                                                <td class="align-middle text-end" style="width: 150px;">Nota/Ov</td>
                                                <td class="align-middle">{{ $workReport->Note->note }}</td>
                                            </tr>
                                            <tr>
                                                <td class="align-middle text-end" style="width: 150px;">Ordem</td>
                                                <td class="align-middle">
                                                    @if ($workReport->Orders->count())
                                                        @foreach ($workReport->Orders as $order)
                                                            <p class="my-o py-0">{{ $order->ordem }}</p>
                                                        @endforeach
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="align-middle text-end" style="width: 150px;">Rubrica</td>
                                                <td class="align-middle">{{ $workReport->Note->rubrica }}</td>
                                            </tr>
                                            <tr>
                                                <td class="align-middle text-end" style="width: 150px;">Município</td>
                                                <td class="align-middle">{{ $workReport->Note->lexp }}</td>
                                            </tr>
                                            <tr>
                                                <td class="align-middle text-end" style="width: 150px;">Group1</td>
                                                <td class="align-middle">{{ $workReport->Note->group1 }}</td>
                                            </tr>
                                            <tr>
                                                <td class="align-middle text-end" style="width: 150px;">Group2</td>
                                                <td class="align-middle">{{ $workReport->Note->group2 }}</td>
                                            </tr>
                                            <tr>
                                                <td class="align-middle text-end" style="width: 150px;">Group3</td>
                                                <td class="align-middle">{{ $workReport->Note->group3 }}</td>
                                            </tr>
                                            <tr>
                                                <td class="align-middle text-end" style="width: 150px;">Group5</td>
                                                <td class="align-middle">{{ $workReport->Note->group5 }}</td>
                                            </tr>
                                            <tr>
                                                <td class="align-middle text-end" style="width: 150px;">Centro Trabalho
                                                </td>
                                                <td class="align-middle">{{ $workReport->Note->centerjob }}</td>
                                            </tr>
                                            <tr>
                                                <td class="align-middle text-end" style="width: 150px;">Status Atual
                                                </td>
                                                <td class="align-middle">{{ $workReport->Note->nstats }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="card mb-3">
                                    <h5 class="card-header py-0 my-0 edp-bg-sprucegreen-70 text-edp-verde">Motivo
                                        Retorno
                                    </h5>

                                    @if ($workReport->ReturnRamal->count())
                                        <table class="table table-condensed table-sm table-striped-columns">
                                            <tbody>
                                                <tr>
                                                    <td class="align-middle text-end" style="width: 150px;">Motivo</td>
                                                    <td class="align-middle text-primary">
                                                        {{ $workReport->ReturnRamal[$pag]->category }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="align-middle text-end" style="width: 150px;">Descrição
                                                    </td>
                                                    <td class="align-middle">
                                                        <p class="my-0 py-0">
                                                            {{ $workReport->ReturnRamal[$pag]->text_obs }}
                                                        </p>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="align-middle text-end" style="width: 150px;">Responsável
                                                    </td>
                                                    <td class="align-middle">
                                                        {{ $workReport->ReturnRamal[$pag]->User->name }}
                                                        ({{ $workReport->ReturnRamal[$pag]->User->email }})</td>
                                                </tr>
                                                <tr>
                                                    <td class="align-middle text-end" style="width: 150px;">Data
                                                    </td>
                                                    <td class="align-middle">
                                                        {{ Carbon::parse($workReport->ReturnRamal[$pag]->created_at)->format('d/m/Y H:i:s') }}
                                                    </td>
                                                </tr>

                                            </tbody>
                                        </table>
                                    @endif
                                    @if ($workReport->ReturnRamal->count() > 1)
                                        <div class="card-footer">
                                            <div class="col-auto my-0">
                                                <nav aria-label="Page navigation">
                                                    <ul class="pagination pagination-sm justify-content-end">
                                                        <li class="page-item @disabled($pag == 0)"><a
                                                                class="page-link" href="#"
                                                                wire:click="previousPage">Anterior</i></a></li>
                                                        <li class="page-item"><a class="page-link"
                                                                href="#">{{ $pag + 1 }}/{{ $workReport->ReturnRamal->count() }}</a>
                                                        </li>

                                                        <li class="page-item @disabled($pag == $workReport->ReturnRamal->count() - 1)"><a
                                                                class="page-link" href="#"
                                                                wire:click="nextPage">Proximo</a>
                                                        </li>
                                                    </ul>
                                                </nav>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- <div class="mb-3">

                                    @livewire('files.manager.create-gen-files', ['note' => $workReport->Note, 'service' => 'INFORME DE OBRA'], key('files_forms'))
                                </div> --}}

                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Observações (Desligamento
                                        programado/ Alterações/ Informações Gerais): </label>
                                    <textarea type="text" class="form-control" id="observacao" rows="4" wire:model.defer="workReport.observation"> </textarea>
                                </div>

                                {{-- <div class="mb-3 " style="max-width: 300px">
                                    <label for="exampleFormControlInput1" class="form-label">Houveram danos a
                                        propriedade
                                        de
                                        particulares? (Ex.: Calçada Quebrada, Padrão Danificado, e outros.) <span
                                            class="text-danger fw-bold">*</span></label>
                                    <select class="form-select" aria-label="Default select example"
                                        wire:model="workReport.damage" id="damage">
                                        <option selected>Selecione</option>
                                        <option value="1">Sim</option>
                                        <option value="0">Não</option>
                                    </select>
                                </div> --}}

                                {{-- @if ($workReport->damage)
                                    <div class="mb-3">
                                        <label for="exampleFormControlInput1" class="form-label">Detalhar os Danos
                                            Causados e
                                            Previsão de reparo: <span class="text-danger fw-bold">*</span></label>
                                        <textarea type="text" class="form-control" id="description" rows="4"
                                            wire:model.defer="workReport.description"> </textarea>
                                    </div>
                                @endif --}}

                                {{-- <div class="mb-3 " style="max-width: 300px">
                                    <label for="exampleFormControlInput1" class="form-label">Ligação foi executada do
                                        momento
                                        da obra? <span class="text-danger fw-bold">*</span></label>
                                    <select class="form-select" aria-label="Default select example"
                                        wire:model="workReport.connection" id="connection">
                                        <option selected>Selecione</option>
                                        <option value="1">Sim</option>
                                        <option value="0">Não</option>
                                    </select>
                                </div> --}}


                                {{-- <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Numero da DD (Ultimo
                                        Relacionado a esta obra) <span class="text-danger fw-bold">*</span></label>
                                    <input type="text" class="form-control" id="dd"
                                        wire:model.defer="workReport.dd">
                                </div>

                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Nome da Equipe (WPA)
                                        <span class="text-danger fw-bold">*</span>:</label>
                                    <input type="text" class="form-control" id="team"
                                        wire:model.defer="workReport.team">
                                </div> --}}

                                {{-- <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Qual o encarregado
                                        responsável
                                        pela execução da atividade? <span class="text-danger fw-bold">*</span></label>
                                    <input type="text" class="form-control" id="responsible"
                                        wire:model.defer="workReport.responsible">
                                </div>

                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Responsável por este
                                        informe? <span class="text-danger fw-bold">*</span></label>
                                    <input type="text" class="form-control" id="informer"
                                        wire:model.defer="workReport.informer">
                                </div> --}}

                            </div>








                            <div class="col-5">
                                <div class="card mb-3">



                                    <div class="card mt-2">
                                        <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">ARQUIVOS
                                            ANEXADOS
                                        </h5>
                                        <div class="card-body py-2 px-3">
                                            @livewire('components.files.show-files-pool', ['files' => $workReport->Note->Files], key('files-pool-{{ $workReport->Note->id }}'))
                                        </div>
                                    </div>

                                    {{-- <h5 class="card-header py-0 my-0 edp-bg-sprucegreen-70 text-edp-verde">Arquivos
                                        Associado</h5>
                                    {{-- <h5 class="card-header py-0 my-0 edp-bg-sprucegreen-70 text-edp-verde">Arquivos
                                        Associado</h5>
                                    @if ($workReport->Note->Files->count())
                                        <div class="card-body">
                                            <table class="table table-sm table-condensed table-striped">
                                                <thead>
                                                    <tr>
                                                        <th class="align-middle text-center">Nome</th>
                                                        <th class="align-middle text-center">Tam</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($workReport->Note->Files as $file)
                                                        <tr>
                                                            <td class="align-middle text-center">
                                                                {{ $file->file_name }}
                                                            </td>

                                                            <td class="align-middle text-center">
                                                                @if (Storage::exists($file->path))
                                                                    {{ round(Storage::size($file->path) / 1024, 2) }}
                                                                    KB
                                                                @else
                                                                    NA
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="card-body">
                                            <h4 class="text-center">SEM ARQUIVOS</h4>
                                        </div>
                                    @endif --}}
                                </div>


                                @livewire('btzero.components.equipaments', ['workReport' => $workReport], key('component-equipment'))
                                {{-- @livewire('partner.components.meeters', ['workReport' => $workReport], key('component-meeters')) --}}


                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeAll">FECHAR</button>
                        <button type="button" class="btn btn-primary" wire:click="toSave">RE-SUBMETER
                            INFORME</button>
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>

<script>
    // Capturando o evento de fechamento do modal
    document.getElementById('modalReturnWorked').addEventListener('hidden.bs.modal', () => {

        Livewire.emitTo('partner.actions.worked-return-form', 'closeAll');
    });
</script>
