<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}" type="image/x-icon">
    <title>CheckList Fiscalização de Obras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 7px;
            /* Reduzi um pouco a fonte */
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background: white;
        }

        .container-pg {
            width: 21cm;
            height: 29.7cm;
            padding: 0.5cm;
            /* Reduzi as margens */
            margin: auto;
            box-sizing: border-box;
            background: white;
        }

        .dotted-line {
            display: inline-block;
            border-bottom: 1px dashed #000;
            width: 70%;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 3px;
            /* Reduzi a margem inferior */
        }

        .logo img {
            max-width: 120px;
            max-height: 43px;
            /* Reduzi o tamanho do logo */
            height: auto;
        }

        .card {
            border: 1px solid #ddd;
            margin-bottom: 3px;
            /* Reduzi a margem inferior */
            padding: 3px;
            /* Reduzi o padding */
            background: #f8f9fa;
        }

        .card-header {
            background-color: #0d6efd;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 2px;
            /* Reduzi o padding */
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 3px;
            /* Reduzi o gap */
        }

        .grid-item {
            padding: 3px;
            /* Reduzi o padding */
            border: 1px solid #ddd;
            background: #f8f9fa;
            box-sizing: border-box;
        }

        .checkbox-group {
            display: flex;
            flex-direction: column;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
            /* Removi a margem inferior dos labels */
        }

        .checkbox-group input {
            margin-right: 3px;
            /* Reduzi a margem direita */
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 2px;
            /* Reduzi o padding */
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 7px;
            /* Reduzi a fonte */
            margin-bottom: 1px;
            /*reduzi o espaço abaixo dos inputs*/
        }

        .row {
            margin-right: 0;
            margin-left: 0;
        }

        [class*="col-"] {
            padding-right: 0;
            padding-left: 0;
        }

        @media print {
            body {
                font-size: 10px;
                margin: 0;
                -webkit-print-color-adjust: exact;
            }

            .container-pg {
                width: 21cm;
                height: 29.7cm;
                padding: 0.5cm;
            }

            .card {
                border: 1px solid #aaa;
            }

            #btnImprimir {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="container-pg">
        <div class="header">
            <div class="logo">
                @if (isset(Auth()->User()->Company->img_w_path) && !empty(Auth()->User()->Company->img_w_path))
                    <img src="{{ asset('storage/' . Auth()->User()->Company->img_w_path) }}" alt="Logo">
                @else
                    <h4 class="fw-bold align-middle">{{ mb_strToUpper(explode(' ', Auth()->User()->Company->name)[0]) }}
                    </h4>
                @endif
            </div>
            <div>
                <h2 class="fw-bold align-middle" style="margin: 0; font-size: 18px;">CHECK LIST PARA FISCALIZAÇÂO DE
                    OBRAS
                </h2>
            </div>
            <div>
                <h4 class="my-0 py-0 ">{{ date('m/Y') }}</h4>
                <p class="my-0 py-0 text-end">Rev-202502.01</p>
            </div>
        </div>

        <div class="card">
            <div class="row">
                <div class="col-3 mb-1">
                    <label class="form-label small">DATA FISCALIZAÇÃO:</label>
                    <div class="form-control form-control-sm p-0 h-auto" style="border-bottom: 1px solid #ccc;"></div>
                </div>
                <div class="col-3 mb-1">
                    <label class="form-label small">HORA INÍCIO:</label>
                    <div class="form-control form-control-sm p-0 h-auto" style="border-bottom: 1px solid #ccc;"></div>
                </div>
                <div class="col-3 mb-1">
                    <label class="form-label small">HORA FIM:</label>
                    <div class="form-control form-control-sm p-0 h-auto" style="border-bottom: 1px solid #ccc;">
                    </div>
                </div>
                <div class="col-3 mb-1">
                    <label class="form-label small">PARCEIRA:</label>
                    <div class="form-control form-control-sm p-0 h-auto" style="border-bottom: 1px solid #ccc;">
                        {{ $dados->company ? $dados->company : Auth()->User()->Company->name }}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-3 mb-1">
                    <label class="form-label small">NOTA/OV:</label>
                    <div class="form-control form-control-sm p-0 h-auto fw-bold text-center align-middle"
                        style="border-bottom: 1px solid #ccc;">
                        <span class="align-middle">{{ $dados->note }}</span>
                    </div>
                </div>
                <div class="col-3 mb-1">
                    <label class="form-label small">MUNICÍPIO:</label>
                    <div class="form-control form-control-sm p-0 h-auto text-center"
                        style="border-bottom: 1px solid #ccc;">
                        {{ $dados->lexp }}</div>
                </div>
                <div class="col-6 mb-1">
                    <label class="form-label small">FISCAL:</label>
                    <div class="form-control form-control-sm p-0 h-auto text-center"
                        style="border-bottom: 1px solid #ccc;">{{ Auth()->User()->name }}</div>
                </div>
            </div>


        </div>
        <div class="grid-item">

            <div class="card-header">CHECKLIST</div>

            <div class="table-responsive">
                <table class="table table-sm table-condensed">
                    <thead>
                        <tr class="text-center">
                            <th style="width:5%;">ITEM</th>
                            <th style="width:80%;">DESCRIÇÃO</th>
                            <th style="width:5%;">SIM</th>
                            <th style="width:5%;">NÃO</th>
                            <th style="width:5%;">N/A</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-primary">
                            <td class="text-center">1</td>
                            <td>FISCALIZAÇÃO NO MOMENTO DA OBRA</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="text-center">1.1</td>
                            <td>Local próprio para estacionamento do veículo?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">1.2</td>
                            <td>Local sinalizado corretamente?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">1.3</td>
                            <td>Realizado Análise Preliminar de Risco?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">1.4</td>
                            <td>Realizado Diálogo de Segurança com a Equipe?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">1.5</td>
                            <td>Intemperes, topografia e terceiros impedindo a execução da Obra?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">1.6</td>
                            <td>Projeto (desenho) e Atestado Distribuição de Serviço (ADS)</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">1.7</td>
                            <td>Ferramental, EPIs e EPCs necessários pare execução do serviço?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">1.8</td>
                            <td>Materiais suficientes para executar a obra?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">1.9</td>
                            <td>O número do equipamento está de acordo com o projeto?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">1.10</td>
                            <td>A nota de desligamento atende os serviços?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">1.11</td>
                            <td>Circuito testado e aterrado?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">1.12</td>
                            <td>Conferência da ATLV (bloqueio do circuito).</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">1.13</td>
                            <td>Encarregado presente no local das atividades?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>

                        <tr class="table-primary">
                            <td class="text-center">2</td>
                            <td>INSTALAÇÃO E DESATIVAÇÃO DE POSTE</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        <tr>
                            <td class="text-center">2.1</td>
                            <td>Poste está de acordo com o projeto?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">2.2</td>
                            <td>Execução da cava e compactação está conforme instrução de trabalho?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">2.3</td>
                            <td>O Poste está aprumado?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">2.4</td>
                            <td>Afastamentos mínimos de segurança obedecidos?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">2.5</td>
                            <td>Foi desativado o poste?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">2.6</td>
                            <td>Necessário apoio de uso mútuo (telefonia)?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">2.7</td>
                            <td>Necessário apoio (poste palhaço) para retirada de poste?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">2.8</td>
                            <td>Refazer calcada?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">2.9</td>
                            <td>Poste apresenta trincas de má fabricação?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">2.10</td>
                            <td>Poste engastado até a marca de indicação?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">2.11</td>
                            <td>Poste (DT) está em angulo e posição correta em relação aos condutores?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">2.12</td>
                            <td>Calço de rodas sendo utilizado?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">2.13</td>
                            <td>Cobertura e mantas instaladas nos condutores?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>

                        <tr class="table-primary">
                            <td class="text-center">3</td>
                            <td>INSTALAÇÃO DE ESTAI</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        <tr>
                            <td class="text-center">3.1</td>
                            <td>A distância entre o poste e a ancora do estai, esta conforme o padrão construtivo?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">3.2</td>
                            <td>A cava, cachimbo e angulo da haste do estai, estão conforme o padrão construtivo?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">3.3</td>
                            <td>A posição do bloco de estai na cava, esta conforme o padrão construtivo?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">3.4</td>
                            <td>A compactação da cava do estai, está conforme o padrão construtivo? (compactar a terra a
                                cada 20 cm)?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">3.5</td>
                            <td>A cordoalha foi tensionada e aterrada de acordo com o padrão construtivo?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">3.6</td>
                            <td>Localização do estai executados conforme projeto?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>

                        <tr class="table-primary">
                            <td class="text-center">4</td>
                            <td>VEGETEÇÃO</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        <tr>
                            <td class="text-center">4.1</td>
                            <td>A distância entre as árvores e a rede elétrica está de acordo com as normas de
                                segurança?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">4.2</td>
                            <td>A rede foi construída conforme projeto, respeitando faixa entre a rede e vegetação
                                local?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>

                        <tr class="table-primary">
                            <td class="text-center">5</td>
                            <td>INSTALAÇÃO E DESATIVAÇÃO DEESTRUTURAS</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        <tr>
                            <td class="text-center">5.1</td>
                            <td>Nivelamento da estrutura e posicionamento no paste (Bissetriz)?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">5.2</td>
                            <td>Ferragens de acordo com a estrutura?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">5.3</td>
                            <td>Afastamentos mínimos de segurança obedecidos?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">5.4</td>
                            <td>Recolhido a sucata das estruturas?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>

                        <tr class="table-primary">
                            <td class="text-center">6</td>
                            <td>INSTALAÇÃO E DESATIVAÇÃO DOS CONDUTORES</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        <tr>
                            <td class="text-center">6.1</td>
                            <td>Tipo de cabos estão de acordo com o projeto?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">6.2</td>
                            <td>Os cabos estão nivelados e tensionados corretamente?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">6.3</td>
                            <td>Encabeçamento e amarração executados corretamente?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">6.4</td>
                            <td>instalação dosisoladores e espaçadores corretamente?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">6.5</td>
                            <td>Afastamentos mínimos de segurança obedecidos?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">6.6</td>
                            <td>Recolhido assucatas dos cabos?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">6.7</td>
                            <td>Isolamento dasconexões e emendas de acordo com a instrução de trabalho?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>

                        <tr class="table-primary">
                            <td class="text-center">7</td>
                            <td>INSTALAÇÃO E DESATIVAÇÃO DE EQUIPAMENTOS</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        <tr>
                            <td class="text-center">7.1</td>
                            <td>Equipamento de acordo com o projeto?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">7.2</td>
                            <td>A montagem da estrutura esta conforme o padrão construtivo?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">7.3</td>
                            <td>Foi instalado o para raio e aterramento do equipamento?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">7.4</td>
                            <td>Jumpers de acordo com o orçamento da obra e conexões instaladas corretamente?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">7.5</td>
                            <td>O equipamento instalado ou desativado foi informado ao COD a situação (Fora de operação
                                ou em Operação).</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">7.6</td>
                            <td>Feito medição na rede e medição do aterramento?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">7.7</td>
                            <td>Instalada placa com número do Equipamento?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">7.8</td>
                            <td>Afastamentos mínimos de segurança obedecidos?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">7.9</td>
                            <td>Recolhido equipamento desativado?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">7.10</td>
                            <td>Equipamento desativado apresentava vazamentos?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>

                        <tr class="table-primary">
                            <td class="text-center">8</td>
                            <td>DIVERSOS</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        <tr>
                            <td class="text-center">8.1</td>
                            <td>Feito o teste de sequência de fase (em interligações), independentemente da Posição da
                                chave (NIA ou N/F)?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">8.2</td>
                            <td>Aterramento de cerca executado?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">8.3</td>
                            <td>Feito retirada dosresíduos e limpeza no local dosserviços?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">8.4</td>
                            <td>Ramais dos clientes conectados na rede?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">8.5</td>
                            <td>Eletricistas e ajudantes se comportaram corretamente interno e com terceiros?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr>
                            <td class="text-center">8.6</td>
                            <td>Preenchido o ADS com os Materiais utilizados?</td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                            <td class="text-center"><input type="checkbox"></td>
                        </tr>
                        <tr class="fs-6 fw-bold table-primary">
                            <td></td>
                            <td class="text-center">DESCREVER O MOTIVO DA NAO CONFORMIDADE NO CAMPO OBSERVAÇÕES
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>

                        </tr>

                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        <div class="card">
            <div class="card-header">OBSERVAÇÕES</div>
            <textarea rows="100"></textarea>
        </div>

        <div class="card">
            <div class="row align-items-center">
                <div class="col-2 mb-1">
                    <label class="form-label small">DATA:</label>
                    <div class="form-control form-control-sm p-0 h-auto" style="border-bottom: 1px solid #ccc;">
                    </div>
                </div>
                <div class="col-6 mb-1">
                    <label class="form-label small">NOME/MATRICULA:</label>
                    <div class="form-control form-control-sm p-0 h-auto text-center"
                        style="border-bottom: 1px solid #ccc;">{{ Auth()->User()->name }}
                        [{{ Auth()->User()->Registration }}]</div>
                </div>

                <div class="col-4 mb-1">
                    <label class="form-label small">ASSINATURA:</label>
                    <div class="form-control form-control-sm p-0 h-auto text-center"
                        style="border-bottom: 1px solid #ccc;">
                    </div>
                </div>
            </div>
        </div>


    </div>



</body>

</html>
