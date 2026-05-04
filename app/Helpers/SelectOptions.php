<?php

namespace App\Helpers;

class SelectOptions
{
    public static function getReclaimsOptions()
    {
        return [
            (object)['info' => '(RI) ANEXAR PDF AO PROJETO', 'value' => 'ANEXAR PDF', 'needFile' => true],
            (object)['info' => '(RI) LIBERAR PROJETO NO EO', 'value' => 'LIBERAR EO', 'needFile' => false],
            (object)['info' => '(RI) ANEXAR PDF E LIBERAR PROJETO NO EO', 'value' => 'ANEXAR E LIBERAR', 'needFile' => true],
            (object)['info' => '(RI) ALTERAÇÃO NO PROJETO', 'value' => 'ALTERAR PROJETO', 'needFile' => true],
        ];
    }

    public static function getRejectOptions()
    {
        return [
            (object)['info' => '(RI) ANEXAR PDF AO PROJETO', 'value' => 'ANEXAR PDF', 'needFile' => true],
            (object)['info' => '(RI) ALTERAÇÃO NO PROJETO', 'value' => 'ALTERAR PROJETO', 'needFile' => true],
            (object)['info' => '(RI) ALTERAÇÃO NO ORÇAMENTO', 'value' => 'ALTERAR ORÇAMENTO', 'needFile' => true],
            (object)['info' => '(RI) ALTERAÇÃO NO NO PROJETO E ORÇAMENTO', 'value' => 'ALTERAR PROJETO E ORÇAMENTO', 'needFile' => true],
            (object)['info' => '(RI) REFAZER LEVANTAMENTO', 'value' => 'REFAZER LEVANTAMENTO', 'needFile' => true],
        ];
    }

    public static function getNewRejectOptions()
    {
        return [
            (object)['info' => '(RI) CORRIGIR ALTERAÇÃO', 'value' => 'ANEXAR PDF', 'needFile' => true],
            (object)['info' => '(RI) ALTERAÇÃO NO PROJETO', 'value' => 'ALTERAR PROJETO', 'needFile' => true],
            (object)['info' => '(RI) ALTERAÇÃO NO ORÇAMENTO', 'value' => 'ALTERAR ORÇAMENTO', 'needFile' => true],
            (object)['info' => '(RI) ALTERAÇÃO NO NO PROJETO E ORÇAMENTO', 'value' => 'ALTERAR PROJETO E ORÇAMENTO', 'needFile' => true],
            (object)['info' => '(RI) REFAZER LEVANTAMENTO', 'value' => 'REFAZER LEVANTAMENTO', 'needFile' => true],
        ];
    }

    public static function getEquipmentOptions()
    {
        return [
            (object)['info' => 'BANCO CAPACITORES', 'nick' => 'BC'],
            (object)['info' => 'CAIXA DE MEDIÇÃO', 'nick' => 'CM'],
            (object)['info' => 'CONCENTRADOR PRIMARIO', 'nick' => 'CP'],
            (object)['info' => 'CONCENTRADOR SECUNDÁRIO', 'nick' => 'CS'],
            (object)['info' => 'REGULADOR DE TENSÃO', 'nick' => 'RT'],
            (object)['info' => 'RELIGADOR', 'nick' => 'RL'],
            (object)['info' => 'TRAFO', 'nick' => 'TF'],
        ];
    }

    public static function getFasesOptions()
    {
        return [
            (object)['info' => 'A', 'nick' => 'A'],
            (object)['info' => 'B', 'nick' => 'B'],
            (object)['info' => 'C', 'nick' => 'C'],
            (object)['info' => 'AB', 'nick' => 'AB'],
            (object)['info' => 'AC', 'nick' => 'AC'],
            (object)['info' => 'BC', 'nick' => 'BC'],
            (object)['info' => 'ABC', 'nick' => 'ABC'],
            (object)['info' => 'Não Aplicável', 'nick' => 'NA'],
        ];
    }

    public static function getPublicationOptions()
    {
        return [

            (object)['info' => 'LIBERAR PARA LIGAÇÃO', 'value' => 'LIBERADO PARA LIGAÇÃO'],
            (object)['info' => 'OBRA CANCELADA', 'value' => 'OBRA CANCELADA'],
            (object)['info' => 'OBRA INFORMADA INDEVIDAMENTE', 'value' => 'OBRA INFORMADA INDEVIDAMENTE'],
            (object)['info' => 'OBRA PUBLICADA (SOLICITACAO)', 'value' => 'OBRA PUBLICADA (SOLICITACAO)'],
        ];
    }

    public static function getPaymentsOptions()
    {
        return [
            (object)['info' => 'LIBERAÇÃO DE CARTA', 'value' => 'LIBERACAO DE CARTA'],
            (object)['info' => 'PAGAMENTO PARCIAL', 'value' => 'PAGAMENTO PARCIAL'],
            (object)['info' => 'PAGAMENTO TOTAL COM RETENCAO DE CARTA', 'value' => 'PAGAMENTO TOTAL COM RETENCAO'],
            (object)['info' => 'PAGAMENTO TOTAL', 'value' => 'PAGAMENTO TOTAL'],
        ];
    }

    public static function verifyNeedFilesReclaims($item)
    {


        foreach (static::getReclaimsOptions() as $option) {



            if ($option->value == $item) {

                if ($option->needFile) {
                    return true;
                } else {
                    return false;
                }
            }
        }

        return false;
    }

    public static function getResponserOptions()
    {
        // Não alterar os valoes dem VALUE, pois é usado como referencia de ação;
        return [
            (object)['info' => 'Selecione Resposta', 'value' => ''],
            (object)['info' => 'CONCORDAR', 'value' => 'CONCORDAR'],
            (object)['info' => 'DISCORDAR', 'value' => 'DISCORDAR'],
        ];
    }

    public static function getResponserDestiniesOptions()
    {
        // Não alterar os valoes dem VALUE, pois é usado como referencia de ação;
        return [
            (object)['info' => 'Selecione Resposta', 'value' => ''],
            (object)['info' => 'RETORNAR', 'value' => 'DEVOLVER'],
            (object)['info' => 'OBRA JA EXECUTADA', 'value' => 'EXECUTADA'],
            (object)['info' => 'LIBERAR PARA CONTRATAR', 'value' => 'LIBERAR'],
            // (object)['info' => 'LIBERAR', 'value' => 'SEGUIR'],

            (object)['info' => 'NOVA VIABILIDADE', 'value' => 'RETORNAR'],
        ];
    }

    public static function getReturnInterOptionsResponse()
    {
        // Não alterar os valoes dem VALUE, pois é usado como referencia de ação;
        return [
            (object)['info' => 'Selecione Resposta', 'value' => '', 'type' => 'TODOS'],
            (object)['info' => 'RETORNAR PARA ETAPA PROJETO', 'value' => 'DEVOLVER', 'type' => 'REPROVADO'],
            // (object)['info' => 'LIBERAR PARA CONTRATAR', 'value' => 'LIBERAR', 'type' => 'APROVADO'],
            (object)['info' => 'LIBERAR', 'value' => 'LIBERAR', 'type' => 'APROVADO'],
            (object)['info' => 'NOVA VIABILIDADE', 'value' => 'RETORNAR_VIAB', 'type' => 'APROVADO'],
        ];
    }

    // MOTIVOS PARA GERAÇÃO DE D5 e FISCALIZAÇÃO

    public static function getD5Reasons()
    {
        return [
            (object)['reason' => '01 - Falta de Materiais', 'value' => '01_FALTA DE MATERIAIS'],
            (object)['reason' => '02 - Reparo Passeio', 'value' => '02_REPARO PASSEIO'],
            (object)['reason' => '03 - Aterramento', 'value' => '03_ATERRAMENTO'],
            (object)['reason' => '04 - Inventário', 'value' => '04_INVENTARIO'],
            (object)['reason' => '05 - Pendência de Poda', 'value' => '05_PENDENCIA DE PODA'],
            (object)['reason' => '06 - Projeto', 'value' => '06_PROJETO'],
            (object)['reason' => '07 - Chave', 'value' => '07_CHAVE'],
            (object)['reason' => '08 - Condutor', 'value' => '08_CONDUTOR'],
            (object)['reason' => '09 - Conexão', 'value' => '09_CONEXAO'],
            (object)['reason' => '10 - Equipamento', 'value' => '10_EQUIPAMENTO'],
            (object)['reason' => '11 - Estrutura/Poste', 'value' => '11_ESTRUTURA/POSTE'],
            (object)['reason' => '12 - Isolador/Cadeia', 'value' => '12_ISOLADOR/CADEIA'],
            (object)['reason' => '13 - Padrão de Entrada', 'value' => '13_PADRAO DE ENTRADA'],
            (object)['reason' => '14 - Para-raio', 'value' => '14_PARA-RAIO'],
            (object)['reason' => '15 - Sinalização', 'value' => '15_SINALIZACAO'],
            (object)['reason' => '16 - Outros', 'value' => '16_OUTROS']
        ];
    }

    public static function getD5codify()
    {
        return [
            (object)['reason' => '001 - Reparo Urgente', 'value' => '001_REPARO URGENTE'],
            (object)['reason' => '002 - Reparo', 'value' => '002_REPARO'],
            (object)['reason' => '003 - Comunicação', 'value' => '003_COMUNICACAO'],
            (object)['reason' => '004 - Solicitação', 'value' => '004_SOLICITACAO'],
            (object)['reason' => '005 - Retorno de Divergência Projeto', 'value' => '005_RETORNO DE DIVERGENCIA PROJETO'],
            (object)['reason' => '006 - Retorno de Divergência de Orçamento', 'value' => '006_RETORNO DE DIVERGENCIA DE ORCAMENTO'],
            (object)['reason' => '007 - Alteração de Projeto', 'value' => '007_ALTERACAO DE PROJETO'],
            (object)['reason' => '008 - Outros', 'value' => '008_OUTROS'],
            (object)['reason' => '009 - Multa Prazo de Execução', 'value' => '009_MULTA PRAZO DE EXECUCAO'],
            (object)['reason' => '010 - Multa Prazo de Devolução', 'value' => '010_MULTA PRAZO DE DEVOLUCAO'],
            (object)['reason' => '011 - Multa Prazo Desigamento', 'value' => '011_MULTA PRAZO DESIGAMENTO'],
            (object)['reason' => '012 - Multas', 'value' => '012_MULTAS'],

        ];
    }

    public static function getComissionEnd()
    {
        return [
            (object)['reason' => '(58) Inspeção Rejeitada', 'value' => 'INSPECAO REJEITADA', 'block' => false],
            (object)['reason' => '(61) Inspeção Aprovada', 'value' => 'INSPECAO APROVADA', 'block' => false]
        ];
    }


    public static function getSupervisionEnd()
    {
        return [
            (object)['reason' => 'Fiscalizado Sem Pendências', 'value' => 'FISCALIZADO SEM PENDENCIAS', 'block' => false],
            (object)['reason' => 'Fiscalizado Com Pendências', 'value' => 'FISCALIZADO COM PENDENCIAS', 'block' => true],
            (object)['reason' => 'Obra Não Executada', 'value' => 'OBRA NAO EXECUTADA', 'block' => true]
        ];
    }


    public static function getReverseFluxConclusion()
    {
        return [
            (object)['reason' => 'Estudo Detalhado', 'value' => 'ESTUDO DETALHADO', 'block' => false],
            (object)['reason' => 'Estudo Simples', 'value' => 'ESTUDO SIMPLES', 'block' => false],
            (object)['reason' => 'Sem Estudo', 'value' => 'SEM ESTUDO', 'block' => false]
        ];
    }

    public static function getReverseFluxEnd()
    {
        return [
            (object)['reason' => 'ISR - Independe de Serviço de rede', 'value' => 'ISR - INDEPENDE DE SERVICO DE REDE', 'block' => false],
            (object)['reason' => 'NE - Não Elaborado', 'value' => 'NE - NAO ELABORADO', 'block' => false],
            (object)['reason' => 'DSR - Dependente de Serviço de rede', 'value' => 'DSR - DEPENDENTE DE SERVICO DE REDE', 'block' => false]
        ];
    }



    // OPÇÕES DE ORGÃO EXTERNO

    // public static function getProtocolReasons()
    // {
    //     return [
    //         (object)[
    //             'reason' => 'À Protocolar',
    //             'value'  => 'A PROTOCOLAR',
    //             'prefix' => 'APROTO',
    //         ],
    //         (object)[
    //             'reason' => 'Aguardando Ajustes',
    //             'value'  => 'AGUARDANDO AJUSTES',
    //             'prefix' => 'AGAJU',
    //         ],
    //         (object)[
    //             'reason' => 'Aguardando Alvará',
    //             'value'  => 'AGUARDANDO ALVARA',
    //             'prefix' => 'AGALVA',
    //         ],
    //         (object)[
    //             'reason' => 'Aguardando Comprovante',
    //             'value'  => 'AGUARDANDO COMPROVANTE',
    //             'prefix' => 'AGCOMP',
    //         ],
    //         (object)[
    //             'reason' => 'AguardandoRetorno do Órgão',
    //             'value'  => 'AGUARDANDO RETORNO DO ORGAO',
    //             'prefix' => 'AGURETORG',
    //         ],
    //         (object)[
    //             'reason' => 'Alvará em Anexo',
    //             'value'  => 'ALVARA EM ANEXO',
    //             'prefix' => 'ALVAAN',
    //         ],
    //         (object)[
    //             'reason' => 'Carta ao cliente',
    //             'value'  => 'CARTA AO CLIENTE',
    //             'prefix' => 'CARTCLI',
    //         ],
    //         (object)[
    //             'reason' => 'Deferido',
    //             'value'  => 'DEFERIDO',
    //             'prefix' => 'DEFE',
    //         ],
    //         (object)[
    //             'reason' => 'Envio de Taxa para Pagamento',
    //             'value'  => 'ENVIO DE TAXA PARA PAGAMENTO',
    //             'prefix' => 'ENVITXPG',
    //         ],
    //         (object)[
    //             'reason' => 'Indeferido',
    //             'value'  => 'INDEFERIDO',
    //             'prefix' => 'INDEF ',
    //         ],
    //         (object)[
    //             'reason' => 'Protocolado',
    //             'value'  => 'PROTOCOLADO',
    //             'prefix' => 'PROTO',
    //         ],
    //         (object)[
    //             'reason' => 'Protocolar Presencial',
    //             'value'  => 'PROTOCOLAR PRESENCIAL',
    //             'prefix' => 'PROTOPRE',
    //         ],
    //         (object)[
    //             'reason' => 'Solicitação de Ajustes',
    //             'value'  => 'SOLICITACAO DE AJUSTES',
    //             'prefix' => 'SOLAJUS',
    //         ],
    //         (object)[
    //             'reason' => 'Taxa Paga',
    //             'value'  => 'TAXA PAGA',
    //             'prefix' => 'TAXAPG',
    //         ],
    //     ];


    // }

    public static function getProtocolReasons()
    {
        return [
            (object)[
                'reason' => 'Aguardando Pagamento',
                'value'  => 'AGUARDANDO_PAGAMENTO',
                'prefix' => 'APAGTO',
                'color'  => 'warning',
                'colorbg' => 'bg-warning text-dark',
            ],
            (object)[
                'reason' => 'Aguardando Retorno do Orgão',
                'value'  => 'AGUARDANDO_ORGAO',
                'prefix' => 'AGURETORG',
                'color'  => 'warning',
                'colorbg' => 'bg-warning text-dark',
            ],
            (object)[
                'reason' => 'Aguardando Taxa',
                'value'  => 'AGUARDANDO_TAXA',
                'prefix' => 'AGTAXA',
                'color'  => 'warning',
                'colorbg' => 'bg-warning text-dark',
            ],
            (object)[
                'reason' => 'Indefinido',
                'value'  => 'INDEFINIDO',
                'prefix' => 'INDEF',
                'color'  => 'secondary',
                'colorbg' => 'bg-secondary text-white',
            ]
        ];
    }



    public static function getExternals($type = null)
    {
        $externals = [
            (object)['type' => 'AMBIENTAL', 'nick' => 'IBAMA', 'agency' => 'Instituto Brasileiro do Meio Ambiente e dos Recursos Naturais Renováveis'],
            (object)['type' => 'AMBIENTAL', 'nick' => 'ICMBIO', 'agency' => 'Instituto Chico Mendes de Conservação da Biodiversidade'],
            (object)['type' => 'AMBIENTAL', 'nick' => 'IDAF', 'agency' => 'Instituto de Defesa Agropecuária e Florestal do Espírito Santo'],
            (object)['type' => 'AMBIENTAL', 'nick' => 'IEMA', 'agency' => 'Instituto Estadual de Meio Ambiente e Recursos Hídricos'],
            (object)['type' => 'ESTRADAS', 'nick' => 'DER', 'agency' => 'Departamento de Edificações e de Rodovias do Estado do Espírito Santo'],
            (object)['type' => 'ESTRADAS', 'nick' => 'DNIT', 'agency' => 'Departamento Nacional de Infraestrutura de Transportes'],
            (object)['type' => 'ESTRADAS', 'nick' => 'ECO101', 'agency' => 'ECO101 CONCESSIONÁRIA DE RODOVIAS S/A'],
            (object)['type' => 'FEDERAL', 'nick' => 'FURNAS', 'agency' => 'Eletrobras Furnas'],
            (object)['type' => 'FEDERAL', 'nick' => 'SECULT', 'agency' => 'Secretaria de Estado da Cultura'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM AFONSO CLAUDIO', 'agency' => 'Prefeitura Municipal de Afonso Cláudio'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM AGUA DOCE DO NORTE', 'agency' => 'Prefeitura Municipal de Água Doce do Norte'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM ALEGRE', 'agency' => 'Prefeitura Municipal de Alegre'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM ANCHIETA', 'agency' => 'Prefeitura Municipal de Anchieta'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM ARACRUZ', 'agency' => 'Prefeitura Municipal de Aracruz'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM BARRA DE SÃO FRANCISCO', 'agency' => 'Prefeitura Municipal de Barra de São Francisco'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM CACHOEIRO DE ITAPEMIRIM', 'agency' => 'Prefeitura Municipal de Cachoeiro de Itapemirim'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM CARIACICA', 'agency' => 'Prefeitura Municipal de Cariacica'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM CASTELO', 'agency' => 'Prefeitura Municipal de Castelo'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM DOMINGOS MARTINS', 'agency' => 'Prefeitura Municipal de Domingos Martins'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM GUACUI', 'agency' => 'Prefeitura Municipal de Guaçuí'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM GUARAPARI', 'agency' => 'Prefeitura Municipal de Guarapari'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM IBATIBA', 'agency' => 'Prefeitura Municipal de Ibatiba'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM IBIRAÇU', 'agency' => 'Prefeitura Municipal de Ibiraçu'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM ICONHA', 'agency' => 'Prefeitura Municipal de Iconha'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM ITAGUAÇU', 'agency' => 'Prefeitura Municipal de Itaguaçu'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM ITAPEMIRIM', 'agency' => 'Prefeitura Municipal de Itapemirim'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM ITARANA', 'agency' => 'Prefeitura Municipal de Itarana'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM IUNA', 'agency' => 'Prefeitura Municipal de Iúna'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM JAGUARE', 'agency' => 'Prefeitura Municipal de Jaguaré'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM LARANJA DA TERRA', 'agency' => 'Prefeitura Municipal de Laranja da Terra'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM LINHARES', 'agency' => 'Prefeitura Municipal de Linhares'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM MARATAIZES', 'agency' => 'Prefeitura Municipal de Marataízes'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM MIMOSO DO SUL', 'agency' => 'Prefeitura Municipal de Mimoso do Sul'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM MONTANHA', 'agency' => 'Prefeitura Municipal de Montanha'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM MUNIZ FREIRE', 'agency' => 'Prefeitura Municipal de Muniz Freire'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM MUQUI', 'agency' => 'Prefeitura Municipal de Muqui'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM NOVA VENÉCIA', 'agency' => 'Prefeitura Municipal de Nova Venécia'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM PEDRO CANARIO', 'agency' => 'Prefeitura Municipal de Pedro Canário'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM PINHEIROS', 'agency' => 'Prefeitura Municipal de Pinheiros'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM PIUMA', 'agency' => 'Prefeitura Municipal de Piuma'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM RIO BANANAL', 'agency' => 'Prefeitura Municipal de Rio Bananal'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM SAO GABRIEL DA PALHA', 'agency' => 'Prefeitura Municipal de São Gabriel da Palha'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM SÃO MATEUS', 'agency' => 'Prefeitura Municipal de São Mateus'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM SERRA', 'agency' => 'Prefeitura Municipal de Serra'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM STA M JETIBA', 'agency' => 'Prefeitura Municipal de Santa Maria de Jetibá'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM STA TERESA', 'agency' => 'Prefeitura Municipal de Santa Teresa'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM VENDA NOVA DO IMIGRANTE', 'agency' => 'Prefeitura Municipal de Venda Nova do Imigrante'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM VIANA', 'agency' => 'Prefeitura Municipal de Viana'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM VILA PAVÃO', 'agency' => 'Prefeitura Municipal de Vila Pavão'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM VILA VELHA', 'agency' => 'Prefeitura Municipal de Vila Velha'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM VITORIA', 'agency' => 'Prefeitura Municipal de Vitória'],
            (object)['type' => 'OUTROS', 'nick' => 'OUTROS', 'agency' => 'Outros'],
        ];


        if ($type) {
            return array_filter($externals, function ($external) use ($type) {
                return $external->type === $type;
            });
        }

        return $externals;
    }

    public static function getExternalsByTypeOrNick($type = null, $nick = null)
    {
        $externals = static::getExternals();

        $filtered = array_filter($externals, function ($external) use ($type, $nick) {
            $matchesType = $type ? $external->type === $type : true;
            $matchesNick = $nick ? $external->nick === $nick : true;
            return $matchesType && $matchesNick;
        });

        $filteredValues = array_values($filtered);

        return count($filteredValues) > 0 ? $filteredValues[0] : null;
    }

    public static function getUniqueExternalTypes()
    {
        $externals = static::getExternals();
        $types = array_map(function ($external) {
            return $external->type;
        }, $externals);

        return array_unique($types);
    }


    // Return Publication
    public static function getReasonPublication()
    {
        return [
            (object)['reason' => 'A pedido da Empreiteira', 'value' => 'A PEDIDO DA EMPREITEIRA'],
            (object)['reason' => 'Alteração de Obra não Informada', 'value' => 'ALTERACAO DE OBRA NAO INFORMADA'],
            (object)['reason' => 'Falta de Evidências de Execução de Obras', 'value' => 'FALTA DE EVIDENCIAS DE EXECUCAO DE OBRAS'],
            (object)['reason' => 'Obra depende da execução de outra', 'value' => 'OBRA DEPENDE DA EXECUCAO DE OUTRA'],
            (object)['reason' => 'Obra não executada', 'value' => 'OBRA NAO EXECUTADA'],
            (object)['reason' => 'Patrimônio de equipamento informado incorretamente', 'value' => 'PATRIMONIO DE EQUIPAMENTO INFORMADO INCORRETAMENTE'],
            (object)['reason' => 'Patrimônio de equipamento não informado', 'value' => 'PATRIMONIO DE EQUIPAMENTO NAO INFORMADO'],
            (object)['reason' => 'Trafo não atendido', 'value' => 'TRAFO NAO ATENDIDO'],
            (object)['reason' => 'Trafo pendente de DE-PARA', 'value' => 'TRAFO PENDENTE DE DE-PARA'],
        ];
    }

    public static function getReasonSmcPublication()
    {
        return [
            (object)['reason' => 'A pedido da Digitação', 'value' => 'A PEDIDO DA DIGITACAO'],
            (object)['reason' => 'Alteração de Obra não Informada', 'value' => 'ALTERACAO DE OBRA NAO INFORMADA'],
            // (object)['reason' => 'Obra depende da execução de outra', 'value' => 'OBRA DEPENDE DA EXECUCAO DE OUTRA'],
            (object)['reason' => 'Obra não executada', 'value' => 'OBRA NAO EXECUTADA'],
            (object)['reason' => 'Patrimônio de equipamento informado incorretamente', 'value' => 'PATRIMONIO DE EQUIPAMENTO INFORMADO INCORRETAMENTE'],
            (object)['reason' => 'Patrimônio de equipamento não informado', 'value' => 'PATRIMONIO DE EQUIPAMENTO NAO INFORMADO'],
            (object)['reason' => 'Trafo não atendido', 'value' => 'TRAFO NAO ATENDIDO'],
            // (object)['reason' => 'Trafo pendente de DE-PARA', 'value' => 'TRAFO PENDENTE DE DE-PARA'],
        ];
    }


    // FIles Type Options
    public static function getFilesType()
    {
        return [
            (object)['reason' => 'ADS (Atestado de Serviço)', 'value' => 'ADS'],
            (object)['reason' => 'ASBUILT (Projeto Conforme Construído)', 'value' => 'ASBUILT'],
            (object)['reason' => 'CROQUI (Projeto a mão ou anotações)', 'value' => 'CROQUI'],
            (object)['reason' => 'EVIDENCIA (Evidência de Conclusão de Obra)', 'value' => 'EVIDENCIA'],
            (object)['reason' => 'FICHA VIAB TECNICA', 'value' => 'FTVEO'],
            (object)['reason' => 'IMAGEM', 'value' => 'IMAGEM'],
            (object)['reason' => 'LISTA (Clientes BT0, etc..)', 'value' => 'LISTA'],
            (object)['reason' => 'PROJETO (Projeto EO, CAD... Sem Anot. a Mão)', 'value' => 'PROJETO'],
            (object)['reason' => 'OUTROS...', 'value' => 'OUTROS'],
        ];
    }

    // Files Type for Production flow (mantém compatibilidade com o padrão geral)
    public static function getProductionFilesType()
    {
        return static::getFilesType();
    }

    // FIles Type ADS Options
    public static function getAdsFilesType()
    {
        return [
            (object)['reason' => 'ASBUILT (Projeto Conforme Construído)', 'value' => 'ASBUILT'],
            (object)['reason' => 'ATLV', 'value' => 'ATLV'],
            (object)['reason' => 'CHECK LIST', 'value' => 'CHKLST'],
            (object)['reason' => 'IMAGEM EQUIPAMENTO', 'value' => 'IMG_EQUIP'],
            (object)['reason' => 'NOTA DE DESELIGAMENTO', 'value' => 'NOTEDES'],
            (object)['reason' => 'PLANILHA CLIENTES', 'value' => 'PLANCLIE'],
        ];
    }

    public static function getPublicationFilesType()
    {
        return [

            (object)['reason' => 'PRE-COMPARAÇÃO', 'value' => 'PRECOMP'],
            (object)['reason' => 'IMAGEM', 'value' => 'IMAGEM'],
            (object)['reason' => 'PROJETO (Projeto EO, CAD... Sem Anot. a Mão)', 'value' => 'PROJETO'],
            (object)['reason' => 'OUTROS...', 'value' => 'OUTROS'],
        ];
    }

    // Desenho Draws Conclusions
    public static function getDrawConclusions()
    {
        return [
            (object)['reason' => '10 - EM CONTATO COM CLIENTE', 'value' => 'EM CONTATO COM CLIENTE'],
            (object)['reason' => '20 - DEPENDE DE ORGÃO EXTERNO', 'value' => 'DEPENDE DE ORGAO EXTERNO'],
            (object)['reason' => '22 - PROCESSO PARA MEDIÇÃO', 'value' => 'PROCESSO PARA MEDICAO'],
            (object)['reason' => '27 - RETORNADO LEVANTAMENTO', 'value' => 'RETORNADO LEVANTAMENTO'],
            (object)['reason' => '47 - EXECUÇÃO DE OBRAS DA EMPRESA', 'value' => 'EXECUCAO DE OBRAS DA EMPRESA'],
            (object)['reason' => '50 - EXECUÇÃO DE OBRAS CUSTO EMPRESA', 'value' => 'EXECUCAO DE OBRAS CUSTO EMPRESA'],
            (object)['reason' => '68 - ORÇAMENTO ESTIMADO', 'value' => 'ORÇAMENTO ESTIMADO'],
            (object)['reason' => '70 - ORÇAMENTO PRÉVIO', 'value' => 'ORÇAMENTO PRÉVIO'],
            (object)['reason' => '99 - ARQUIVADO', 'value' => 'ARQUIVADO'],
        ];
    }

    // Motivos para Levantamento
    public static function getSurveyConclusions()
    {
        return [
            (object)['reason' => '10 - EM CONTATO COM CLIENTE', 'value' => 'EM CONTATO COM CLIENTE', 'block' => false],
            (object)['reason' => '20 - DEPENDE DE ORGÃO EXTERNO', 'value' => 'DEPENDE DE ORGAO EXTERNO', 'block' => false],
            (object)['reason' => '21 - RETORNADO PARA ANÁLISE', 'value' => 'RETORNADO ANALISE', 'block' => false],
            (object)['reason' => '28 - ENVIADO AO DESENHO/ORÇAMENTO', 'value' => 'ENVIADO AO DESENHO/ORÇAMENTO', 'block' => false],
            (object)['reason' => '50 - EXECUÇÃO DE OBRAS CUSTO EMPRESA', 'value' => 'EXECUCAO DE OBRAS CUSTO EMPRESA'],
            (object)['reason' => '70 - ORÇAMENTO PRÉVIO', 'value' => 'ORÇAMENTO PRÉVIO'],
        ];
    }


    // Motivos PAUSE
    public static function getPauseNotesCategory()
    {
        return [
            (object)['reason' => 'CS NÃO INFORMADA', 'value' => 'CS NÃO INFORMADA'],
            (object)['reason' => 'DEPENDE DE OUTRA OV/NOTA', 'value' => 'DEPENDE DE OUTRA OV/NOTA'],
            (object)['reason' => 'DEPENDE DE REDESENHO', 'value' => 'DEPENDE DE REDESENHO'],
            (object)['reason' => 'PATRIMÔNIO NÃO INFORMADO', 'value' => 'PATRIMÔNIO NÃO INFORMADO'],
            (object)['reason' => 'TRAFO BAIXADO NA ORDEM ERRADA', 'value' => 'TRAFO BAIXADO NA ORDEM ERRADA'],
            (object)['reason' => 'TRAFO PENDENTE DE BAIXA', 'value' => 'TRAFO PENDENTE DE BAIXA'],
        ];
    }


    public static function getProtestCategory()
    {
        return [
            (object)['reason' => 'CIP - ANÁLISES (INVERSÃO DE FLUXO)', 'value' => 'CIP ANALISE (INVERSAO DE FLUXO)'],
            (object)['reason' => 'CIP - ANÁLISES (PRÉ-ANÁLISE)', 'value' => 'CIP ANALISE (PRE-ANALISE)'],
            (object)['reason' => 'CIP - PROJETO', 'value' => 'CIP PROJETO'],
            (object)['reason' => 'CONSTRUÇÃO', 'value' => 'CONSTRUCAO'],
            (object)['reason' => 'CONSTRUÇÃO BTZERO', 'value' => 'CONSTRUCAO BTZERO'],

        ];
    }
}
