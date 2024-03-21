<?php

namespace App\Custom;

class GeradorCartas
{
    protected $buyer;

    protected $client;

    protected $protocol;

    protected $lat;

    protected $lon;

    protected $restriction;

    protected $reason;

    protected $city;

    protected $reserve;

    protected $infra;

    /**
     * Gerador de Cartas prontas
     *
     * @param [type] $restriction
     * @param [type] $reason
     * @param [type] $buyer
     * @param [type] $client
     * @param [type] $protocol
     * @param [type] $lat
     * @param [type] $long
     * @param [type] $city
     * @param [type] $infra
     */
    public function __construct($restriction, $reason = null, $buyer = null, $client = null, $protocol = null, $lat = null, $long = null, $city = null, $infra = null, $reserve = null)
    {
        $this->restriction = $restriction;
        $this->reason      = $reason;
        $this->buyer       = $buyer;
        $this->client      = $client;
        $this->protocol    = $protocol;
        $this->lat         = $lat;
        $this->lon         = $long;
        $this->city        = $city;
        $this->infra       = $infra;
        $this->reserve     = $reserve;

    }

    public function Carta()
    {
        $carta['FUNAI']['FUNAI'] = '
    Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado, pelo seguinte motivo: 
O endereço informado se encontra em TERRA INDÍGENA e, conforme Resolução Normativa ANEEL n° 1000/2021, em seu artigo 67:        

“O consumidor e demais usuários devem fornecer as seguintes informações para a elaboração do orçamento prévio, no formulário disponibilizado pela distribuidora: 
II - para pessoa física, apresentação de: 

VIII - apresentação de licença ou declaração emitida pelo órgão competente se as instalações ou a extensão de rede de responsabilidade do consumidor e demais usuários ocuparem áreas protegidas pela legislação, tais como unidades de conservação, reservas legais, áreas de preservação permanente, territórios indígenas e quilombolas, entre outras; 

Dessa forma, pedimos que nos apresente ofício autorizativo da FUNAI em nome do solicitante. De posse do documento citado, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação. 

Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes). 

Atenciosamente,';

        $carta['LOTEAMENTO']['VILLAGE'] = "        
    Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado, pelo seguinte motivo:
Conforme parecer técnico APAS nº003-2017 processo 72708697 – Assunto:
Regularização do fornecimento de energia elétrica Loteamento Village do Sol, foi estabelecido pelo órgão ambiental estadual manifestação favorável as ligações de energia elétrica para as moradias já existentes até 02/10/2015 e não se estende às novas ocupações.
Por este motivo, sua solicitação encontra-se embargada pelo IEMA (Instituto Estadual de Meio Ambiente e Recursos Hídricos) através do parecer técnico acima mencionado.

Coordenadas UTM de localização/extensão da instalação = {$this->lat} m {$this->lon} m

Atenciosamente,";

        $carta['LOTEAMENTO']['BANANAL'] = "
    Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado, pelo seguinte motivo:
O endereço informado se encontra em ÁREA RESTRITA (LOTEAMENTO IRREGULAR).

Coordenadas UTM de localização/extensão da instalação = {$this->lat} m {$this->lon} m

Conforme ofício nº 0167/19 SEMAMA, para a continuidade do atendimento da ligação de energia, torna-se necessário V.Sa obter  autorização prévia junto à Secretaria  Municipal de Agricultura e Meio Ambiente, razão pela qual pedimos dirigir-se à citada Secretaria e obter o Requerimento específico.
De posse da autorização, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação.
Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes).

Atenciosamente,";

        $carta['LOTEAMENTO']['SERRA'] = "
    Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado, pelo seguinte motivo:
O endereço informado se encontra em ÁREA RESTRITA (LOTEAMENTO IRREGULAR).

Coordenadas UTM de localização/extensão da instalação = {$this->lat} m {$this->lon} m

Para a continuidade do atendimento da ligação, torna-se necessário V.Sa obter  autorização prévia junto à Secretaria  de Desenvolvimento Urbano da Prefeitura Municipal da Serra.
De posse da autorização, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação.
Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes).

Atenciosamente,";

        $carta['LOTEAMENTO']['DM'] = "
    Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado, pelo seguinte motivo:
O endereço informado se encontra em ÁREA RESTRITA (LOTEAMENTO IRREGULAR).

Coordenadas UTM de localização/extensão da instalação = {$this->lat} m {$this->lon} m

Para a continuidade do atendimento da ligação, torna-se necessário V.Sa obter  autorização prévia junto à Secretaria  de Meio Ambiente da Prefeitura Municipal de Domingos Martins
Dessa forma, pedimos dirigir-se à citada Secretaria munido dos seguintes documentos: Certidão atualizada do imóvel, documentos pessoais do requisitante, cadastro ambiental rural (CAR), alvará de obras emitido pela SECPDE e croqui ou planta do imóvel georreferenciado com memorial descritivo e ART do responsável técnico, com identificação dos recursos hídricos mais próximos.De posse da autorização, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação.
Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes).

Atenciosamente,";

        $carta['SEMMA']['DM'] = '
    Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado.
O endereço informado se encontra em Zona de Proteção Ambiental/Unidade de Conservação e, para o serviço solicitado, torna-se necessário que V.Sa. obtenha a autorização prévia junto à Secretaria de Meio Ambiente da Prefeitura Municipal de Domingos Martins
Conforme Resolução Normativa ANEEL n° 1000/2021, em seu artigo 67:
“O consumidor e demais usuários devem fornecer as seguintes informações para a elaboração do orçamento prévio, no formulário disponibilizado pela distribuidora:
II - para pessoa física, apresentação de:
VIII - apresentação de licença ou declaração emitida pelo órgão competente se as instalações ou a extensão de rede de responsabilidade do consumidor e demais usuários ocuparem áreas protegidas pela legislação, tais como unidades de conservação, reservas legais, áreas de preservação permanente, territórios indígenas e quilombolas, entre outras;
Dessa forma, pedimos dirigir-se à citada Secretaria munido dos seguintes documentos: Certidão atualizada do imóvel, documentos pessoais do requisitante, cadastro ambiental rural (CAR), alvará de obras emitido pela SECPDE e croqui ou planta do imóvel georreferenciado com memorial descritivo e ART do responsável técnico, com identificação dos recursos hídricos mais próximos.
De posse da autorização, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação.
Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes).

Atenciosamente,';

        $carta['SEMMA']['SERRA'] = '
    Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado.
O endereço informado se encontra em Zona de Proteção Ambiental/Unidade de Conservação e, para o serviço solicitado, torna-se necessário que V.Sa. obtenha a autorização prévia junto à Secretaria de Meio Ambiente da Prefeitura Municipal da Serra.
Conforme Resolução Normativa ANEEL n° 1000/2021, em seu artigo 67:
“O consumidor e demais usuários devem fornecer as seguintes informações para a elaboração do orçamento prévio, no formulário disponibilizado pela distribuidora:
II - para pessoa física, apresentação de:
VIII - apresentação de licença ou declaração emitida pelo órgão competente se as instalações ou a extensão de rede de responsabilidade do consumidor e demais usuários ocuparem áreas protegidas pela legislação, tais como unidades de conservação, reservas legais, áreas de preservação permanente, territórios indígenas e quilombolas, entre outras;
Dessa forma, pedimos dirigir-se à citada Secretaria munido do documento de identidade e o número de inscrição imobiliária do imóvel, ou carnê do IPTU.
De posse da autorização, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação.
Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes).

Atenciosamente,';

        $carta['LOTEAMENTO']['OUTROS'] = "
    Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado, pelo seguinte motivo:
O endereço informado se encontra em ÁREA RESTRITA (LOTEAMENTO IRREGULAR).

Coordenadas UTM de localização/extensão da instalação = {$this->lat} m {$this->lon} m

Para a continuidade do atendimento da ligação, torna-se necessário V.Sa obter  autorização prévia junto à Secretaria  de Desenvolvimento Urbano da Prefeitura Municipal de {$this->city}.
De posse da autorização, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação.
Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes).

Atenciosamente,";

        $carta['SEMMA']['OUTROS'] = "
    Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado.
O endereço informado se encontra em Zona de Proteção Ambiental/Unidade de Conservação e, para o serviço solicitado, torna-se necessário que V.Sa. obtenha a autorização prévia junto à Secretaria de Meio Ambiente da Prefeitura Municipal de {$this->city}.
Conforme Resolução Normativa ANEEL n° 1000/2021, em seu artigo 67:
“O consumidor e demais usuários devem fornecer as seguintes informações para a elaboração do orçamento prévio, no formulário disponibilizado pela distribuidora:
II - para pessoa física, apresentação de:
VIII - apresentação de licença ou declaração emitida pelo órgão competente se as instalações ou a extensão de rede de responsabilidade do consumidor e demais usuários ocuparem áreas protegidas pela legislação, tais como unidades de conservação, reservas legais, áreas de preservação permanente, territórios indígenas e quilombolas, entre outras;
Dessa forma, pedimos dirigir-se à citada Secretaria munido do documento de identidade e o número de inscrição imobiliária do imóvel, ou carnê do IPTU.
De posse da autorização, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação.
Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes).

Atenciosamente,";

        $carta['AMBIENTE']['IEMA'] = "
    Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado, pelo seguinte motivo: 
O endereço informado se encontra em Unidade de Conservação/Zona de Amortecimento Estadual {$this->reserve} e, conforme Resolução Normativa ANEEL n° 1000/2021, em seu artigo 67:          

'O consumidor e demais usuários devem fornecer as seguintes informações para a elaboração do orçamento prévio, no formulário disponibilizado pela distribuidora: 

II - para pessoa física, apresentação de: 
VIII - apresentação de licença ou declaração emitida pelo órgão competente se as instalações ou a extensão de rede de responsabilidade do consumidor e demais usuários ocuparem áreas protegidas pela legislação, tais como unidades de conservação, reservas legais, áreas de preservação permanente, territórios indígenas e quilombolas, entre outras; 

Dessa forma, para o serviço solicitado, é indispensável que V.Sa. obtenha autorização prévia junto ao IEMA, por isso, pedimos que acesse o site https://iema.es.gov.br/servicos_edocs/4 e registe a sua solicitação juntamente com a documentação listada abaixo, além desta carta. 

Documentos a serem apresentados: 

Carta EDP; 

Documento de identificação do requerente com foto e CPF/CNPJ; 
Documento de comprovação de vínculo do requerente com a titularidade ou posse da área/imóvel (proprietário, locatário, comodatário, arrendatário, etc) e tamanho da área/imóvel; 
Cadastro no CAR (no caso de imóvel rural);
Alvará de construção ou 'habite-se' ou certidão emitida pela prefeitura municipal que ateste a regularidade urbanística e ambiental do imóvel, (no caso de imóvel urbano); 
Informar telefone de contato e endereço de correspondência do (s) beneficiário (s) a que serão atendidos pela instalação. 

Descrição da instalação da rede/ infraestrutura pretendida = {$this->infra} 
Coordenadas UTM de localização/extensão da instalação = {$this->lat} m {$this->lon} m

De posse da autorização, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação. 
Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes). 


Atenciosamente,";

        $carta['AMBIENTE']['ICMBIO'] = "
    Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado, pelo seguinte motivo:
O endereço informado se encontra em Unidade de Conservação/Zona de Amortecimento Federal {$this->reserve} e, conforme Resolução Normativa ANEEL n° 1000/2021, em seu artigo 67:
“O consumidor e demais usuários devem fornecer as seguintes informações para a elaboração do orçamento prévio, no formulário disponibilizado pela distribuidora:
II - para pessoa física, apresentação de:
VIII - apresentação de licença ou declaração emitida pelo órgão competente se as instalações ou a extensão de rede de responsabilidade do consumidor e demais usuários ocuparem áreas protegidas pela legislação, tais como unidades de conservação, reservas legais, áreas de preservação permanente, territórios indígenas e quilombolas, entre outras;
Dessa forma, para o serviço solicitado, é indispensável que, V.Sa. obtenha autorização prévia junto ao ICMBio (Instituto Chico Mendes de Conservação da Biodiversidade). O protocolo no órgão deverá ser realizado eletronicamente, juntamente com esta carta, através do endereço: https://www.gov.br/pt-br/servicos/protocolar-documentos-junto-ao-instituto-chico-mendes-de-conservacao-da-biodiversidade-icmbio.
Descrição da instalação da rede/ infraestrutura pretendida = {$this->infra}
Coordenadas UTM de localização/extensão da instalação = {$this->lat} m {$this->lon} m
De posse da autorização, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação.
Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes).

Atenciosamente,
            ";

        $carta['DOCUMENTACAO']['CCIR'] = '
Documentação apresentada está incompleta, necessidade de apresentação do CCIR em nome do solicitante.

Atenciosamente,
            ';

        $carta['DOCUMENTACAO']['IPTU'] = "
Informamos que Vsª encontra-se em área urbana, dessa forma, pedimos que apresente o IPTU junto a Prefeitura Municipal de {$this->city}

Atenciosamente,
            ";

        $carta['DOCUMENTACAO']['CAR'] = '
Pedimos que Vsª Apresente o CAR (Cadastro Ambiental Rural), sinalizando a área do terreno para revisão da universalização.

Atenciosamente,
            ';

        $carta['DOCUMENTACAO']['ESCRITURA'] = "
    Não foi apresentado documento, com data, que comprove a propriedade ou posse do imóvel, tais como: escritura, documento formal de partilha homologado, 
contrato de compra e venda, todos devidamente registrado no cartório de imóveis ou CCIR (Certificado de Cadastro de Imóvel Rural), acompanhado de contrato de 
compra e venda, para os casos de parcelamento do solo. 
De posse do referido documento, V. Sa deverá comparecer em uma de nossas agências de atendimento, para abertura de nova solicitação.
Caso Vsª esteja localizado em zona urbana, pedimos que apresente o IPTU junto a Prefeitura Municipal de {$this->city}

            Atenciosamente,
            ";

        $carta['OUTROS']['CLOCALIZADO'] = '
    Não foi localizada a unidade consumidora a ser atendida. Sendo assim, será necessário o fornecimento de melhores referências físicas e/ou elétricas 
(nº de medidor/instalação vizinha) e outras formas de contato com o solicitante. 
De posse dessas informações, V. Sa deverá comparecer em uma de nossas agências de atendimento, para abertura de nova solicitação

Atenciosamente,
            ';

        $text = $carta[$this->restriction][$this->reason] ?? 'NÃO EXISTE CARTA PARA ESSA COMBINAÇÃO.';

        return "Prezado(a) Senhor(a) {$this->client}, \n" . $text;
    }
}
