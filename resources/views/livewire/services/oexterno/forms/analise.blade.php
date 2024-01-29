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
                <h4 class="card-header">Resultado Analise</h4>
                <div class="card-body">

                    <div class="row">
                        <div class="mb-3 col-3">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Restrições:</label>
                            <select class="form-select border border-secondary" aria-label="Default select example"
                                wire:model="restriction">
                                <option value="" selected>Selecione</option>
                                <option value="APROVADO">APROVADO</option>
                                <option value="NEGADO">REPROVADO</option>
                            </select>
                        </div>

                        {{-- @if ($restriction)
                            <div class="mb-3 col-3">
                                <label for="inputPassword" class="col-sm-12 col-form-label">MOTIVO:</label>
                                <select class="form-select border border-secondary"
                                    aria-label="Default select example" wire:model="motivo">
                                    <option value="" selected>SELECIONE</option>

                                    
                                    @if ($restriction === 'SERVIDAO')
                                        <option value="">SERVIDAO</option>
                                    @endif

                                    
                                    @if ($restriction === 'LOTEAMENTO')
                                        <option value="VILLAGE">VILLAGE DO SOL</option>
                                        <option value="BANANAL">RIO BANANAL</option>
                                        <option value="SERRA">SERRA</option>
                                        <option value="DM">DOMINGOS MARITNS</option>
                                        <option value="OUTROS">OUTROS</option>
                                    @endif

                                    @if ($restriction === 'SEMMA')
                                        <option value="SERRA">SERRA</option>
                                        <option value="DM">DOMINGOS MARITNS</option>
                                        <option value="OUTROS">OUTROS</option>
                                    @endif

                           
                                    @if ($restriction === 'FUNAI')
                                        <option value="FUNAI">FUNAI</option>
                                    @endif

                              
                                    @if ($restriction === 'AMBIENTE')
                                        <option value="IEMA">IEMA</option>
                                        <option value="ICMBIO">ICMBIO</option>
                                    @endif

                                </select>
                            </div>
                        @endif --}}

                        {{-- @if ($motivo === 'OUTROS' && !isset($note->lexp))
                            <div class="mb-3 col-2">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Município:</label>
                                <input type="text" class="form-control border border-secondary"
                                    wire:model.defer="municipio">
                            </div>
                        @endif

                        @if ($motivo === 'IEMA' || $motivo === 'ICMBIO')
                            <div class="mb-3 col-2">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Reserva:</label>
                                <input type="text" class="form-control border border-secondary"
                                    wire:model.defer="reserva">
                            </div>
                        @endif

                        <div class="mb-3 col-2">
                            @if ($restriction && $motivo)
                                <label for="inputPassword" class="col-sm-12 col-form-label mt-3"></label>
                                <button class="btn btn-primary align-bottom"
                                    wire:click.prevent="gerarCarta('{{ $restriction }}', '{{ $motivo }}')">Gerar
                                    Carta</button>
                            @endif
                        </div>

                        <div class="mb-3 col-2">
                            <label for="inputPassword" class="col-sm-12 col-form-label">MMGD? <span
                                    class="text-danger fw-bold">*</span></label>
                            <select class="form-select border border-secondary" aria-label="Default select example"
                                wire:model.defer="mmgd">
                                <option value="" selected>Selecione</option>
                                <option value="SIM">SIM</option>
                                <option value="NAO">NÃO</option>
                            </select>
                        </div> --}}

                        <div class="mb-3 col-2">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Protocolo:</label>
                            <input type="text" class="form-control border border-secondary"
                                wire:model.defer="protocol">
                        </div>

                        <div class="mb-3 col-3">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Conclusão: <span
                                    class="text-danger fw-bold">*</span></label>
                            <select class="form-select border border-secondary" aria-label="Default select example"
                                wire:model.defer="conclusion">
                                <option value="0" selected>Selecione</option>
                                <option value="CANCELAMENTO">10 - CANCELAMENTO</option>
                                <option value="AGUARDANDO LIBERAÇÃO">11 - AGUARDANDO LIBERAÇAO</option>
                                <option value="ENVIADO PARA ORÇAMENTO">28 - ENVIADO PARA ORÇAMENTO</option>
                            </select>
                        </div>



                        <div class="mb-3">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Informações: <span
                                    class="fw-bold"><i class="ri-file-copy-line copyButton" data-id="infoTextArea2"
                                        style="cursor: pointer;"></i></span></label>
                            <textarea id="infoTextArea2" class="form-control border border-secondary" rows="8" wire:model.defer="info"></textarea>
                        </div>

                        {{-- @if ($card)
                            <div class="mb-3">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Carta: <span
                                        class="fw-bold"><i class="ri-file-copy-line copyButton"
                                            data-id="infoTextArea" style="cursor: pointer;"></i></span></label>
                                <textarea id="infoTextArea" class="form-control border border-secondary" rows="15" wire:model.defer="card"></textarea>
                            </div>
                        @endif --}}
                    </div>

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
