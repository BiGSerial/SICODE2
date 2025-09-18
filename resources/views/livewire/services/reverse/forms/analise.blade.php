@php
    use App\Helpers\SelectOptions;
@endphp

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
            {{-- <div class="card mb-4">
                <h4 class="card-header">Informação de Análise</h4>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="ninst" wire:model.defer="ninst"
                                    placeholder="Número de Instalação">
                                <label for="ninst">Número de Instalação</label>
                            </div>
                        </div>


                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="nmedidor" wire:model.defer="nmedidor"
                                    placeholder="Número do Medidor">
                                <label for="nmedidor">Número do Medidor</label>
                            </div>
                        </div>


                        <div class="col-12 col-md-6 col-lg-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="patrimonio" wire:model.defer="patrimonio"
                                    placeholder="Patrimônio (ESTF)">
                                <label for="patrimonio">Patrimônio (ESTF)</label>
                            </div>
                        </div>


                        <div class="col-12 col-md-6 col-lg-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="lat" wire:model.defer="lat"
                                    placeholder="Latitude UTM (ESTF)">
                                <label for="lat">Latitude UTM (ESTF)</label>
                            </div>
                        </div>


                        <div class="col-12 col-md-6 col-lg-2">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="lon" wire:model.defer="lon"
                                    placeholder="Longitude UTM (ESTF)">
                                <label for="lon">Longitude UTM (ESTF)</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div> --}}

            {{-- ======= Card: Informação de Carga ======= --}}
            {{-- <div class="card mb-4">
                <h4 class="card-header">Informação de Carga</h4>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="carga_ini" wire:model.defer="carga_ini"
                                    placeholder="Carregamento Inicial (%)">
                                <label for="carga_ini">Carregamento Inicial (%)</label>
                            </div>
                        </div>


                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="carga_fim" wire:model.defer="carga_fim"
                                    placeholder="Carregamento Final (%)">
                                <label for="carga_fim">Carregamento Final (%)</label>
                            </div>
                        </div>


                        <div class="col-12 col-md-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="queda" wire:model.defer="queda"
                                    placeholder="Queda (%)">
                                <label for="queda">Queda (%)</label>
                            </div>
                        </div>


                        <div class="col-12 col-md-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="queda_max" wire:model.defer="queda_max"
                                    placeholder="Queda Máx (%)">
                                <label for="queda_max">Queda Máx (%)</label>
                            </div>
                        </div>


                        <div class="col-12 col-md-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="queda_cliente"
                                    wire:model.defer="queda_cliente" placeholder="Queda Cliente (%)">
                                <label for="queda_cliente">Queda Cliente (%)</label>
                            </div>
                        </div>


                        <div class="col-12 col-md-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="vao" wire:model.defer="vao"
                                    placeholder="Número de Vãos (qtd)">
                                <label for="vao">Número de Vãos (qtd)</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div> --}}

            {{-- ======= Card: Resultado Análise ======= --}}
            <div class="card mb-4">
                <h4 class="card-header">Resultado da Análise</h4>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Restrições --}}
                        {{-- <div class="col-12 col-sm-6 col-md-4 col-lg-3">
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
                        </div> --}}

                        {{-- Motivo --}}
                        {{-- @if ($restriction)
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="form-floating">
                                    <select class="form-select" id="motivo" wire:model="motivo"
                                        aria-label="Motivo">
                                        <option value="">SELECIONE</option>
                                        @if ($restriction === 'SERVIDAO')
                                            <option value="SERVIDAO">SERVIDAO</option>
                                        @endif
                                        @if ($restriction === 'LOTEAMENTO')
                                            <option value="VILLAGE">VILLAGE DO SOL</option>
                                            <option value="BANANAL">RIO BANANAL</option>
                                            <option value="SERRA">SERRA</option>
                                            <option value="DM">DOMINGOS MARTINS</option>
                                            <option value="OUTROS">OUTROS</option>
                                        @endif
                                        @if ($restriction === 'SEMMA')
                                            <option value="SERRA">SERRA</option>
                                            <option value="DM">DOMINGOS MARTINS</option>
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
                                    <label for="motivo">Motivo</label>
                                </div>
                            </div>
                        @endif --}}

                        {{-- Município --}}
                        {{-- @if ($motivo === 'OUTROS' && (!trim($note->lexp) || $note->lexp == null))
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                @if ($cities)
                                    <div class="form-floating">
                                        <select class="form-select" id="municipio" wire:model.defer="municipio">
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
                        @endif --}}

                        {{-- Reserva --}}
                        {{-- @if ($motivo === 'IEMA' || $motivo === 'ICMBIO')
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="reserva"
                                        wire:model.defer="reserva" placeholder="Reserva">
                                    <label for="reserva">Reserva</label>
                                </div>
                            </div>
                        @endif --}}

                        {{-- Botão Gerar Carta --}}
                        {{-- @if ($restriction && $motivo)
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                <button class="btn btn-primary w-100 h-100"
                                    wire:click.prevent="gerarCarta('{{ $restriction }}', '{{ $motivo }}')">
                                    Gerar Carta
                                </button>
                            </div>
                        @endif --}}

                        {{-- MMGD --}}
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="form-floating">
                                <select class="form-select" id="mmgd" wire:model.defer="mmgd">
                                    <option value="" selected>Selecione</option>
                                    <option value="SIM">SIM</option>
                                    <option value="NAO">NÃO</option>
                                </select>
                                <label for="mmgd">MMGD?</label>
                            </div>
                        </div>

                        {{-- Art.90 --}}
                        {{-- <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="form-floating">
                                <select class="form-select" id="is45" wire:model.defer="is45">
                                    <option value="" selected>Selecione</option>
                                    <option value="1">SIM</option>
                                    <option value="0">NÃO</option>
                                </select>
                                <label for="is45" class="d-flex align-items-center">
                                    Art.90 (45 dias)?
                                    <i class="ri-information-line ms-1" style="cursor: pointer;"
                                        data-bs-toggle="modal" data-bs-target="#art90Modal"></i>
                                </label>

                                <!-- Modal -->
                                <div class="modal fade" id="art90Modal" tabindex="-1"
                                    aria-labelledby="art90ModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="art90ModalLabel">Art. 90 - Lei nº
                                                    14.195/2021</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Art. 90.</strong> Nos casos enquadrados na Lei nº 14.195, de
                                                    26 de agosto de 2021, os procedimentos necessários para a obtenção
                                                    da conexão desde a solicitação até o início do fornecimento devem
                                                    ser realizados em até 45 dias.</p>

                                                <p><strong>§1º</strong> A distribuidora deve observar os seguintes
                                                    prazos, contados sucessivamente a partir da solicitação do orçamento
                                                    de conexão:</p>

                                                <ul>
                                                    <li><strong>I -</strong> até 10 dias: para a distribuidora elaborar
                                                        e fornecer ao consumidor o orçamento de conexão, entregar os
                                                        contratos e o documento ou meio para o pagamento se houver
                                                        participação financeira;</li>
                                                    <li><strong>II -</strong> até 5 dias: para o consumidor devolver
                                                        para a distribuidora os contratos e demais documentos assinados
                                                        e, caso aplicável, pagar os custos de participação financeira de
                                                        sua responsabilidade, ou pactuar com a distribuidora como será
                                                        realizado o pagamento;</li>
                                                    <li><strong>III -</strong> até 30 dias: para a distribuidora
                                                        realizar as obras de conexão, a vistoria e instalar os
                                                        equipamentos de medição nas instalações do consumidor, observado
                                                        o art. 89.</li>
                                                </ul>

                                                <p><strong>§2º</strong> Aplicam-se as disposições deste artigo às
                                                    unidades consumidoras do Grupo A, sem microgeração ou minigeração
                                                    distribuída, com as seguintes características:</p>

                                                <ul>
                                                    <li><strong>I -</strong> potência contratada de até 140 kW;</li>
                                                    <li><strong>II -</strong> localização em área urbana;</li>
                                                    <li><strong>III -</strong> distância até a rede de distribuição mais
                                                        próxima até 150 metros; e</li>
                                                    <li><strong>IV -</strong> não haja a necessidade de realização de
                                                        obras de ampliação, de reforço ou de melhoria no sistema de
                                                        distribuição de energia elétrica existente.</li>
                                                </ul>

                                                <p><strong>§3º</strong> Para as situações enquadradas neste artigo, a
                                                    distribuidora deve dispensar a aprovação prévia de projeto das
                                                    instalações de entrada de energia.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> --}}


                        {{-- Conclusão --}}
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="form-floating">
                                <select class="form-select" id="conclusion" wire:model.defer="preResult">
                                    <option value="0" selected>Selecione</option>
                                    @foreach (SelectOptions::getReverseFluxConclusion() as $option)
                                        <option value="{{ $option->value }}">{{ $option->reason }}</option>
                                    @endforeach
                                </select>
                                <label for="preresult">Tipo de Estudo</label>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="form-floating">
                                <select class="form-select" id="conclusion" wire:model.defer="conclusion">
                                    <option value="0" selected>Selecione</option>
                                    @foreach (SelectOptions::getReverseFluxEnd() as $option)
                                        <option value="{{ $option->value }}">{{ $option->reason }}</option>
                                    @endforeach
                                </select>
                                <label for="conclusion">Conclusão</label>
                            </div>
                        </div>

                        {{-- Informações --}}
                        <div class="col-12">
                            <div class="form-floating position-relative">
                                <textarea class="form-control" placeholder="Informações" id="info" style="height: 150px" wire:model.defer="info"></textarea>
                                <label for="info">Informações</label>

                            </div>
                        </div>

                        {{-- Carta --}}
                        {{-- @if ($card)
                            <div class="col-12">
                                <div class="form-floating position-relative">
                                    <textarea class="form-control" placeholder="Carta" id="card" style="height: 200px" wire:model.defer="card"></textarea>
                                    <label for="card">Carta</label>
                                    <i class="ri-file-copy-line copyButton position-absolute end-2 bottom-2"
                                        data-id="infoTextArea" style="cursor: pointer;"></i>
                                </div>
                            </div>
                        @endif --}}
                    </div>
                </div>
            </div>

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
