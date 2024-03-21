<?php

namespace App\Http\Livewire\Services\Analisesinc\Forms;

use App\Models\{Analise as ModelsAnalise, Note, Notetimeline, Production};
use Carbon\Carbon;
use Livewire\Component;

class Analise extends Component
{
    public $view_form = false; // Ou o valor inicial que desejar

    public $ninst;

    public $nmedidor;

    public $patrimonio;

    public $lat;

    public $lon;

    public $carga_ini;

    public $carga_fim;

    public $queda;

    public $queda_max;

    public $queda_cliente;

    public $vao;

    public $restriction;

    public $motivo;

    public $conclusion;

    public $info;

    public $carta;

    public $card;

    public $count = 0;

    public $municipio;

    public $reserva;

    public $limit_pause = 3;

    public $production;

    public $note;

    public $mmgd;

    public $analise;

    protected $listeners = [
        'open_analise_inc' => 'openAnalise',
        'analise_clean'    => 'clean',
        'confirm_goFinish' => 'goFinish',

    ];

    public function openAnalise($data)
    {
        $this->clean();
        $this->clean_form();

        $productionId = $data['productionId'];
        $noteId       = $data['noteId'];

        $this->production = Production::find($productionId);
        $this->note       = Note::find($noteId);

        // Verficando a existencia de uma analise ja atriobuida para esta produção
        $this->analise = ModelsAnalise::where('production_id', $productionId)->first();

        if (!$this->analise) {
            $this->clean_form();
            $this->production->Analise()->create();
            $this->analise = ModelsAnalise::where('production_id', $productionId)->first();
        }

        if ($this->analise) {
            $this->ninst         = $this->analise->ninst;
            $this->nmedidor      = $this->analise->nMedidor;
            $this->patrimonio    = $this->analise->patrimonio;
            $this->lat           = $this->analise->lat;
            $this->lon           = $this->analise->lon;
            $this->carga_ini     = $this->analise->carga_ini + 0.00;
            $this->carga_fim     = $this->analise->carga_fim + 0.00;
            $this->queda         = $this->analise->queda + 0.00;
            $this->queda_max     = $this->analise->queda_max + 0.00;
            $this->queda_cliente = $this->analise->queda_cliente + 0.00;
            $this->vao           = $this->analise->vao;
            $this->restriction   = $this->analise->restricao;
            $this->motivo        = $this->analise->motivo;
            $this->conclusion    = $this->analise->conclusion;
            $this->info          = $this->analise->info;
            $this->card          = $this->analise->card;
        }

        if ($this->production && $this->note) {

            $time = 0;

            if ($this->production->status === 4) {
                $hist = Notetimeline::where('note_id', $this->production->note_id)->Where('service_id', $this->production->service_id)->where('status', 4)->orderBy('created_at', 'DESC')->first();

                if ($hist) {
                    $time = (Carbon::parse($hist->created_at))->diffInSeconds(Carbon::now());
                    $hist->update(['return_stop' => date('Y-m-d H:i:s')]);
                }

            }
            // Coloca nota em andamento
            $update = $this->production->update([
                'status'  => 3,
                'stopped' => $this->production->stopped + $time,
            ]);

            if ($update && $this->production->status !== 3) {
                // Registra Movimento Nota
                $user = Auth()->User()->name;

                Notetimeline::Create([
                    'note_id'      => $this->note->id,
                    'service_id'   => $this->production->service_id,
                    'user_id'      => Auth()->User()->id,
                    'info'         => "Usuário {$user} iniciou a Nota/OV.",
                    'status'       => 3,
                    'productionId' => $this->production->id,
                ]);
            }

            $this->view_form = true;
        }
    }

    public function save_info()
    {

        $chk = $this->analise->update([
            'ninst'         => $this->ninst ? $this->ninst : null,
            'nMedidor'      => $this->nmedidor ? $this->nmedidor : null,
            'patrimonio'    => $this->patrimonio,
            'lat'           => $this->lat,
            'lon'           => $this->lon,
            'carga_ini'     => $this->carga_ini ? (float) $this->carga_ini : 0.00,
            'carga_fim'     => $this->carga_fim ? (float) $this->carga_fim : 0.00,
            'queda'         => $this->queda ? (float) $this->queda : 0.00,
            'queda_max'     => $this->queda_max ? (float) $this->queda_max : 0.00,
            'queda_cliente' => $this->queda_cliente ? (float) $this->queda_cliente : 0.00,
            'vao'           => $this->vao ? $this->vao : 0,
            'restricao'     => $this->restriction,
            'motivo'        => $this->motivo,
            'conclusion'    => $this->conclusion,
            'info'          => $this->info,
            'card'          => $this->card,

        ]);
    }

    public function to_pause()
    {
        $this->save_info();

        $this->count = Production::Where('status', 4)->Where('service_id', $this->production->service_id)->Where('user_id', Auth()->User()->id)->count();

        if ($this->count === $this->limit_pause) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'LIMITE ATINGIDO',
                'html'     => "Você atingiu o limite máximo de pausas. Não é possível interromper esta nota. \n
                    <p class='text-bg-light mt-2 p-2'>
                        É importante salientar que existe um limite para interromper notas. Uma vez atingido esse limite, essas notas deverão ter uma destinação
                        adequada. 
                    </p>
                ",
            ]);

            return;
        }

        $this->emit('stop_note', ['productionId' => $this->production->id, 'noteId' => $this->production->note_id, 'limit' => $this->limit_pause]);

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'pause_note',
        ]);
    }

    public function to_finish(Production $production)
    {
        $this->save_info();
        $this->production = $production;
        $this->note       = Note::find($this->production->note_id);

        if (!$this->conclusion) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'CONCLUSÃO NÃO DEFINIDA',
                'html'     => 'Você não definiu uma conclusão para a nota/ov em questão. Gentileza concluir a análise da mesma.
                ',
            ]);

            return;
        }

        if (!$this->mmgd) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'INFORMAÇÃO OBIRGATÓRIA',
                'html'     => 'Obrigatório informar MMGD
                ',
            ]);

            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'ENCERRAMENTO DE SERVIÇO',
            'msg'   => "Você está prestes encerrar <strong>{$this->note->note}</strong>.
                <div class='card'>
                    <div class='card-body'>
                        Ao encerrar, entendemos que você seguiu todos os procedimentos em relação as transações no SAP.\n
                        Uma vez encerrado, essa operação nao poderá ser desfeita. 
                        <h4 class='text-center'>DESEJA CONTINAR COM O ENCERRAMENTO DO SERVIÇO?</h4>
                    </div>
                </div>
            ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Continue!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_goFinish',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Ação Cancelada.',

        ]);
    }

    public function goFinish()
    {
        $mmgd = false;

        if ($this->mmgd) {

            if ($this->mmgd === 'SIM') {
                $mmgd = true;
            }
        }

        $chk = $this->production->update([
            'status'       => 5,
            'completed_at' => date('Y-m-d H:i:s'),
            'completed'    => true,
            'confirmed'    => false,
            'mmgd'         => $mmgd,
            'status_note'  => ($this->note->nstats != $this->production->status_note) ? $this->note->nstats : $this->production->status_note,
        ]);

        if ($chk) {
            $user = Auth()->User()->name;

            Note::find($this->note->id)->update(['mmgd' => $mmgd]);

            Notetimeline::Create([
                'note_id'      => $this->note->id,
                'service_id'   => $this->production->service_id,
                'user_id'      => Auth()->User()->id,
                'info'         => "Usuário {$user} encerrou a Nota/OV.",
                'status'       => 5,
                'productionId' => $this->production->id,
            ]);

            $this->clean();
            $this->dispatchBrowserEvent('hideModal');
            $this->emit('refresh_accomany');
        }
    }

    public function gerarCarta($res, $sub)
    {
        $carta['FUNAI']['FUNAI'] = "
            Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado, pelo seguinte motivo:
            O endereço informado se encontra em TERRA INDÍGENA e, conforme Resolução Normativa ANEEL n° 1000/2021, em seu artigo 67:\n
            “O consumidor e demais usuários devem fornecer as seguintes informações para a elaboração do orçamento prévio, no formulário disponibilizado pela distribuidora:
            II - para pessoa física, apresentação de:
            VIII - apresentação de licença ou declaração emitida pelo órgão competente se as instalações ou a extensão de rede de responsabilidade do consumidor e demais usuários ocuparem áreas protegidas pela legislação, tais como unidades de conservação, reservas legais, áreas de preservação permanente, territórios indígenas e quilombolas, entre outras;
            Dessa forma, pedimos que nos apresente ofício autorizativo da FUNAI em nome do solicitante. De posse do documento citado, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação.
            Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes).
            
            Atenciosamente,";

        $carta['LOTEAMENTO']['VILLAGE'] = '        
        Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado, pelo seguinte motivo:
        Conforme parecer técnico APAS nº003-2017 processo 72708697 – Assunto:
        Regularização do fornecimento de energia elétrica Loteamento Village do Sol, foi estabelecido pelo órgão ambiental estadual manifestação favorável as ligações de energia elétrica para as moradias já existentes até 02/10/2015 e não se estende às novas ocupações.
        Por este motivo, sua solicitação encontra-se embargada pelo IEMA (Instituto Estadual de Meio Ambiente e Recursos Hídricos) através do parecer técnico acima mencionado.
        Atenciosamente,';

        $carta['LOTEAMENTO']['BANANAL'] = '
        Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado, pelo seguinte motivo:
        O endereço informado se encontra em ÁREA RESTRITA (LOTEAMENTO IRREGULAR).
        Conforme ofício nº 0167/19 SEMAMA, para a continuidade do atendimento da ligação de energia, torna-se necessário V.Sa obter  autorização prévia junto à Secretaria  Municipal de Agricultura e Meio Ambiente, razão pela qual pedimos dirigir-se à citada Secretaria e obter o Requerimento específico.
        De posse da autorização, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação.
        Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes).
        
        Atenciosamente,';

        $carta['LOTEAMENTO']['SERRA'] = '
        Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado, pelo seguinte motivo:
        O endereço informado se encontra em ÁREA RESTRITA (LOTEAMENTO IRREGULAR).
        Para a continuidade do atendimento da ligação, torna-se necessário V.Sa obter  autorização prévia junto à Secretaria  de Desenvolvimento Urbano da Prefeitura Municipal da Serra.
        De posse da autorização, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação.
        Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes).
        
        Atenciosamente,';

        $carta['LOTEAMENTO']['DM'] = '
        Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado, pelo seguinte motivo:
        O endereço informado se encontra em ÁREA RESTRITA (LOTEAMENTO IRREGULAR).
        Para a continuidade do atendimento da ligação, torna-se necessário V.Sa obter  autorização prévia junto à Secretaria  de Meio Ambiente da Prefeitura Municipal de Domingos Martins
        Dessa forma, pedimos dirigir-se à citada Secretaria munido dos seguintes documentos: Certidão atualizada do imóvel, documentos pessoais do requisitante, cadastro ambiental rural (CAR), alvará de obras emitido pela SECPDE e croqui ou planta do imóvel georreferenciado com memorial descritivo e ART do responsável técnico, com identificação dos recursos hídricos mais próximos.De posse da autorização, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação.
        Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes).
        
        Atenciosamente,';

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

        $municipio = $this->note->lexp ? $this->note->lexp : $this->municipio;

        $carta['LOTEAMENTO']['OUTROS'] = "
        Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado, pelo seguinte motivo: 
        O endereço informado se encontra em ÁREA RESTRITA (LOTEAMENTO IRREGULAR).

        Para a continuidade do atendimento da ligação de energia, torna-se necessário V.Sa obter  autorização prévia junto à Prefeitura Municipal de {$municipio}, por meio da respectiva Secretaria  responsável pela regularização fundiária. 
        De posse da autorização, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação. 
        Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes). 
            ";

        $carta['SEMMA']['OUTROS'] = "
        Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado.
        O endereço informado se encontra em Zona de Proteção Ambiental/Unidade de Conservação e, para o serviço solicitado, torna-se necessário que V.Sa. obtenha a autorização prévia junto à Secretaria de Meio Ambiente da Prefeitura Municipal de {$municipio}.
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
            O endereço informado se encontra em Unidade de Conservação/Zona de Amortecimento Estadual {$this->reserva}, conforme Resolução Normativa ANEEL n° 1000/2021, em seu artigo 67:
            “O consumidor e demais usuários devem fornecer as seguintes informações para a elaboração do orçamento prévio, no formulário disponibilizado pela distribuidora:

            II - para pessoa física, apresentação de:
            VIII - apresentação de licença ou declaração emitida pelo órgão competente se as instalações ou a extensão de rede de responsabilidade do consumidor e demais usuários ocuparem áreas protegidas pela legislação, tais como unidades de conservação, reservas legais, áreas de preservação permanente, territórios indígenas e quilombolas, entre outras;
            Dessa forma, para o serviço solicitado, é indispensável que V.Sa. obtenha autorização prévia junto ao IEMA, por isso, pedimos que acesse o site https://acessocidadao.es.gov.br/Conta/Entrar/E-DOCS/NOVO/ENCAMINHAMENTO e registe a sua solicitação juntamente com a documentação listada abaixo, além desta carta.
            Documentos a serem apresentados:
            1.       Carta EDP;
            2.       Documento de identificação do requerente com foto e CPF/CNPJ;
            3.       Documento de comprovação de vínculo do requerente com a titularidade ou posse da área/imóvel (proprietário, locatário, comodatário, arrendatário, etc) e tamanho da área/imóvel;
            4.       Cadastro no CAR (no caso de imóvel rural);
            5.       Alvará de construção ou '''habite-se''' ou certidão emitida pela prefeitura municipal que ateste a regularidade urbanística e ambiental do imóvel, (no caso de imóvel urbano);
            6.       Informar telefone de contato e endereço de correspondência do (s) beneficiário (s) a que serão atendidos pela instalação.
            Descrição da instalação da rede/ infraestrutura pretendida = {$this->note->group1} 
            Coordenadas UTM de localização/extensão da instalação = {$this->lat} m {$this->lon} m
            De posse da autorização, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação.
            Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes).

            Atenciosamente,";

        $carta['AMBIENTE']['ICMBIO'] = "

            Em atenção à solicitação de V.Sª., informamos que não foi possível dar sequência no protocolo solicitado, pelo seguinte motivo:
            O endereço informado se encontra em Unidade de Conservação/Zona de Amortecimento Federal {$this->reserva} e, conforme Resolução Normativa ANEEL n° 1000/2021, em seu artigo 67:
            “O consumidor e demais usuários devem fornecer as seguintes informações para a elaboração do orçamento prévio, no formulário disponibilizado pela distribuidora:
            II - para pessoa física, apresentação de:
            VIII - apresentação de licença ou declaração emitida pelo órgão competente se as instalações ou a extensão de rede de responsabilidade do consumidor e demais usuários ocuparem áreas protegidas pela legislação, tais como unidades de conservação, reservas legais, áreas de preservação permanente, territórios indígenas e quilombolas, entre outras;
            Dessa forma, para o serviço solicitado, é indispensável que, V.Sa. obtenha autorização prévia junto ao ICMBio (Instituto Chico Mendes de Conservação da Biodiversidade). O protocolo no órgão deverá ser realizado eletronicamente, juntamente com esta carta, através do endereço: https://www.gov.br/pt-br/servicos/protocolar-documentos-junto-ao-instituto-chico-mendes-de-conservacao-da-biodiversidade-icmbio.
            Descrição da instalação da rede/ infraestrutura pretendida = {$this->note->group1}
            Coordenadas UTM de localização/extensão da instalação = {$this->lat} m {$this->lon} m
            De posse da autorização, pedimos retornar a uma das Agências de Atendimento ao Cliente da EDP ES, para formalizar nova solicitação.
            Esclarecimentos adicionais poderão ser obtidos pelos telefones 0800 721 0707 (Atendimento Clientes Baixa Tensão) ou 0800 721 5671 (Atendimento Poder Público e Grandes Clientes).

            Atenciosamente,
            ";

        $this->card = "Prezado(a) Senhor(a) {$this->note->client}, \n" . $carta[$res][$sub];

    }

    public function clean()
    {
        $this->production  = null;
        $this->note        = null;
        $this->motivo      = null;
        $this->info        = null;
        $this->restriction = null;
        $this->card        = null;
        $this->view_form   = false;

    }

    public function clean_form()
    {
        $this->ninst         = '';
        $this->nmedidor      = '';
        $this->patrimonio    = '';
        $this->lat           = '';
        $this->lon           = '';
        $this->carga_ini     = '';
        $this->carga_fim     = '';
        $this->queda         = '';
        $this->queda_max     = '';
        $this->queda_cliente = '';
        $this->vao           = '';
        $this->restriction   = '';
        $this->motivo        = '';
        $this->conclusion    = '';
        $this->info          = '';
        $this->card          = '';

    }

    public function render()
    {
        return view('livewire.services.analisesinc.forms.analise');
    }
}
