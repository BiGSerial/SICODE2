<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}" type="image/x-icon">
    <title>Ficha Viabilidade Técnica de Execução de Obras</title>
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
                font-size: 7px;
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
                @if (isset($viability->Company->img_w_path) && !empty($viability->Company->img_w_path))
                    <img src="{{ asset('storage/' . $viability->Company->img_w_path) }}" alt="Logo">
                @elseif (isset(Auth()->User()->Company->img_w_path) && !empty(Auth()->User()->Company->img_w_path))
                    <img src="{{ asset('storage/' . Auth()->User()->Company->img_w_path) }}" alt="Logo">
                @else
                    <h4 class="fw-bold align-middle">{{ mb_strToUpper(explode(' ', Auth()->User()->Company->name)[0]) }}
                    </h4>
                @endif
            </div>
            <div>
                <h2 class="fw-bold align-middle" style="margin: 0; font-size: 18px;">FICHA VIABILIDADE TÉCNICA DE
                    EXECUÇÃO DE OBRAS
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
                    <label class="form-label small">DATA EXECUÇÃO:</label>
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
                <div class="col-3 mb-1">
                    <label class="form-label small">CIRCUITO:</label>
                    <div class="form-control form-control-sm p-0 h-auto" style="border-bottom: 1px solid #ccc;"></div>
                </div>
                <div class="col-3 mb-1">
                    <label class="form-label small">EQUIPAMENTO QUE ISOLA O TRECHO:</label>
                    <div class="form-control form-control-sm p-0 h-auto" style="border-bottom: 1px solid #ccc;"></div>
                </div>
            </div>

            <div class="row">
                <div class="col-8 mb-1">
                    <label class="form-label small">ENDEREÇO:</label>
                    <div class="form-control form-control-sm p-0 h-auto" style="border-bottom: 1px solid #ccc;"></div>
                </div>
                <div class="col-4 mb-1">
                    <label class="form-label small">PRAZO PARA RETORNO VIABILIDADE:</label>
                    <div class="form-control form-control-sm p-0 h-auto fw-bold text-center text-danger"
                        style="border-bottom: 1px solid #ccc;">{{ $dados->prazo }}</div>
                </div>
            </div>
        </div>

        <div class="grid-container">
            <div class="grid-item">
                <div class="card-header">FLYNG TAPE/CRUZAMENTO AÉREO</div>
                <div class="row mt-2">
                    <div class="col-6">
                        <div class="checkbox-group">
                            <label><input type="checkbox"> NÃO</label>
                            <label><input type="checkbox"> SIM</label>

                        </div>
                    </div>
                    <div class="col-6">
                        <div class="checkbox-group">

                            <label><input type="checkbox"> BT</label>
                            <label><input type="checkbox"> MT</label>
                            <label><input type="checkbox"> AT</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card-header">EQUIPES NECESSÁRIAS</div>
                <div class="checkbox-group mt-2">
                    <label><input type="checkbox"> LINHA VIVA</label>
                    <label><input type="checkbox"> LINHA MORTA</label>
                    <label><input type="checkbox"> EQUIPE MULTI</label>
                </div>
            </div>

            <div class="grid-item">
                <div class="card-header">TIPO DE OBRA</div>
                <div class="row mt-2">
                    <div class="col-6">
                        <div class="checkbox-group">
                            <label><input type="checkbox"> LIVRE</label>
                            <label><input type="checkbox"> MANOBRA</label>
                            <label><input type="checkbox"> DESLIGAMENTO</label>

                        </div>
                    </div>
                    <div class="col-6">
                        <div class="checkbox-group">

                            <label><input type="checkbox"> LINHA VIVA</label>
                            <label><input type="checkbox"> INTERLIGAÇÃO</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Abrir Rede -->
            <div class="grid-item">

                <div class="card-header">ABRIR PONTO DE REDE MT/REDE SECUNDÁRIA?</div>
                <div class="card-body">
                    <p class="mb-0">Existe Rede BT alimentando outro equipamento no trecho?</p>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="projeto" value="nao"> NÃO</label>
                        <label><input type="checkbox" name="projeto" value="sim"> SIM</label>
                    </div>
                    <div class="form-group">
                        <label for="telefone_acesso">Nº ET:</label>
                        <input type="text" name="telefone_acesso" id="telefone_acesso">
                    </div>
                </div>

            </div>
            <div class="grid-item">

                <div class="card-header">COMPARTILHAMENTOS (USO MÚTUO)</div>
                <div class="card-body pt-1">

                    <div class="checkbox-group">
                        <label><input type="checkbox" name="compartilhamentos" value="encabecamento">
                            ENCABEÇAMENTO</label>
                        <label><input type="checkbox" name="compartilhamentos" value="travessa_rede"> TRAVESSIA
                            DE REDE</label>
                        <label><input type="checkbox" name="compartilhamentos" value="tangente"> TANGENTE</label>
                        <label><input type="checkbox" name="compartilhamentos" value="implantar_poste"> IMPLANTAR
                            POSTE</label>
                    </div>
                </div>

            </div>

            <div class="grid-item">

                <div class="card-header">MELHOR DIA DA SEMANA</div>
                <div class="card-body pt-1">

                    <div class="checkbox-group">
                        <label><input type="checkbox" name="melhor_dia" value="qualquer_dia"> QUALQUER
                            DIA</label>
                        <label><input type="checkbox" name="melhor_dia" value="sabado"> SÁBADO</label>
                        <label><input type="checkbox" name="melhor_dia" value="domingo"> DOMINGO</label>
                        <label><input type="checkbox" name="melhor_dia" value="segunda_sexta"> SEGUNDA A
                            SEXTA</label>
                    </div>
                </div>

            </div>

            <div class="grid-item">

                <div class="card-header">ABRIR PONTO DE REDE BT?</div>
                <div class="card-body pt-1">

                    <div class="checkbox-group">
                        <label><input type="checkbox" name="projeto" value="nao"> NÃO</label>
                        <label><input type="checkbox" name="projeto" value="sim"> SIM</label>
                    </div>
                    <p class="my-1">Indicar no Croqui</p>
                </div>

            </div>

            <div class="grid-item">

                <div class="card-header">APOIO ORGÃOS PÚBLICOS</div>
                <div class="card-body pt-1">

                    <div class="row gx-1">
                        <div class="col-6">
                            <div class="checkbox-group">
                                <label><input type="checkbox" name="apoio_orgaos" value="companhia_agua"> CIA DE
                                    ÁGUA</label>
                                <label><input type="checkbox" name="apoio_orgaos" value="companhia_gas"> CIA DE
                                    GÁS</label>
                                <label><input type="checkbox" name="apoio_orgaos" value="prefeitura">
                                    PREFEITURA</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="checkbox-group">
                                <label><input type="checkbox" name="apoio_orgaos" value="der"> DER</label>
                                <label><input type="checkbox" name="apoio_orgaos" value="dnit"> DNIT</label>
                                <label><input type="checkbox" name="apoio_orgaos" value="outros"> OUTROS</label>
                            </div>
                        </div>
                    </div>
                    <p class="my-0">Semáforo será desligado? Se sim, indicar abaixoe informar o transito.</p>
                </div>

            </div>



            <div class="grid-item">
                <div class="card-header">TIPO DE CALÇADA</div>
                <div class="checkbox-group pt-1">
                    <label><input type="checkbox"> LADRILHO</label>
                    <label><input type="checkbox"> CIMENTO</label>
                    <label><input type="checkbox"> TERRA</label>
                    <label><input type="checkbox"> CALÇADA CIDADÃO</label>
                </div>
            </div>


            <div class="grid-item">

                <div class="card-header">DADOS ELETRICOS</div>
                <div class="card-body pt-1">
                    <p class="my-0">Há divergências nas referências elétricas?</p>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="projeto" value="nao"> NÃO</label>
                        <label><input type="checkbox" name="projeto" value="sim"> SIM</label>
                    </div>
                    <p class="my-1">Indicar no Croqui detalhado</p>
                </div>

            </div>


            <!-- ACESSOS LIBERADOS -->
            <div class="grid-item">

                <div class="card-header">ACESSOS LIBERADOS</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <p class="my-1">Existem porteiras antes do local da obra?</p>
                            <div class="checkbox-group">
                                <label><input type="checkbox" name="acessos_liberados" value="nao"> NÃO</label>
                                <label><input type="checkbox" name="acessos_liberados" value="sim"> SIM</label>
                            </div>
                            <div class="form-group">
                                <label for="telefone_acesso">Se Sim, informar contato de Telefone:</label>
                                <input type="text" name="telefone_acesso" id="telefone_acesso">
                            </div>
                        </div>
                        <div class="col-6">
                            <p class="my-1">Acesso liberado pelo proprietrio?</p>
                            <div class="checkbox-group">
                                <label><input type="checkbox" name="acessos_liberados" value="nao"> NÃO</label>
                                <label><input type="checkbox" name="acessos_liberados" value="sim"> SIM</label>
                            </div>
                            <div class="form-group mt-2">
                                <label for="telefone_acesso">Se Sim, informar contato de Telefone:</label>
                                <input type="text" name="telefone_acesso" id="telefone_acesso">
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <div class="grid-item">

                <div class="card-header">ACESSO EM DIAS DE CHUVA?</div>
                <div class="card-body pt-1">

                    <div class="checkbox-group">
                        <label><input type="checkbox" name="projeto" value="nao"> NÃO</label>
                        <label><input type="checkbox" name="projeto" value="sim"> SIM</label>
                    </div>

                </div>

            </div>

            <!-- RECURSOS NECESSÁRIOS -->
            <div class="grid-item">

                <div class="card-header">RECURSOS NECESSÁRIOS</div>
                <div class="card-body pt-1">
                    <div class="row mt-0">
                        <div class="col-6">
                            <div class="checkbox-group">
                                <label><input type="checkbox" name="recursos" value="camionete"> CAMIONETE</label>
                                <label><input type="checkbox" name="recursos" value="trator"> TRATOR</label>

                            </div>
                        </div>
                        <div class="col-6">
                            <div class="checkbox-group">

                                <label><input type="checkbox" name="recursos" value="cesto_aereo"> CESTO
                                    AÉREO</label>
                                <label><input type="checkbox" name="recursos" value="escavadeira">
                                    ESCAVADEIRA</label>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- DIVERGÊNCIAS NOS CABOS -->
            <div class="grid-item">

                <div class="card-header">DIVERGÊNCIAS NOS CABOS</div>
                <div class="card-body pt-1">
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="divergencias_cabos" value="nao"> NÃO</label>
                        <label><input type="checkbox" name="divergencias_cabos" value="sim"> SIM</label>
                    </div>
                </div>

            </div>

            <!-- ABELHAS/MARIMBONDOS -->
            <div class="grid-item">

                <div class="card-header">ABELHAS/MARIMBONDOS</div>
                <div class="card-body pt-1">
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="abelhas" value="nao"> NÃO</label>
                        <label><input type="checkbox" name="abelhas" value="sim"> SIM</label>
                    </div>
                </div>

            </div>

            <!-- FERRAGENS EXPOSTAS -->
            <div class="grid-item">

                <div class="card-header">FERRAGENS EXPOSTAS</div>
                <div class="card-body pt-1">
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="ferragens_expostas" value="nao"> NÃO</label>
                        <label><input type="checkbox" name="ferragens_expostas" value="sim"> SIM</label>
                    </div>
                </div>

            </div>

            <!-- CHAVES ANTES/DEPOIS -->
            <div class="grid-item">

                <div class="card-header">CHAVES ANTES/DEPOIS</div>
                <div class="card-body pt-1">
                    <p class="my-0">Tem como instalar chaves antes e depois do local de trabalho desligando/isolando
                        o trecho de
                        trabalho?</p>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="chaves" value="nao"> NÃO</label>
                        <label><input type="checkbox" name="chaves" value="sim"> SIM</label>
                    </div>
                    <p class="my-1">Se sim, fazer o croqui detalhado.</p>
                </div>

            </div>

            <!-- CABO EXISTENTE -->
            <div class="grid-item">

                <div class="card-header">CABO EXISTENTE</div>
                <div class="card-body pt-1">
                    <div class="form-group">
                        <label for="mt">MT:</label>
                        <input type="text" name="mt" id="mt">
                    </div>
                    <div class="form-group">
                        <label for="bt">BT:</label>
                        <input type="text" name="bt" id="bt">
                    </div>
                </div>

            </div>

            <!-- SEGURANÇA C/ ESCADAS -->
            <div class="grid-item">

                <div class="card-header">SEGURANÇA C/ ESCADAS</div>
                <div class="card-body pt-0">
                    <p class="py-0">Existem condições para executar obra somente com escadas?</p>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="seguranca_escadas" value="nao"> NÃO</label>
                        <label><input type="checkbox" name="seguranca_escadas" value="sim"> SIM</label>
                    </div>
                </div>

            </div>

            <!-- PROJETO -->
            <div class="grid-item">

                <div class="card-header">PROJETO</div>
                <div class="card-body pt-0">
                    <p class="py-0">Projeto precisa ser atualizado?</p>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="projeto" value="nao"> NÃO</label>
                        <label><input type="checkbox" name="projeto" value="sim"> SIM</label>
                    </div>
                    <p class="my-1">Enviar as-built</p>
                </div>

            </div>

            <!-- ORÇAMENTO -->
            <div class="grid-item">

                <div class="card-header">ORÇAMENTO</div>
                <div class="card-body">
                    <p class="py-0">Material e Mão de Obra precisam ser ajustados?</p>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="orcamento" value="sim"> SIM</label>
                        <label><input type="checkbox" name="orcamento" value="nao"> NÃO</label>
                    </div>
                    <div class="form-group">
                        <label for="ma">Μα να:</label>
                        <input type="text" name="ma" id="ma">
                    </div>
                </div>

            </div>

            <!-- DIVERGÊNCIAS POSTES -->
            <div class="grid-item">

                <div class="card-header">DIVERGÊNCIAS POSTES</div>
                <div class="card-body pt-1">
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="divergencias_postes" value="nao"> NÃO</label>
                        <label><input type="checkbox" name="divergencias_postes" value="sim"> SIM</label>
                    </div>
                </div>

            </div>

            <!-- ESPAÇO NO POSTE -->
            <div class="grid-item">

                <div class="card-header">ESPAÇO NO POSTE</div>
                <div class="card-body">

                    <p class="py-0">Existe espaço no poste para fazer a estrura de derivação?</p>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="espaco_poste" value="nao"> NÃO</label>
                        <label><input type="checkbox" name="espaco_poste" value="sim"> SIM</label>
                    </div>
                </div>


            </div>

            <div class="grid-item">
                <div class="card-header">CLIENTES A AVISAR?</div>
                <p class="py-0">Existem clientes importantes que precisam ser avisados em caso de desligamento?</p>
                <div class="checkbox-group">
                    <label><input type="checkbox"> NÃO</label>
                    <label><input type="checkbox"> SIM</label>
                </div>
            </div>

        </div>
        <div class="row">
            <div class="col-4">
                <div class="grid-item">
                    <div class="card-header">ACESSO PARA VEÍCULOS</div>

                    <div class="checkbox-group pt-1">
                        <label><input type="checkbox"> GUINDAUTO</label>
                        <label><input type="checkbox"> PORTEIRA TRANCADA</label>
                        <label><input type="checkbox"> TRATOR (ABERTURA DE ESTRADA)</label>
                        <label><input type="checkbox"> CESTA AÉREA</label>
                        <label><input type="checkbox"> SEM ACESSO</label>

                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="grid-item">
                    <div class="card-header">CAVA EM ROCHA</div>

                    <div class="checkbox-group pt-1">
                        <label><input type="checkbox"> SIM</label>
                        <label><input type="checkbox"> NÃO</label>
                        <label><input type="checkbox"> NÃO IDENTIFICADO</label>

                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-header">OBSERVAÇÕES</div>
                    <textarea rows="8"></textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="row align-items-center">
                <div class="col-12 mb-1">
                    <label class="form-label">Viabilidade em conjunto com o fiscal de obras?</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox"> NÃO</label>
                        <label><input type="checkbox"> SIM, Qual? __________________________________</label>
                    </div>
                </div>


                <div class="col-3 mb-1">
                    <label class="form-label small">DATA:</label>
                    <div class="form-control form-control-sm p-0 h-auto" style="border-bottom: 1px solid #ccc;"></div>
                </div>
                <div class="col-3 mb-1">
                    <label class="form-label small">RESPONSÁVEL VIABILIDADE (Nome Legivel):</label>
                    <div class="form-control form-control-sm p-0 h-auto" style="border-bottom: 1px solid #ccc;"></div>
                </div>
                <div class="col-6 mb-1">
                    <label class="form-label small">RESPONSÁVEL VIABILIDADE (Rubrica):</label>
                    <div class="form-control form-control-sm p-0 h-auto" style="border-bottom: 1px solid #ccc;"></div>
                </div>
            </div>
        </div>


    </div>



</body>

</html>
