<?php

namespace App\Helpers;

class SelectOptions
{
    public static function getReclaimsOptions()
    {
        return [
            (object)['info' => '(RI) ANEXAR PDF AO PROJETO', 'value' => 'ANEXAR PDF', 'needFile' => true],
            (object)['info' => '(RI) LIBERAR PROJETO NO EO', 'value' => 'LIBERAR EO', 'needFile' => false],
            (object)['info' => '(RI) ALTERAÇÃO NO PROJETO', 'value' => 'ALTERAR PROJETO', 'needFile' => true],
        ];
    }

    public static function getEquipmentOptions()
    {
        return [
            (object)['info' => 'BANCO CAPACITORES', 'nick' => 'BC'],
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
        return [
            (object)['info' => 'Selecione Resposta', 'value' => ''],
            (object)['info' => 'CONCORDAR', 'value' => 'CONCORDAR'],
            (object)['info' => 'DISCORDAR', 'value' => 'DISCORDAR'],
        ];
    }

    public static function getProtocolReasons()
    {
        return [
            (object)['reason' => 'Aguardando Alvará', 'value' => 'AGUARDANDO ALVARA'],
            (object)['reason' => 'Aguardando Ajustes', 'value' => 'AGUARDANDO AJUSTES'],
            (object)['reason' => 'Aguardando Comprovante', 'value' => 'AGUARDANDO COMPROVANTE'],
            (object)['reason' => 'Aguardando Protocolo Presencial', 'value' => 'AGUARDANDO PROTOCOLO PRESENCIAL'],
            (object)['reason' => 'Alvará em Anexo', 'value' => 'ALVARA EM ANEXO'],
            (object)['reason' => 'Carta ao cliente', 'value' => 'CARTA AO CLIENTE'],
            (object)['reason' => 'Deferido', 'value' => 'DEFERIDO'],
            (object)['reason' => 'Envio de Taxa para Pagamento', 'value' => 'ENVIO DE TAXA PARA PAGAMENTO'],
            (object)['reason' => 'Indeferido', 'value' => 'INDEFERIDO'],
            (object)['reason' => 'Protocolado', 'value' => 'PROTOCOLADO'],
            (object)['reason' => 'Retorno do Órgão', 'value' => 'RETORNO DO ORGAO'],
            (object)['reason' => 'Solicitação de Ajustes', 'value' => 'SOLICITACAO DE AJUSTES'],
            (object)['reason' => 'Taxa de Pagamento Paga', 'value' => 'TAXA DE PAGAMENTO PAGA']
        ];
    }

    public static function getExternals($type = null)
    {
        $externals = [
            (object)['type' => 'ESTRADAS', 'nick' => 'DER', 'agency' => 'Departamento de Edificações e de Rodovias do Estado do Espírito Santo'],
            (object)['type' => 'ESTRADAS', 'nick' => 'DNIT', 'agency' => 'Departamento Nacional de Infraestrutura de Transportes'],
            (object)['type' => 'ESTRADAS', 'nick' => 'ECO101', 'agency' => 'ECO101 CONCESSIONÁRIA DE RODOVIAS S/A'],
            (object)['type' => 'AMBIENTAL', 'nick' => 'IEMA', 'agency' => 'Instituto Estadual de Meio Ambiente e Recursos Hídricos'],
            (object)['type' => 'AMBIENTAL', 'nick' => 'ICMBIO', 'agency' => 'Instituto Chico Mendes de Conservação da Biodiversidade'],
            (object)['type' => 'AMBIENTAL', 'nick' => 'IDAF', 'agency' => 'Instituto de Defesa Agropecuária e Florestal do Espírito Santo'],
            (object)['type' => 'AMBIENTAL', 'nick' => 'IBAMA', 'agency' => 'Instituto Brasileiro do Meio Ambiente e dos Recursos Naturais Renováveis'],
            (object)['type' => 'FEDERAL', 'nick' => 'FURNAS', 'agency' => 'Eletrobras Furnas'],
            (object)['type' => 'FEDERAL', 'nick' => 'SECULT', 'agency' => 'Secretaria de Estado da Cultura'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM ANCHIETA', 'agency' => 'Prefeitura Municipal de Anchieta'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM AFONSO CLAUDIO', 'agency' => 'Prefeitura Municipal de Afonso Cláudio'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM ALEGRE', 'agency' => 'Prefeitura Municipal de Alegre'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM AGUA DOCE DO NORTE', 'agency' => 'Prefeitura Municipal de Água Doce do Norte'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM ARACRUZ', 'agency' => 'Prefeitura Municipal de Aracruz'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM BARRA DE SÃO FRANCISCO', 'agency' => 'Prefeitura Municipal de Barra de São Francisco'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM CACHOEIRO DE ITAPEMIRIM', 'agency' => 'Prefeitura Municipal de Cachoeiro de Itapemirim'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM CASTELO', 'agency' => 'Prefeitura Municipal de Castelo'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM CARIACICA', 'agency' => 'Prefeitura Municipal de Cariacica'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM DOMINGOS MARTINS', 'agency' => 'Prefeitura Municipal de Domingos Martins'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM GUACUI', 'agency' => 'Prefeitura Municipal de Guaçuí'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM GUARAPARI', 'agency' => 'Prefeitura Municipal de Guarapari'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM IBIRAÇU', 'agency' => 'Prefeitura Municipal de Ibiraçu'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM ICONHA', 'agency' => 'Prefeitura Municipal de Iconha'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM ITAGUAÇU', 'agency' => 'Prefeitura Municipal de Itaguaçu'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM ITAPEMIRIM', 'agency' => 'Prefeitura Municipal de Itapemirim'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM ITARANA', 'agency' => 'Prefeitura Municipal de Itarana'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM JAGUARE', 'agency' => 'Prefeitura Municipal de Jaguaré'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM LARANJA DA TERRA', 'agency' => 'Prefeitura Municipal de Laranja da Terra'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM LINHARES', 'agency' => 'Prefeitura Municipal de Linhares'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM MIMOSO DO SUL', 'agency' => 'Prefeitura Municipal de Mimoso do Sul'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM MARATAIZES', 'agency' => 'Prefeitura Municipal de Marataízes'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM MONTANHA', 'agency' => 'Prefeitura Municipal de Montanha'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM MUNIZ FREIRE', 'agency' => 'Prefeitura Municipal de Muniz Freire'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM NOVA VENÉCIA', 'agency' => 'Prefeitura Municipal de Nova Venécia'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM PEDRO CANARIO', 'agency' => 'Prefeitura Municipal de Pedro Canário'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM PIUMA', 'agency' => 'Prefeitura Municipal de Piuma'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM RIO BANANAL', 'agency' => 'Prefeitura Municipal de Rio Bananal'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM STA M JETIBA', 'agency' => 'Prefeitura Municipal de Santa Maria de Jetibá'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM STA TERESA', 'agency' => 'Prefeitura Municipal de Santa Teresa'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM SAO GABRIEL DA PALHA', 'agency' => 'Prefeitura Municipal de São Gabriel da Palha'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM SÃO MATEUS', 'agency' => 'Prefeitura Municipal de São Mateus'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM SERRA', 'agency' => 'Prefeitura Municipal de Serra'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM VENDA NOVA DO IMIGRANTE', 'agency' => 'Prefeitura Municipal de Venda Nova do Imigrante'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM VIANA', 'agency' => 'Prefeitura Municipal de Viana'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM VILA PAVÃO', 'agency' => 'Prefeitura Municipal de Vila Pavão'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM VILA VELHA', 'agency' => 'Prefeitura Municipal de Vila Velha'],
            (object)['type' => 'PREFEITURA', 'nick' => 'PM VITORIA', 'agency' => 'Prefeitura Municipal de Vitória'],
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
}
