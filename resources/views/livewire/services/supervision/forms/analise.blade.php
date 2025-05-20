@push('css')
    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.2);
            /* opcional: fundo escurecido */
            z-index: 9999;
            /* para garantir que o overlay esteja na frente de tudo */
        }

        .loading-message {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush

@php
    use App\Helpers\SelectOptions;
@endphp



<div>

    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    @if ($view_form)
        <div class="container">
            <div class="card">
                <h4 class="card-header">Informações da Nota</h4>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <dl class="row">
                                <dt class="col-sm-4">Nota/Ov:</dt>
                                <dd class="col-sm-8">{{ $note->note }}</dd>
                                <dt class="col-sm-4">Cliente:</dt>
                                <dd class="col-sm-8">{{ $note->client }}</dd>
                                <dt class="col-sm-4">Município</dt>
                                <dd class="col-sm-8">{{ $note->lexp }}</dd>
                                <dt class="col-sm-4 text-danger">MMGD</dt>
                                <dd class="col-sm-8 text-danger">{{ $note->mmgd ? 'SIM' : 'NÃO' }}</dd>
                            </dl>
                        </div>

                        <div class="col-6">
                            <dl class="row">
                                <dt class="col-sm-4">Tipo:</dt>
                                <dd class="col-sm-8">{{ $note->rubrica }}</dd>
                                <dt class="col-sm-4">Data:</dt>
                                <dd class="col-sm-8">{{ date('d/m/Y', strToTime($note->dt_status)) }}</dd>
                                <dt class="col-sm-4">Pedido:</dt>
                                <dd class="col-sm-8">{{ $note->numPedido }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>





            <div class="card">
                <h4 class="card-header">Resultado da Fiscalização</h4>
                <div class="card-body">


                    {{-- <div class="mb-3 col-3">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Postes:</label>
                            <input type="number" min="0" max="500"
                                class="form-control border border-secondary" wire:model.defer="postes">
                        </div> --}}

                    <div class="mb-3 col-2">
                        <label for="inputPassword" class="col-sm-12 col-form-label">Nessessidade de D5?</label>
                        <select class="form-select border border-secondary" aria-label="Default select example"
                            wire:model="d5">
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
                            <input type="text" class="form-control border border-secondary"
                                aria-label="Default select example" wire:model.defer="d5note" />

                        </div>

                        <div class="mb-3 col-3">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Motivo: <span
                                    class="text-danger fw-bold">*</span></label>
                            <select class="form-select border border-secondary" aria-label="Default select example"
                                wire:model.defer="d5reason">
                                <option value="" selected>Selecione</option>
                                @foreach (SelectOptions::getD5Reasons() as $reasonD5)
                                    <option value="{{ $reasonD5->value }}" selected>{{ $reasonD5->reason }}</option>
                                @endforeach

                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Observações da D5: </label>
                            <textarea id="infoTextArea2" class="form-control border border-secondary" rows="8" wire:model.defer="d5detail"></textarea>
                        </div>

                    @endif

                    @if ($d5 == 0 || $d5 == 1)


                        <div class="mb-3 col-3">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Conclusão:</label>
                            <select class="form-select border border-secondary" aria-label="Default select example"
                                wire:model="conclusion">
                                <option value="" selected>Selecione</option>
                                @foreach (SelectOptions::getSupervisionEnd() as $supEnd)
                                    <option value="{{ $supEnd->value }}" selected>{{ $supEnd->reason }}</option>
                                @endforeach
                                @if ($production->parcial == true)
                                    <option value="reject">Rejeitar</option>
                                @endif
                            </select>

                        </div>


                        <div class="mb-3">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Observações: <span
                                    class="fw-bold"><i class="ri-file-copy-line copyButton" data-id="infoTextArea2"
                                        style="cursor: pointer;"></i></span></label>
                            <textarea id="infoTextArea2" class="form-control border border-secondary" rows="8" wire:model.defer="info"></textarea>
                        </div>
                    @endif




                </div>
            </div>


            <div class="d-flex justify-content-end">
                <button class="btn btn-primary me-2" wire:click.prevent="save_info">SALVAR</button>
                <button class="btn btn-warning me-2" wire:click.prevent="to_pause">PAUSAR</button>
                <button class="btn btn-success me-2"
                    wire:click.prevent="to_finish({{ $analise->production_id }})">ENCERRAR</button>

            </div>
        @else
            <div class="loading-overlay">
                <div class="loading-message">
                    <h1>Carregando Dados...</h1>
                </div>
            </div>
    @endif
</div>
