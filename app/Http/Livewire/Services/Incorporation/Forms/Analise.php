<?php

namespace App\Http\Livewire\Services\Incorporation\Forms;

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

    public $info_save;

    public $carta;

    public $card;

    public $alimentador;

    public $comprador;

    public $matricula;

    public $area;

    public $endereco;

    public $documento;

    public $count = 0;

    public $municipio;

    public $reserva;

    public $service_type;

    public $limit_pause = 50;

    public $production;

    public $note;

    public $analise;

    public $postes;

    public $postes_c;

    public $odi;

    public $odd;

    public $ods;

    public $cadastro;

    public $iproject;

    public $eo;

    public $preresult;

    protected $listeners = [
        'open_analise_incorporation' => 'openAnalise',
        'analise_clean'              => 'clean',
        'confirm_goFinish'           => 'goFinish',

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

        if ($this->analise) {

            $this->conclusion = $this->analise->conclusion;
            $this->info       = $this->analise->info;
            $this->postes     = $this->production->postes_u ? $this->production->postes_u : '';
            $this->odi        = $this->production->odi;
            $this->odd        = $this->production->odd;
            $this->ods        = $this->production->ods;
            $this->cadastro   = $this->production->cadastro;
            $this->iproject   = $this->production->iproject;
            $this->eo         = $this->production->eo;
            $this->postes_c   = $this->production->postes_c;
            $this->preresult  = $this->analise->preresult;

        } else {
            $this->clean_form();
            // ModelsAnalise::Create(['production_id' => $productionId]);
            $this->production->Analise()->create();
            $this->analise = ModelsAnalise::where('production_id', $productionId)->first();
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

    //     public function updatedConclusion($value)
    //     {

    //         $this->save_info();

    //         $text = "";

    //         if ($this->service_type == "ER") {
    //             $text = "
    // __________________________________________________

    // Comprador: {$this->comprador};
    // Matrícula: {$this->matricula} - em conformidade com o INCRA apresentado;
    // Área total: {$this->area} ha;
    // Localização do imóvel: {$this->endereco} - em conformidade com a informada no pedido;

    // Documento Apresentado: {$this->documento}

    // *****
    // Documentação válida para dar continuidade ao levantamento de campo;
    // Necessário informar a universalização no croqui/SAP para definição do custo;
    // *****

    // Instalação vizinha: {$this->nmedidor}
    // Coordenada: Lat {$this->lat} / Lon {$this->lon}
    // Alim: {$this->alimentador}
    // Tel.:

    // __________________________________________________
    //         ";
    //         }

    //         if ($this->service_type == "RR") {
    //             $text = "
    // __________________________________________________

    // Comprador: {$this->comprador};
    // Segue para levantamento de campo;

    // ****
    // Providenciar:
    // Croqui, fotos, GPS, parecer técnico e análise de risco, se necessário.
    // ****

    // Documentação válida para dar continuidade ao levantamento de campo.
    // Necessário informar a universalização no croqui/SAP para definição do custo.

    // Instalação: {$this->ninst};
    // Coordenada: Lat {$this->lat} / Lon {$this->lon};
    // Alim: {$this->alimentador};
    // Tel.:
    // __________________________________________________
    // ";
    //         }

    //         $this->info = $text;

    //     }

    public function updatedConclusion()
    {

        if ($this->preresult !== 'NORMAL' && $this->preresult !== 'REVALIDACAO') {
            $this->iproject = $this->eo = $this->cadastro = false;
            $this->postes   = 1;
            $this->odi      = '';
            $this->odd      = '';
            $this->ods      = '';
        }

        if ($this->conclusion === 'ARQUIVADO' || $this->conclusion === 'RETORNADO LEVANTAMENTO') {
            $this->iproject = $this->eo = $this->cadastro = false;
            $this->postes   = '';
            $this->odi      = '';
            $this->odd      = '';
            $this->ods      = '';
        }

        $this->info = '';

        if (trim($this->odi) != '') {
            $this->info .= 'ODI/DR - ' . $this->odi . "\n";
        }

        if (trim($this->odi) != '') {
            $this->info .= 'ODD/PEP - ' . $this->odd . "\n";
        }

        if (trim($this->odi) != '') {
            $this->info .= 'ODS - ' . $this->ods . "\n";
        }

        if (trim($this->postes) != '') {
            $this->info .= 'POSTES - ' . $this->postes . "\n";
        }

        if ($this->eo || $this->iproject || $this->cadastro) {
            $this->info .= "-------------------- \n";

            if ($this->eo) {
                $this->info .= "EO \n";
            }

            if ($this->iproject) {
                $this->info .= "iProject \n";
            }

            if ($this->cadastro) {
                $this->info .= "Acerto Cadastro: \n";
                $this->info .= 'POSTES: ' . $this->postes_c . "\n";
            }
        }
        $this->info .= "-------------------- \n";
        $this->info .= Auth()->User()->Registration . ' - ' . Auth()->User()->name . "\n";
        $this->info .= date('d/m/Y') . "\n";

    }

    public function save_info()
    {
        $chk = $this->analise->update([

            'conclusion' => $this->conclusion,
            'info'       => $this->info,
            'preresult'  => $this->preresult,
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

        if ($this->postes == '') {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'QUANTIDADE DE POSTES',
                'html'     => 'Você não informou a quantidade de postes levantados.
                ',
            ]);

            return;
        }

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
        $chk = $this->production->update([
            'status'       => 5,
            'completed_at' => date('Y-m-d H:i:s'),
            'postes_p'     => (int) $this->postes,
            'odi'          => $this->odi ? trim($this->odi) : null,
            'odd'          => $this->odd ? trim($this->odd) : null,
            'ods'          => $this->ods ? trim($this->ods) : null,
            'postes_u'     => $this->postes ? (int) $this->postes : 0,
            'cadastro'     => $this->cadastro ? true : false,
            'iproject'     => $this->iproject ? true : false,
            'eo'           => $this->eo ? true : false,
            'postes_c'     => $this->postes_c ? (int) $this->postes_c : 0,
            'completed'    => true,
            'confirmed'    => false,
            'priority'     => false,
            'status_note'  => ($this->note->nstats != $this->production->status_note) ? $this->note->nstats : $this->production->status_note,
        ]);

        if ($chk) {
            $user = Auth()->User()->name;

            Notetimeline::Create([
                'note_id'    => $this->note->id,
                'service_id' => $this->production->service_id,
                'user_id'    => Auth()->User()->id,
                'info'       => "Usuário {$user} encerrou a Nota/OV.",
                'status'     => 5,
            ]);

            $this->clean();
            $this->dispatchBrowserEvent('hideModal');
            $this->emit('refresh_accomany');
        }
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
        $this->postes      = '';

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
        $this->alimentador   = '';
        $this->comprador     = '';
        $this->matricula     = '';
        $this->area          = '';
        $this->endereco      = '';
        $this->postes        = '';
        $this->postes_c      = '';
        $this->odi           = '';
        $this->odd           = '';
        $this->ods           = '';
        $this->cadastro      = false;
        $this->iproject      = false;
        $this->eo            = false;

    }

    public function render()
    {
        return view('livewire.services.incorporation.forms.analise');
    }
}
