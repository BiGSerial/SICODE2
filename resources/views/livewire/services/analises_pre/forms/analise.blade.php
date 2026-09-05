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
        .analysis-close-form label,
        .analysis-close-form .col-form-label {
            color: #334155;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            margin-bottom: 0.35rem;
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
            <div class="card note-summary-card">
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


            <div class="mb-3 col-3">
                <label for="inputPassword" class="col-sm-12 col-form-label">Tipo de Serviço:</label>
                <select class="form-select border border-secondary" aria-label="Default select example"
                    wire:model="service_type">
                    <option value="" selected>Selecione</option>
                    <option value="RR">Alteração de Carga</option>
                    <option value="RR">Ligação Existente</option>
                    <option value="RR">Ligação Nova</option>
                    <option value="RR">Ligação Provisória</option>
                    <option value="IP">Iluminação Pública</option>
                    <option value="ER">Extensão de Rede</option>
                    <option value="RR">Remoção de Rede</option>
                </select>
            </div>

            @if ($service_type === 'ER')
                <div class="card">
                    <h4 class="card-header">Informação do Comprador</h4>
                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-3">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Comprador:</label>
                                <div class="col-sm-12">
                                    <input type="text" class="form-control border border-secondary"
                                        wire:model.defer="comprador">
                                </div>
                            </div>
                            <div class="mb-3 col-6">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Endereço:</label>
                                <div class="col-sm-12">
                                    <input type="text" class="form-control border border-secondary"
                                        wire:model.defer="endereco">
                                </div>
                            </div>
                            <div class="mb-3 col-2">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Matrícula:
                                    :</label>
                                <div class="col-sm-12">
                                    <input type="text" class="form-control border border-secondary"
                                        wire:model.defer="matricula">
                                </div>
                            </div>
                            <div class="mb-3 col-2">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Area²:
                                </label>
                                <div class="col-sm-12">
                                    <input type="text" class="form-control border border-secondary"
                                        wire:model.defer="area">
                                </div>
                            </div>
                            <div class="mb-3 col-3">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Documento Analisado:
                                </label>
                                <div class="col-sm-12">
                                    <input type="text" class="form-control border border-secondary"
                                        wire:model.defer="documento">
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            @endif


            @if ($service_type)
                <div class="card">
                    <h4 class="card-header">Informação de Analise</h4>
                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-3">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Número de
                                    Instalação:</label>
                                <div class="col-sm-12">
                                    <input type="text" class="form-control border border-secondary"
                                        wire:model.defer="ninst">
                                </div>
                            </div>
                            <div class="mb-3 col-3">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Número do Medidor ou Inst.
                                    Vizinha:</label>
                                <div class="col-sm-12">
                                    <input type="text" class="form-control border border-secondary"
                                        wire:model.defer="nmedidor">
                                </div>
                            </div>
                            <div class="mb-3 col-2">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Alimentador:
                                    :</label>
                                <div class="col-sm-12">
                                    <input type="text" class="form-control border border-secondary"
                                        wire:model.defer="alimentador">
                                </div>
                            </div>
                            <div class="mb-3 col-2">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Latitude:
                                </label>
                                <div class="col-sm-12">
                                    <input type="text" class="form-control border border-secondary"
                                        wire:model.defer="lat">
                                </div>
                            </div>
                            <div class="mb-3 col-2">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Longitude:
                                </label>
                                <div class="col-sm-12">
                                    <input type="text" class="form-control border border-secondary"
                                        wire:model.defer="lon">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            @endif




            {{-- <div class="card">
                <h4 class="card-header">Informação de Carga</h4>
                <div class="card-body">
                    <div class="row">
                        <div class="mb-3 col-6">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Carregamento Inicial:
                                (%)</label>
                            <div class="col-sm-12">
                                <input type="text" class="form-control border border-secondary"
                                    wire:model.defer="carga_ini">
                            </div>
                        </div>
                        <div class="mb-3 col-6">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Carregamento Final:
                                (%):</label>
                            <div class="col-sm-12">
                                <input type="text" class="form-control border border-secondary"
                                    wire:model.defer="carga_fim">
                            </div>
                        </div>
                        <div class="mb-3 col-3">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Queda:
                                (%):</label>
                            <div class="col-sm-12">
                                <input type="text" class="form-control border border-secondary"
                                    wire:model.defer="queda">
                            </div>
                        </div>
                        <div class="mb-3 col-3">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Queda Max:
                                (%):</label>
                            <div class="col-sm-12">
                                <input type="text" class="form-control border border-secondary"
                                    wire:model.defer="queda_max">
                            </div>
                        </div>
                        <div class="mb-3 col-3">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Queda Cliente:
                                (%):</label>
                            <div class="col-sm-12">
                                <input type="text" class="form-control border border-secondary"
                                    wire:model.defer="queda_cliente">
                            </div>
                        </div>
                        <div class="mb-3 col-3">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Numero de Vãos:
                                (qtd):</label>
                            <div class="col-sm-12">
                                <input type="text" class="form-control border border-secondary"
                                    wire:model.defer="vao">
                            </div>
                        </div>
                    </div>

                </div>
            </div> --}}

            @if ($service_type)
                <div class="card">
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

                        <div class="row closure-parameters-grid">
                            <div class="mb-3 col-3">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Restrições:</label>
                                <select class="form-select border border-secondary"
                                    aria-label="Default select example" wire:model="restriction">
                                    <option value="" selected>SEM RESTRIÇÃO</option>
                                    <option value="DOCUMENTACAO">DOCUMENTAÇÃO</option>
                                    <option value="AUT_PASSAGEM">AUTORIZAÇÃO DE PASSAGEM</option>
                                    <option value="DESISTENCIA">DESISTÊNCIA</option>
                                    <option value="ESTIMADO">ESTIMADO</option>
                                    <option value="DUPLICIDADE">DUPLICIDADE</option>
                                    <option value="MANUTENCAO">MANUTENÇÃO</option>
                                    <option value="PEDIDO_ERRO">ERRO NO PEDIDO</option>
                                    <option value="IP">ILUMINACAO PÚBLICA</option>
                                    <option value="SERVIDAO">FAIXA DE SERVIDÃO</option>
                                    <option value="FUNAI">FUNAI</option>
                                    <option value="LOTEAMENTO">LOTEAMENTO CLANDESTINO</option>
                                    <option value="AMBIENTE">MEIO AMBIENTE</option>
                                    <option value="SEMMA">SEMMA</option>
                                    <option value="OUTROS">OUTROS</option>

                                </select>
                            </div>

                            @if ($restriction && !(SicodeRules::analysisEnvironmentWithoutReason() && $restriction === 'AMBIENTE'))
                                <div class="mb-3 col-3">
                                    <label for="inputPassword" class="col-sm-12 col-form-label">MOTIVO:</label>
                                    <select class="form-select border border-secondary"
                                        aria-label="Default select example" wire:model="motivo">
                                        <option value="" selected>SELECIONE</option>

                                        {{-- SERVIDAO --}}
                                        @if ($restriction === 'DOCUMENTACAO')
                                            <option value="CCIR">FALTA CCIR</option>
                                            <option value="IPTU">FALTA IPTU</option>
                                            <option value="CAR">FALTA CAR</option>
                                            <option value="ESCRITURA">ESCRITURA / DOCUMENTO</option>
                                            <option value="OUTROS">OUTROS</option>
                                        @endif

                                        {{-- LOTEAMENTO --}}
                                        @if ($restriction === 'AUT_PASSAGEM')
                                            <option value="FALTA_AUT_PASSAGEM">FALTA AUTORIZAÇÃO DE PASSAGEM
                                            </option>
                                        @endif

                                        {{-- SEMMA --}}
                                        @if ($restriction === 'DESISTENCIA')
                                            <option value="DESISTENCIA DO CLIENTE">DESISTÊNCIA DO CLIENTE</option>
                                        @endif

                                        {{-- SEMMA --}}
                                        @if ($restriction === 'ESTIMADO')
                                            <option value="CUSTO ESTIMADO">CUSTO ESTIMADO</option>
                                        @endif

                                        {{-- SEMMA --}}
                                        @if ($restriction === 'DUPLICIDADE')
                                            <option value="ATENDIMENTO EM OUTRA OV">ATENDIMENTO EM OUTRA OV
                                            </option>
                                        @endif

                                        @if ($restriction === 'MANUTENCAO')
                                            <option value="TRAFO FURTADO">TRAFO FURTADO</option>
                                            <option value="REMOCAO DE POSTE">REMOÇÃO DE POSTE</option>
                                            <option value="REMOCAO DE RAMAL">REMOÇÃO DE RAMAL</option>
                                        @endif

                                        @if ($restriction === 'PEDIDO_ERRO')
                                            <option value="SOLICITAR LIGACAO NOVA">SOLICITAR LIGAÇÃO NOVA</option>
                                            <option value="SOLICITAR ALTERACAO DE CARGA">SOLICITAR ALTERACAO DE
                                                CARGA</option>
                                            <option value="SOLICITAR REMOCAO">SOLICITAR REMOCAO</option>
                                            <option value="SOLICITAR EXTENSAO">SOLICITAR EXTENSAO</option>
                                            <option value="CARGA DECLARADA ERRADA">CARGA DECLARADA ERRADA</option>
                                            <option value="PEDIDO REMOCAO POSTE PADRAO">PEDIDO REMOCAO POSTE PADRAO
                                            </option>
                                            <option value="ENDERECO DIVERGENTE">ENDERECO DIVERGENTE</option>
                                            <option value="RELOCACAO DE CABOS E OU POSTE TELEFONE">RELOCACAO DE
                                                CABOS E OU POSTE TELEFONE</option>
                                        @endif
                                        @if ($restriction === 'IP')
                                            <option value="ATUALIZACAO CADASTRAL">ATUALIZACAO CADASTRAL</option>
                                        @endif

                                        @if ($restriction === 'OUTROS')
                                            <option value="CLOCALIZADO">CLIENTE NÃO LOCALIZADO
                                            </option>

                                            <option value="IMOVEL SEM SEPARACAO FISICA">IMOVEL SEM SEPARACAO FISICA
                                            </option>
                                            <option value="ATENDIMENTO POR OUTRA CONCESSIONARIA">ATENDIMENTO POR
                                                OUTRA CONCESSIONÁRIA</option>
                                        @endif

                                        {{-- SERVIDAO --}}
                                        @if ($restriction === 'SERVIDAO')
                                            <option value="">SERVIDAO</option>
                                        @endif

                                        {{-- LOTEAMENTO --}}
                                        @if ($restriction === 'LOTEAMENTO')
                                            <option value="VILLAGE">VILLAGE DO SOL</option>
                                            <option value="BANANAL">RIO BANANAL</option>
                                            <option value="SERRA">SERRA</option>
                                            <option value="DM">DOMINGOS MARITNS</option>
                                            <option value="OUTROS">OUTROS</option>
                                        @endif

                                        {{-- SEMMA --}}
                                        @if ($restriction === 'SEMMA')
                                            <option value="SERRA">SERRA</option>
                                            <option value="DM">DOMINGOS MARITNS</option>
                                            <option value="OUTROS">OUTROS</option>
                                        @endif

                                        {{-- SEMMA --}}
                                        @if ($restriction === 'FUNAI')
                                            <option value="FUNAI">FUNAI</option>
                                        @endif

                                        {{-- SEMMA --}}
                                        @if ($restriction === 'AMBIENTE')
                                            <option value="IEMA">IEMA</option>
                                            <option value="ICMBIO">ICMBIO</option>
                                        @endif

                                    </select>
                                </div>
                            @endif

                            @if ($motivo === 'OUTROS' && !isset($note->lexp))
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
                                        wire:click.prevent="gerarCarta()">Gerar
                                        Carta</button>
                                @endif
                            </div>

                            <div class="mb-3 col-3">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Conclusão:</label>
                                <select class="form-select border border-secondary"
                                    aria-label="Default select example" wire:model="conclusion">
                                    <option value="0" selected>Selecione</option>
                                    @foreach (SicodeRules::preAnalysisConclusionOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>



                            <div class="mb-3">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Informações: <span
                                        class="fw-bold"><i class="ri-file-copy-line copyButton"
                                            data-id="infoTextArea2" style="cursor: pointer;"></i></span></label>
                                <textarea id="infoTextArea2" class="form-control border border-secondary" rows="8" wire:model.defer="info"></textarea>
                            </div>

                            @if ($card)
                                <div class="mb-3">
                                    <label for="inputPassword" class="col-sm-12 col-form-label">Carta: <span
                                            class="fw-bold"><i class="ri-file-copy-line copyButton"
                                                data-id="infoTextArea" style="cursor: pointer;"></i></span></label>
                                    <textarea id="infoTextArea" class="form-control border border-secondary" rows="15" wire:model.defer="card"></textarea>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            @endif

            <div class="analysis-action-bar d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                <div class="analysis-step">
                    <span class="analysis-step__icon"><i class="ri-checkbox-circle-line"></i></span>
                    <div>
                        <div class="analysis-step__eyebrow">Encerramento</div>
                        <div class="analysis-step__text">Revise os parâmetros antes de finalizar a pré-análise.</div>
                    </div>
                </div>
                <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                    <button class="btn btn-primary" wire:click.prevent="save_info">
                        <i class="ri-save-3-line"></i> SALVAR
                    </button>
                    <button class="btn btn-warning" wire:click.prevent="to_pause">
                        <i class="ri-pause-circle-line"></i> PAUSAR
                    </button>
                    <button class="btn btn-success"
                        wire:click.prevent="to_finish({{ $analise->production_id }})">
                        <i class="ri-check-double-line"></i> ENCERRAR
                    </button>
                </div>

            </div>
        </div>
        </div>
        @else
            <div class="loading-overlay">
                <div class="loading-message">
                    <h1>Carregando Dados...</h1>
                </div>
            </div>
    @endif

</div>
