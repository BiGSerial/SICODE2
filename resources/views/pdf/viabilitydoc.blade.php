<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viabilidade Técnica de Execução de Obras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-size: 9px;
            /* Further reduced font size to fit even more content */
        }

        .container-pg {
            width: 21cm;
            /* A4 Width */
            margin: 0 auto;
            /* Center the container */
            padding: 0.5cm;
            /* Reduced padding */
            box-sizing: border-box;
        }

        .header {
            text-align: center;
            margin-bottom: 5px;
        }

        .logo {
            text-align: left;
            margin-bottom: 5px;
        }

        .logo img {
            max-width: 60px;
            /* Adjusted logo size */
            height: auto;
        }

        .form-group {
            margin-bottom: 3px;
            /* Further reduced space */
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 1px;
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 3px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5px;
            /* Reduced gap */
            margin-bottom: 5px;
        }

        .grid-item {
            padding: 3px;
            box-sizing: border-box;
        }

        .checkbox-group {
            display: flex;
            flex-direction: column;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            margin-bottom: 1px;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 3px;
        }

        /* Colored Boxes */
        .blue-box {
            background-color: #E1F5FE;
            /* Light Blue */
            border: 1px solid #B3E5FC;
            /* Light Blue Border */
        }

        .blue-box-title {
            background-color: #0d6efd;
            /* Bootstrap Primary Color */
            color: white;
            text-align: center;
            padding: 3px;
            margin-bottom: 5px;
        }

        /* Printing styles */
        @page {
            size: A4;
            margin: 0;
        }

        @media print {
            body {
                font-size: 9px;
                -webkit-print-color-adjust: exact;
                margin: 0;
            }

            .container-pg {
                width: 21cm;
                height: 29.7cm;
                margin: 0;
                padding: 0.5cm;
                box-sizing: border-box;
            }
        }
    </style>
</head>

<body>
    <div class="container-pg">
        <div class="header d-flex align-items-center justify-content-between">
            <div class="logo">
                <img src="{{ asset('img/edp-img/edp_documento.png') }}"
                    alt="EDP Logo" style="max-width: 60px;">
            </div>
            <div>
                <h2 class="fw-bold" style="margin: 0; font-size: 14px;">VIABILIDADE TÉCNICA DE EXECUÇÃO DE OBRAS</h2>
            </div>
            <div>
                <h4 class="fw-bold">{{ date('m/Y') }}</h4>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-1">
                <div class="row mb-1 gx-1">
                    <div class="col-4">
                        <p class="my-0">DATA DE EXECUÇÃO: __________________</p>
                    </div>
                    <div class="col-4">
                        <p class="my-0">HORA INICIO: __________________</p>
                    </div>
                    <div class="col-4">
                        <p class="my-0">HORA FIM: __________________</p>
                    </div>
                </div>
                <div class="row mb-1 gx-1">
                    <div class="col-4">
                        <p class="my-0">NOTA/OV: __________________</p>
                    </div>
                    <div class="col-4">
                        <p class="my-0">PARCEIRA: __________________</p>
                    </div>
                    <div class="col-4">
                        <p class="my-0">CIRCUITO: __________________</p>
                    </div>

                </div>
                <div class="row mb-1 gx-1">
                    <div class="col-5">
                        <p class="my-0">MUNICIPIO: __________________</p>
                    </div>
                    <div class="col-7">
                        <p class="my-0 ms-4">EQUIPAMENTO QUE ISOLA O TRECHO: ______________________</p>
                    </div>
                </div>
                <div class="row mb-0 gx-1">
                    <div class="col-12">
                        <p class="my-0">ENDEREÇO:
                            ______________________________________________________________________________</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-container">
            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">FLYNG TAPE/CRUZAMENTO AÉREO</div>
                    <div class="card-body p-1">
                        <p class="mb-1">Cruzamento aéreo energizado?</p>
                        <div class="row gx-1">
                            <div class="col-6">
                                <div class="checkbox-group">
                                    <label><input type="checkbox" name="cruzamento_aereo" value="nao"> NÃO</label>
                                    <label><input type="checkbox" name="cruzamento_aereo" value="sim"> SIM</label>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="checkbox-group">
                                    <label><input type="checkbox" name="cruzamento_aereo_bt" value="bt"> BT</label>
                                    <label><input type="checkbox" name="cruzamento_aereo_mt" value="mt"> MT</label>
                                    <label><input type="checkbox" name="cruzamento_aereo_at" value="at"> AT</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">EQUIPES NECESSÁRIAS</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="equipes_necessarias" value="linha_viva"> LINHA
                                VIVA</label>
                            <label><input type="checkbox" name="equipes_necessarias" value="linha_morta"> LINHA
                                MORTA</label>
                            <label><input type="checkbox" name="equipes_necessarias" value="equipe_multi"> EQUIPE
                                MULTI</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">TIPO DE OBRA</div>
                    <div class="card-body p-1">
                        <div class="row gx-1">
                            <div class="col-6">
                                <div class="checkbox-group">
                                    <label><input type="checkbox" name="tipo_obra" value="livre"> LIVRE</label>
                                    <label><input type="checkbox" name="tipo_obra" value="manobra"> MANOBRA</label>
                                    <label><input type="checkbox" name="tipo_obra" value="desligamento">
                                        DESLIGAMENTO</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="checkbox-group">
                                    <label><input type="checkbox" name="tipo_obra" value="linha_viva"> LINHA
                                        VIVA</label>
                                    <label><input type="checkbox" name="tipo_obra" value="interligacao">
                                        INTERLIGAÇÃO</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">ABRIR PONTO REDE MT/REDE SECUNDÁRIA</div>
                    <div class="card-body p-1">
                        <p class="mb-1">Rede BT alimentada?</p>
                        <div class="form-check form-check-inline">
                            <label class="form-check-label" for="inlineCheckbox1"><input class="form-check-input"
                                    type="checkbox" id="inlineCheckbox1" value="option1"> SIM</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <label class="form-check-label" for="inlineCheckbox2"><input class="form-check-input"
                                    type="checkbox" id="inlineCheckbox2" value="option2"> NÃO</label>
                        </div>
                        <div class="checkbox-group">
                            <label for="net">Nº ET</label>
                            <input type="text" name="net" id="net" style="width: auto;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">COMPARTILHAMENTOS (USO MÚTUO)</div>
                    <div class="card-body p-1">
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
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">MELHOR DIA DA SEMANA</div>
                    <div class="card-body p-1">
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
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">ABRIR PONTO REDE BT?</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="abrir_ponto_rede" value="nao"> NÃO</label>
                            <label><input type="checkbox" name="abrir_ponto_rede" value="sim"> SIM</label>
                        </div>
                        <p class="my-1">Indicar no croqui</p>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">APOIO ORGÃOS PÚBLICOS</div>
                    <div class="card-body p-1">
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
                        <p class="my-1">Semáforo desligado?</p>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">TIPO DE CALÇADA</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="tipo_calcada" value="ladrilho"> LADRILHO</label>
                            <label><input type="checkbox" name="tipo_calcada" value="cimento"> CIMENTO</label>
                            <label><input type="checkbox" name="tipo_calcada" value="terra"> TERRA</label>
                            <label><input type="checkbox" name="tipo_calcada" value="calcada_cidadao"> CALÇADA
                                CIDADÃO</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">ACESSO PARA VEÍCULOS</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="acesso_veiculos" value="guindauto"> GUINDAUTO</label>
                            <label><input type="checkbox" name="acesso_veiculos" value="porteira_trancada"> PORTEIRA
                                TRANCADA</label>
                            <label><input type="checkbox" name="acesso_veiculos" value="trator"> TRATOR
                                (ABERTURA)</label>
                            <label><input type="checkbox" name="acesso_veiculos" value="cesta_aerea"> CESTA
                                AÉREA</label>
                            <label><input type="checkbox" name="acesso_veiculos" value="sem_acesso"> SEM
                                ACESSO</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">EXISTEM PORTEIRAS?</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="porteiras" value="nao"> NÃO</label>
                            <label><input type="checkbox" name="porteiras" value="sim"> SIM</label>
                        </div>
                        <div class="form-group">
                            <label for="net">Telefone:</label>
                            <input type="text" name="net" id="net" style="width: auto;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">ACESSO EM DIAS DE CHUVA</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="acesso_chuva" value="nao"> NÃO</label>
                            <label><input type="checkbox" name="acesso_chuva" value="sim"> SIM</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">DADOS ELÉTRICOS</div>
                    <div class="card-body p-1">
                        <p>Divergências elétricas?</p>
                        <div class="form-check form-check-inline">
                            <label class="form-check-label" for="inlineCheckbox1"><input class="form-check-input"
                                    type="checkbox" id="inlineCheckbox1" value="option1"> SIM</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <label class="form-check-label" for="inlineCheckbox2"><input class="form-check-input"
                                    type="checkbox" id="inlineCheckbox2" value="option2"> NÃO</label>
                        </div>
                        <p class="my-1">Indicar no croqui</p>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">ACESSOS LIBERADOS</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="porteiras" value="nao"> NÃO</label>
                            <label><input type="checkbox" name="porteiras" value="sim"> SIM</label>
                        </div>
                        <div class="form-group">
                            <label for="net">Telefone:</label>
                            <input type="text" name="net" id="net" style="width: auto;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">RECURSOS NECESSÁRIOS</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="recursos_necessarios" value="camionete">
                                CAMIONETE</label>
                            <label><input type="checkbox" name="recursos_necessarios" value="trator"> TRATOR</label>
                            <label><input type="checkbox" name="recursos_necessarios" value="cesto_aereo"> CESTO
                                AÉREO</label>
                            <label><input type="checkbox" name="recursos_necessarios" value="escavadeira">
                                ESCAVADEIRA</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">DIVERGÊNCIAS NOS CABOS?</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="divergencias_cabos" value="nao"> NÃO</label>
                            <label><input type="checkbox" name="divergencias_cabos" value="sim"> SIM</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">ABELHAS/MARIMBONDOS?</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="abelhas" value="nao"> NÃO</label>
                            <label><input type="checkbox" name="abelhas" value="sim"> SIM</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">FERRAGENS EXPOSTAS?</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="ferragens" value="nao"> NÃO</label>
                            <label><input type="checkbox" name="ferragens" value="sim"> SIM</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">CHAVES ANTES/DEPOIS?</div>
                    <div class="card-body p-1">
                        <p>Desligando/isolando?</p>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="chaves" value="nao"> NÃO</label>
                            <label><input type="checkbox" name="chaves" value="sim"> SIM</label>
                        </div>
                        <p class="my-1">Fazer croqui</p>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">CABO EXISTENTE?</div>
                    <div class="card-body p-1">
                        <div class="form-group">
                            <label for="mt">MT=</label>
                            <input type="text" name="mt" id="mt" style="width: 100%;">
                        </div>
                        <div class="form-group">
                            <label for="bt">BT=</label>
                            <input type="text" name="bt" id="bt" style="width: 100%;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">SEGURANÇA C/ ESCADAS?</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="condicoes_seguranca" value="nao"> NÃO</label>
                            <label><input type="checkbox" name="condicoes_seguranca" value="sim"> SIM</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">PROJETO</div>
                    <div class="card-body p-1">
                        <p>Projeto atualizado?</p>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="projeto" value="nao"> NÃO</label>
                            <label><input type="checkbox" name="projeto" value="sim"> SIM</label>
                        </div>
                        <p class="my-1">Enviar as-built</p>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">ORÇAMENTO</div>
                    <div class="card-body p-1">
                        <p>Ajustar na AD?</p>
                        <div class="form-check form-check-inline">
                            <label class="form-check-label" for="simCheckbox"><input class="form-check-input"
                                    type="checkbox" id="simCheckbox" value="sim"> SIM</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <label class="form-check-label" for="naoCheckbox"><input class="form-check-input"
                                    type="checkbox" id="naoCheckbox" value="nao"> NÃO</label>
                        </div>
                        <div class="form-group">
                            <label for="ma">Μα να:</label>
                            <input type="text" name="ma" id="ma" style="width: 100%;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">DIVERGÊNCIAS POSTES?</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="divergencias_postes" value="nao"> NÃO</label>
                            <label><input type="checkbox" name="divergencias_postes" value="sim"> SIM</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">ESPAÇO NO POSTE?</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="espaco_poste" value="nao"> NÃO</label>
                            <label><input type="checkbox" name="espaco_poste" value="sim"> SIM</label>
                        </div>
                    </div>
                </div>
            </div>


            <div class="grid-item">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">CLIENTES A AVISAR?</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="clientes" value="nao"> NÃO</label>
                            <label><input type="checkbox" name="clientes" value="sim"> SIM</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-item" colspan="3">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">OBSERVAÇÕES</div>
                    <div class="card-body p-1">
                        <textarea name="observacoes" rows="2" style="width: 100%;"></textarea>
                    </div>
                </div>
            </div>

            <div class="grid-item" colspan="3">
                <div class="card blue-box">
                    <div class="card-header blue-box-title">VIABILIDADE C/ FISCAL?</div>
                    <div class="card-body p-1">
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="viabilidade_fiscal" value="nao"> NÃO</label>
                            <label><input type="checkbox" name="viabilidade_fiscal" value="sim"> SIM,
                                QUAL?</label>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label for="data_fiscal">DATA:</label>
                    <input type="text" name="data_fiscal" id="data_fiscal" style="width: 100%;">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="responsavel">RESPONSÁVEL:</label>
                    <input type="text" name="responsavel" id="responsavel" style="width: 100%;">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
