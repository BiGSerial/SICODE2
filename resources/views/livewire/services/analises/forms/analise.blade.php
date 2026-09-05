@php
    use App\Support\SicodeRules;
@endphp

@push('css')
    <style>
        .analysis-close-form {
            background: #eef4f5;
            padding: 1rem 0 1.25rem;
        }

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

        .analysis-close-form .card {
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .analysis-close-form .card-header {
            align-items: center;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            color: #0f172a;
            display: flex;
            font-size: 0.96rem;
            font-weight: 800;
            gap: 0.45rem;
            margin: 0;
            padding: 0.85rem 1rem;
        }

        .analysis-close-form .card-header::before {
            color: #0f766e;
            content: "\ea5c";
            font-family: remixicon;
            font-size: 1rem;
            font-weight: 400;
        }

        .analysis-close-form .card-body {
            padding: 1rem;
        }

        .analysis-close-form .note-summary-card {
            border-left: 5px solid #0f766e;
        }

        .analysis-close-form dt,
        .analysis-close-form .form-floating > label {
            color: #334155;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .analysis-close-form dd {
            color: #0f172a;
            font-size: 0.9rem;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .analysis-close-form .form-control,
        .analysis-close-form .form-select {
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px;
            min-height: 40px;
        }

        .analysis-close-form .form-control:focus,
        .analysis-close-form .form-select:focus {
            border-color: #0f766e !important;
            box-shadow: 0 0 0 0.18rem rgba(15, 118, 110, 0.14);
        }

        .analysis-close-form textarea.form-control {
            min-height: 112px;
        }

        .analysis-close-form .analysis-sequence {
            display: grid;
            gap: 0.5rem;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            margin-bottom: 1rem;
        }

        .analysis-close-form .analysis-sequence__item {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            display: flex;
            gap: 0.6rem;
            padding: 0.65rem 0.75rem;
        }

        .analysis-close-form .analysis-sequence__number {
            align-items: center;
            background: #0f766e;
            border-radius: 7px;
            color: #ffffff;
            display: inline-flex;
            flex: 0 0 28px;
            font-size: 0.78rem;
            font-weight: 900;
            height: 28px;
            justify-content: center;
            width: 28px;
        }

        .analysis-close-form .analysis-sequence__text {
            color: #0f172a;
            font-size: 0.78rem;
            font-weight: 900;
            line-height: 1.15;
            text-transform: uppercase;
        }

        .analysis-close-form .closure-parameters-grid {
            align-items: start;
        }

        .analysis-close-form .closure-parameters-grid > [class*="col-"] {
            min-width: 0;
        }

        .analysis-close-form .analysis-action-bar {
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            bottom: 0;
            box-shadow: 0 -8px 22px rgba(15, 23, 42, 0.10);
            margin-top: 1rem;
            padding: 0.75rem 0.9rem;
            position: sticky;
            z-index: 3;
        }

        .analysis-close-form .analysis-step {
            align-items: center;
            display: flex;
            gap: 0.55rem;
            min-width: min(100%, 280px);
        }

        .analysis-close-form .analysis-step__icon {
            align-items: center;
            background: #0f766e;
            border-radius: 7px;
            color: #ffffff;
            display: inline-flex;
            flex: 0 0 34px;
            font-size: 1rem;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .analysis-close-form .analysis-step__eyebrow {
            color: #0f766e;
            font-size: 0.64rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .analysis-close-form .analysis-step__text {
            color: #0f172a;
            font-size: 0.86rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .analysis-close-form .analysis-action-bar .btn {
            align-items: center;
            border-radius: 6px;
            display: inline-flex;
            font-weight: 800;
            gap: 0.35rem;
            justify-content: center;
            min-height: 36px;
            min-width: 104px;
        }

        @media (max-width: 767.98px) {
            .analysis-close-form .analysis-action-bar {
                position: static;
            }

            .analysis-close-form .analysis-action-bar .btn {
                width: 100%;
            }
        }
    </style>
@endpush

<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    @if ($view_form)
        <div class="analysis-close-form">
        <div class="container">
            {{-- ======= Card: Informações da Nota ======= --}}
            <div class="card note-summary-card mb-4">
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
                <h4 class="card-header">Parâmetros de Encerramento</h4>
                <div class="card-body">
                    <div class="analysis-sequence">
                        <div class="analysis-sequence__item">
                            <span class="analysis-sequence__number">1</span>
                            <span class="analysis-sequence__text">Classificação</span>
                        </div>
                        <div class="analysis-sequence__item">
                            <span class="analysis-sequence__number">2</span>
                            <span class="analysis-sequence__text">Resultado e parecer</span>
                        </div>
                        <div class="analysis-sequence__item">
                            <span class="analysis-sequence__number">3</span>
                            <span class="analysis-sequence__text">Confirmação</span>
                        </div>
                    </div>

                    <div class="row g-3 closure-parameters-grid">
                        {{-- Restrições --}}
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
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

                        {{-- Motivo --}}
                        @if ($restriction && !(SicodeRules::analysisEnvironmentWithoutReason() && $restriction === 'AMBIENTE'))
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
                        @endif

                        {{-- Município --}}
                        @if ($motivo === 'OUTROS' && (!trim($note->lexp) || $note->lexp == null))
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
                        @endif

                        {{-- Reserva --}}
                        @if ($motivo === 'IEMA' || $motivo === 'ICMBIO')
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="reserva"
                                        wire:model.defer="reserva" placeholder="Reserva">
                                    <label for="reserva">Reserva</label>
                                </div>
                            </div>
                        @endif

                        {{-- Botão Gerar Carta --}}
                        @if ($restriction && $motivo)
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                <button class="btn btn-primary w-100 h-100"
                                    wire:click.prevent="gerarCarta('{{ $restriction }}', '{{ $motivo }}')">
                                    Gerar Carta
                                </button>
                            </div>
                        @endif

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
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
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
                        </div>

                        {{-- Conclusão --}}
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="form-floating">
                                <select class="form-select" id="conclusion" wire:model="conclusion">
                                    <option value="0" selected>Selecione</option>
                                    @foreach (SicodeRules::analysisConclusionOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label for="conclusion">Conclusão</label>
                            </div>
                        </div>

                        {{-- Informações --}}
                        <div class="col-12">
                            <div class="form-floating position-relative">
                                <textarea class="form-control" placeholder="Informações" id="info" style="height: 150px"
                                    wire:model.defer="info"></textarea>
                                <label for="info">Informações</label>
                                <i class="ri-file-copy-line copyButton position-absolute end-2 bottom-2"
                                    data-id="infoTextArea2" style="cursor: pointer;"></i>
                            </div>
                        </div>

                        {{-- Carta --}}
                        @if ($card)
                            <div class="col-12">
                                <div class="form-floating position-relative">
                                    <textarea class="form-control" placeholder="Carta" id="card" style="height: 200px" wire:model.defer="card"></textarea>
                                    <label for="card">Carta</label>
                                    <i class="ri-file-copy-line copyButton position-absolute end-2 bottom-2"
                                        data-id="infoTextArea" style="cursor: pointer;"></i>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ======= Botões de Ação ======= --}}
            <div class="analysis-action-bar d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                <div class="analysis-step">
                    <span class="analysis-step__icon"><i class="ri-checkbox-circle-line"></i></span>
                    <div>
                        <div class="analysis-step__eyebrow">Encerramento</div>
                        <div class="analysis-step__text">Revise os parâmetros antes de finalizar a análise.</div>
                    </div>
                </div>
                <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                    <button class="btn btn-primary" wire:click.prevent="save_info">
                        <i class="ri-save-3-line"></i> SALVAR
                    </button>
                    <button class="btn btn-warning" wire:click.prevent="to_pause">
                        <i class="ri-pause-circle-line"></i> PAUSAR
                    </button>
                    <button class="btn btn-success" wire:click.prevent="to_finish({{ $analise->production_id }})">
                        <i class="ri-check-double-line"></i> ENCERRAR
                    </button>
                </div>
            </div>
        </div> {{-- fim container --}}
        </div>
    @else
        <div class="loading-overlay">
            <div class="loading-message">
                <h1>Carregando Dados...</h1>
            </div>
        </div>
    @endif
</div>
