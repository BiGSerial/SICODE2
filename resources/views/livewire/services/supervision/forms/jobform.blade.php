@php
    use App\Helpers\SelectOptions;
@endphp
<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="formProductionModal" tabindex="-1"
        aria-labelledby="formProductionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            @if ($production)
                <div class="modal-content">
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <h1 class="modal-title fs-5" id="formProductionModalLabel">
                            {{ mb_strToUpper($production->Service->service) }} - {{ $production->Note->note }}</h1>
                        <button type="button" class="btn-close btn-succes" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body edp-bg-stategrey-50">
                        <div class="container">
                            <div class="card">
                                <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">INFORMAÇÕES</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm table-condensed table-striped-columns">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold col-2 align-middle">NOTA/OV:</td>
                                                <td class="align-middle fw-bold">{{ $production->Note->note }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold col-2 align-middle">ORDEM:</td>
                                                <td class="align-middle">
                                                    @if ($production->Note->WorkForm && $production->Note->WorkForm->Orders->count())
                                                        @foreach ($production->Note->WorkForm->Orders as $order)
                                                            <p class="my-1 py-0">{{ $order->ordem }}</p>
                                                        @endforeach
                                                    @elseif($production->partial)
                                                        @foreach ($production->Note->Partials->last()->Orders as $order)
                                                            <p class="my-1 py-0">{{ $order->ordem }}</p>
                                                        @endforeach
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold col-2 align-middle">RUBRICA:</td>
                                                <td class="align-middle text-uppercase">{{ $production->Note->rubrica }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold col-2 align-middle text-uppercase">MUNICIPIO:</td>
                                                <td class="align-middle">{{ $production->Note->lexp }}</td>
                                            </tr>

                                            <tr class="mt-3">
                                                <td class="fw-bold col-2 align-middle text-uppercase">MUDANÇA NO
                                                    PROJETO:</td>
                                                <td class="align-middle">
                                                    @if ($production->Note->WorkForm)
                                                        {{ $production->Note->WorkForm->changes ? 'SIM' : 'NÃO' }}
                                                    @elseif ($production->partial)
                                                        Fiscalização Parcial
                                                    @endif

                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold col-2 align-middle text-uppercase">Data Informada:
                                                </td>
                                                <td class="align-middle">
                                                    @if ($production->Note->WorkForm)
                                                        {{ date('d/m/Y', strToTime($production->Note->WorkForm->date)) }}
                                                    @elseif ($production->partial)
                                                        {{ date('d/m/Y', strToTime($production->Note->Partials->last()->created_at)) }}
                                                    @endif

                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold col-2 align-middle text-uppercase">Data Sicode:</td>
                                                <td class="align-middle">
                                                    @if (isset($production->Note->WorkForm))
                                                        {{ date('d/m/Y H:i:s', strToTime($production->Note->WorkForm->informed_at)) }}
                                                    @elseif ($production->partial)
                                                        Não Aplica
                                                    @endif
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="fw-bold col-2 align-middle text-uppercase">Equipe WPA:</td>
                                                <td class="align-middle">
                                                    @if (isset($production->Note->WorkForm))
                                                        {{ $production->Note->WorkForm->team }}
                                                    @elseif ($production->partial)
                                                        Não Aplica
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold col-2 align-middle text-uppercase">Responsável
                                                    Execução:</td>
                                                <td class="align-middle">
                                                    @if (isset($production->Note->WorkForm))
                                                        {{ $production->Note->WorkForm->responsible }}
                                                    @elseif ($production->partial)
                                                        {{ $production->Note->Partials->last()->responsible }}
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card">
                                <h4 class="card-header">Resultado da Fiscalização @if ($production->partial)
                                        (PARCIAL)
                                    @endif
                                </h4>


                                <div class="card-body">

                                    <div class="mb-3 col-2">
                                        <label for="inputPassword" class="col-sm-12 col-form-label">Necessidade de
                                            D5?</label>
                                        <select class="form-select border border-secondary"
                                            aria-label="Default select example" wire:model="d5">
                                            <option value="" selected>Selecione</option>
                                            {{-- <option value="DEPENDE DE ORGAO EXTERNO">DEPENDE DE ORGÃO EXTERNO</option> --}}
                                            <option value="1">SIM</option>
                                            <option value="0">NÃO</option>

                                            {{-- <option value="INSPECAO REJEITADA">INSPEÇÃO REJEITADA</option>
                                                <option value="INSPECAO REJEITADA">INSPEÇÃO APROVADA</option> --}}
                                        </select>
                                    </div>

                                    @if ($d5 == 1)

                                        <div class="mb-3 col-3">
                                            <label for="inputPassword" class="col-sm-12 col-form-label">Nota D5: <span
                                                    class="text-danger fw-bold">*</span></label>
                                            <input type="text"
                                                class="form-control border border-secondary col-sm-2 col-xl-1"
                                                aria-label="Default select example" wire:model.defer="return.note"
                                                required />

                                        </div>

                                        <div class="mb-3 col-3">
                                            <label for="inputPassword" class="col-sm-12 col-form-label">Motivo: <span
                                                    class="text-danger fw-bold">*</span></label>
                                            <select class="form-select border border-secondary"
                                                aria-label="Default select example" wire:model.defer="return.reason">
                                                <option value="" selected>Selecione</option>
                                                @foreach (SelectOptions::getD5Reasons() as $reasonD5)
                                                    <option value="{{ $reasonD5->value }}" selected>
                                                        {{ $reasonD5->reason }}</option>
                                                @endforeach

                                            </select>
                                        </div>



                                        <div class="mb-3">
                                            <label for="inputPassword" class="col-sm-12 col-form-label">Observações da
                                                D5: </label>
                                            <textarea id="infoTextArea2" class="form-control border border-secondary" rows="8"
                                                wire:model.defer="return.description"></textarea>
                                        </div>

                                    @endif

                                    @if ($d5 == 0 || $d5 == 1)

                                        <div class="mb-3 col-3">
                                            <label for="inputPassword" class="col-sm-12 col-form-label">Postes:</label>
                                            <input type="number" name="number" id="poles"
                                                class="form-control border border-secondary" min="0"
                                                wire:model.defer="analise.postes">

                                        </div>

                                        <div class="mb-3 col-3">
                                            <label for="inputPassword"
                                                class="col-sm-12 col-form-label">Conclusão:</label>
                                            <select class="form-select border border-secondary"
                                                aria-label="Default select example" wire:model="analise.conclusion">
                                                <option value="" selected>Selecione</option>
                                                @foreach (SelectOptions::getSupervisionEnd() as $supEnd)
                                                    <option value="{{ $supEnd->value }}" selected>
                                                        {{ $supEnd->reason }}</option>
                                                @endforeach
                                                @if ($production->partial)
                                                    <option value="reject">Rejeitar Obra</option>
                                                @endif
                                            </select>

                                        </div>
                                    @endif

                                    <div class="mb-3 ">

                                        {{-- @livewire('files.filesupervision', ['note' => $production->Note, 'production' => $production], key('FilesSupervision')) --}}
                                        @livewire('files.manager.create-prod-files', ['production' => $production, 'needFiles' => false], key('FilesSupervision'))
                                    </div>

                                    <div class="mb-3">
                                        <label for="inputPassword" class="col-sm-12 col-form-label">Observações:
                                            <span class="fw-bold"><i class="ri-file-copy-line copyButton"
                                                    data-id="infoTextArea2"
                                                    style="cursor: pointer;"></i></span></label>
                                        <textarea id="infoTextArea2" class="form-control border border-secondary" rows="8"
                                            wire:model.defer="analise.info"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer edp-bg-stategrey-100">
                        <button type="button" class="btn btn-secondary"
                            wire:click.prevent="saveForm()">SALVAR</button>
                        <button type="button" class="btn btn-info"
                            wire:click.prevent="waitingForm()">ESPERAR</button>
                        <button type="button" class="btn btn-warning"
                            wire:click="$emitTo('components.pausenote.pausenote2', 'stop_note', {{ $production }})">PAUSAR</button>
                        <button type="button" class="btn btn-success"
                            wire:click.prevent="to_finish()">ENCERRAR</button>
                    </div>
                </div>
            @endif
        </div>
    </div>
    {{-- Livewire Components --}}
    @livewire('components.pausenote.pausenote2', key('PauseNotes2'))
</div>
