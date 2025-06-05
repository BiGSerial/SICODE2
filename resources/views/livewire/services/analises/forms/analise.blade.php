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
            {{-- ======= Card: Informações da Nota ======= --}}
            <div class="card mb-4">
                <h4 class="card-header">Informações da Nota</h4>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <dl class="row">
                                <dt class="col-sm-4">Nota/Ov:</dt>
                                <dd class="col-sm-8">{{ $note->note }}</dd>
                                <dt class="col-sm-4">Cliente:</dt>
                                <dd class="col-sm-8">{{ $note->client }}</dd>
                                <dt class="col-sm-4">Município</dt>
                                <dd class="col-sm-8">{{ $note->lexp }}</dd>
                            </dl>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <dl class="row">
                                <dt class="col-sm-4">Tipo:</dt>
                                <dd class="col-sm-8">{{ $note->rubrica }}</dd>
                                <dt class="col-sm-4">Data:</dt>
                                <dd class="col-sm-8">{{ date('d/m/Y', strtotime($note->dt_status)) }}</dd>
                                <dt class="col-sm-4">Pedido:</dt>
                                <dd class="col-sm-8">{{ $note->numPedido }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======= Card: Informação de Análise ======= --}}
            <div class="card mb-4">
                <h4 class="card-header">Informação de Análise</h4>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Número de Instalação --}}
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="ninst" wire:model.defer="ninst"
                                    placeholder="Número de Instalação">
                                <label for="ninst">Número de Instalação</label>
                            </div>
                        </div>

                        {{-- Número do Medidor --}}
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="nmedidor" wire:model.defer="nmedidor"
                                    placeholder="Número do Medidor">
                                <label for="nmedidor">Número do Medidor</label>
                            </div>
                        </div>

                        {{-- Patrimônio (ESTF) --}}
                        <div class="col-12 col-md-6 col-lg-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="patrimonio" wire:model.defer="patrimonio"
                                    placeholder="Patrimônio (ESTF)">
                                <label for="patrimonio">Patrimônio (ESTF)</label>
                            </div>
                        </div>

                        {{-- Latitude UTM (ESTF) --}}
                        <div class="col-12 col-md-6 col-lg-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="lat" wire:model.defer="lat"
                                    placeholder="Latitude UTM (ESTF)">
                                <label for="lat">Latitude UTM (ESTF)</label>
                            </div>
                        </div>

                        {{-- Longitude UTM (ESTF) --}}
                        <div class="col-12 col-md-6 col-lg-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="lon" wire:model.defer="lon"
                                    placeholder="Longitude UTM (ESTF)">
                                <label for="lon">Longitude UTM (ESTF)</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======= Card: Informação de Carga ======= --}}
            <div class="card mb-4">
                <h4 class="card-header">Informação de Carga</h4>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Carregamento Inicial (%) --}}
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="carga_ini" wire:model.defer="carga_ini"
                                    placeholder="Carregamento Inicial (%)">
                                <label for="carga_ini">Carregamento Inicial (%)</label>
                            </div>
                        </div>

                        {{-- Carregamento Final (%) --}}
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="carga_fim" wire:model.defer="carga_fim"
                                    placeholder="Carregamento Final (%)">
                                <label for="carga_fim">Carregamento Final (%)</label>
                            </div>
                        </div>

                        {{-- Queda (%) --}}
                        <div class="col-12 col-md-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="queda" wire:model.defer="queda"
                                    placeholder="Queda (%)">
                                <label for="queda">Queda (%)</label>
                            </div>
                        </div>

                        {{-- Queda Máx (%) --}}
                        <div class="col-12 col-md-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="queda_max" wire:model.defer="queda_max"
                                    placeholder="Queda Máx (%)">
                                <label for="queda_max">Queda Máx (%)</label>
                            </div>
                        </div>

                        {{-- Queda Cliente (%) --}}
                        <div class="col-12 col-md-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="queda_cliente"
                                    wire:model.defer="queda_cliente" placeholder="Queda Cliente (%)">
                                <label for="queda_cliente">Queda Cliente (%)</label>
                            </div>
                        </div>

                        {{-- Número de Vãos (qtd) --}}
                        <div class="col-12 col-md-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="vao" wire:model.defer="vao"
                                    placeholder="Número de Vãos (qtd)">
                                <label for="vao">Número de Vãos (qtd)</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======= Card: Resultado Análise ======= --}}
            <div class="card mb-4">
                <h4 class="card-header">Resultado Análise</h4>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Restrições --}}
                        <div class="col-12 col-md-4 col-lg-3">
                            <div class="form-floating">
                                <select class="form-select" id="restriction" wire:model="restriction"
                                    aria-label="Restrições">
                                    <option value="">SEM RESTRIÇÃO</option>
                                    <option value="SERVIDAO">FAIXA DE SERVIDÃO</option>
                                    <option value="FUNAI">FUNAI</option>
                                    <option value="LOTEAMENTO">LOTEAMENTO CLANDESTINO</option>
                                    <option value="AMBIENTE">MEIO AMBIENTE</option>
                                    <option value="SEMMA">SEMMA</option>
                                </select>
                                <label for="restriction">Restrições</label>
                            </div>
                        </div>

                        {{-- Motivo (só aparece quando $restriction != "") --}}
                        @if ($restriction)
                            <div class="col-12 col-md-4 col-lg-3">
                                <div class="form-floating">
                                    <select class="form-select" id="motivo" wire:model="motivo"
                                        aria-label="Motivo">
                                        <option value="">SELECIONE</option>

                                        {{-- SERVIDAO --}}
                                        @if ($restriction === 'SERVIDAO')
                                            <option value="SERVIDAO">SERVIDAO</option>
                                        @endif

                                        {{-- LOTEAMENTO --}}
                                        @if ($restriction === 'LOTEAMENTO')
                                            <option value="VILLAGE">VILLAGE DO SOL</option>
                                            <option value="BANANAL">RIO BANANAL</option>
                                            <option value="SERRA">SERRA</option>
                                            <option value="DM">DOMINGOS MARTINS</option>
                                            <option value="OUTROS">OUTROS</option>
                                        @endif

                                        {{-- SEMMA --}}
                                        @if ($restriction === 'SEMMA')
                                            <option value="SERRA">SERRA</option>
                                            <option value="DM">DOMINGOS MARTINS</option>
                                            <option value="OUTROS">OUTROS</option>
                                        @endif

                                        {{-- FUNAI --}}
                                        @if ($restriction === 'FUNAI')
                                            <option value="FUNAI">FUNAI</option>
                                        @endif

                                        {{-- AMBIENTE --}}
                                        @if ($restriction === 'AMBIENTE')
                                            <option value="IEMA">IEMA</option>
                                            <option value="ICMBIO">ICMBIO</option>
                                        @endif
                                    </select>
                                    <label for="motivo">Motivo</label>
                                </div>
                            </div>
                        @endif

                        {{-- Município (quando $motivo === 'OUTROS' e cidade não informada no $note->lexp) --}}
                        @if ($motivo === 'OUTROS' && (!trim($note->lexp) || $note->lexp == null))
                            <div class="col-12 col-md-6 col-lg-4">
                                @if ($cities)
                                    <div class="form-floating">
                                        <select class="form-select" id="municipio" wire:model.defer="municipio"
                                            aria-label="Município">
                                            <option value="" selected>Selecione...</option>
                                            @foreach ($cities as $city)
                                                <option value="{{ $city->cidade }}">{{ $city->municipio }}</option>
                                            @endforeach
                                        </select>
                                        <label for="municipio">Município</label>
                                    </div>
                                @else
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="municipio"
                                            wire:model.defer="municipio" placeholder="Município">
                                        <label for="municipio">Município</label>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Reserva (quando IEMA ou ICMBIO) --}}
                        @if ($motivo === 'IEMA' || $motivo === 'ICMBIO')
                            <div class="col-12 col-md-4 col-lg-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="reserva"
                                        wire:model.defer="reserva" placeholder="Reserva">
                                    <label for="reserva">Reserva</label>
                                </div>
                            </div>
                        @endif

                        {{-- Botão "Gerar Carta" (só aparece quando há $restriction e $motivo selecionados) --}}
                        @if ($restriction && $motivo)
                            <div class="col-12 col-md-4 col-lg-2 d-flex align-items-end">
                                <button class="btn btn-primary w-100"
                                    wire:click.prevent="gerarCarta('{{ $restriction }}', '{{ $motivo }}')">
                                    Gerar Carta
                                </button>
                            </div>
                        @endif

                        {{-- MMGD? --}}
                        <div class="col-12 col-md-4 col-lg-3">
                            <div class="form-floating">
                                <select class="form-select" id="mmgd" wire:model.defer="mmgd"
                                    aria-label="MMGD">
                                    <option value="" selected>Selecione</option>
                                    <option value="SIM">SIM</option>
                                    <option value="NAO">NÃO</option>
                                </select>
                                <label for="mmgd">MMGD?</label>
                            </div>
                        </div>

                        {{-- MMGD? --}}
                        <div class="col-12 col-md-4 col-lg-3">
                            <div class="form-floating">
                                <select class="form-select" id="is45" wire:model.defer="is45"
                                    aria-label="is45">
                                    <option value="" selected>Selecione</option>
                                    <option value="SIM">SIM</option>
                                    <option value="NAO">NÃO</option>
                                </select>
                                <label for="is45">Art.90 (45 dias)?</label>
                            </div>
                        </div>

                        {{-- Conclusão --}}
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-floating">
                                <select class="form-select" id="conclusion" wire:model.defer="conclusion"
                                    aria-label="Conclusão">
                                    <option value="0" selected>Selecione</option>
                                    <option value="ISR - LIBERADO">ISR - LIBERADO</option>
                                    <option value="ENVIADO A CAMPO">ENVIADO A CAMPO</option>
                                    <option value="ENVIADO AO DESENHO">ENVIADO AO DESENHO</option>
                                    <option value="ENVIADO CARTA AO CLIENTE">ENVIADO CARTA AO CLIENTE</option>
                                    <option value="ENVIADO RESPOSTA EMPRESA">ENVIADO RESPOSTA EMPRESA</option>
                                    <option value="ENVIADO PARA O STATUS 21">ENVIADO PARA O STATUS 21</option>
                                </select>
                                <label for="conclusion">Conclusão</label>
                            </div>
                        </div>

                        {{-- Informações (textarea) --}}
                        <div class="col-12">
                            <div class="form-floating">
                                <textarea class="form-control" placeholder="Informações" id="info" style="height: 150px"
                                    wire:model.defer="info"></textarea>
                                <label for="info">Informações</label>
                                <span class="fw-bold">
                                    <i class="ri-file-copy-line copyButton" data-id="infoTextArea2"
                                        style="cursor: pointer;"></i>
                                </span>
                            </div>
                        </div>

                        {{-- Carta (textarea) --}}
                        @if ($card)
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="Carta" id="card" style="height: 200px" wire:model.defer="card"></textarea>
                                    <label for="card">Carta</label>
                                    <span class="fw-bold">
                                        <i class="ri-file-copy-line copyButton" data-id="infoTextArea"
                                            style="cursor: pointer;"></i>
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div> {{-- fim row --}}
                </div> {{-- fim card-body --}}
            </div> {{-- fim card Resultado Análise --}}

            {{-- ======= Botões de Ação ======= --}}
            <div class="d-flex justify-content-end gap-2 mb-4">
                <button class="btn btn-primary" wire:click.prevent="save_info">SALVAR</button>
                <button class="btn btn-warning" wire:click.prevent="to_pause">PAUSAR</button>
                <button class="btn btn-success" wire:click.prevent="to_finish({{ $analise->production_id }})">
                    ENCERRAR
                </button>
            </div>
        </div> {{-- fim container --}}
    @else
        <div class="loading-overlay">
            <div class="loading-message">
                <h1>Carregando Dados...</h1>
            </div>
        </div>
    @endif
</div>
