<?php

namespace App\Http\Livewire\Services\Publication\Forms;

use App\Helpers\SelectOptions;
use App\Models\{Analise as ModelsAnalise, Note, Notetimeline, Production, Reclaim};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class Analise extends Component
{
    use WithFileUploads;

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

    public $limit_pause = 5000;

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


    // Files
    public $files = [];

    public $needFiles = false;

    public $show_files = [];

    public $nota_divergente;

    protected $listeners = [
        'open_analise_draw' => 'openAnalise',
        'analise_clean'     => 'clean',
        'confirm_goFinish'  => 'goFinish',
        'clean' => 'clean',
        'hasFile' => 'hasFile',

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

            if ($this->production->d5) {

                if ($this->production->Reclaim->category && ($this->production->Reclaim->category != 'LIBERAR EO')) {
                    $this->conclusion = $this->production->Reclaim->category;
                    $this->needFiles = true;
                    $this->updatedConclusion();
                }
            }

            $this->view_form = true;
        }
    }

    // OPERAÇÕES COM ARQUIVOS
    public function updatedFiles()
    {

        try {
            $this->validate([
                'files.*' => 'mimes:pdf,jpeg,png,webp',
            ]);
        } catch (ValidationException $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'TIPO DE ARQUIVO NÃO PERMITIDO',
                'html'     => '<div class="card bg-primary text-white"><div class="card-body">Somente são aceitos arquivos: <span class="fw-bold">.pdf, .jpg, .png ou .webp</span> </div></div>',

            ]);

            return;
        }

        if (count($this->files)) {

            $this->show_files = [];

            foreach ($this->files as $index => $file) {

                $skip_file = false;

                if (!$skip_file) {


                    if (strpos(explode('.', $file->getClientOriginalName())[0], $this->production->Note->note) !== false) {
                        $this->nota_divergente = false;
                    } else {
                        $this->nota_divergente = true;
                    }

                    $this->show_files[$index] = [
                        'id'       => $index,
                        'note_id'  => '',
                        'name'     => explode('.', $file->getClientOriginalName())[0],
                        'old_name' => explode('.', $file->getClientOriginalName())[0],
                        'ext'      => $file->getClientOriginalExtension(),
                        'chk'      => false,
                    ];
                }
            }

        }
    }

    public function delete_file($id)
    {
        if (isset($this->show_files[$id])) {
            unset($this->files[$id]);
            unset($this->show_files[$id]);
        }

        if (!count($this->show_files)) {
            $this->reset('files');

        }

        $this->updatedFiles();
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

        if ($this->production->d5) {
            $this->info .= "\n";
            $this->info .= "Resolução Interna (RI): \n";
            $this->info .= $this->conclusion ."\n";
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

    public function cancel(Production $production)
    {
        $this->emit('cancel_files');
        $production->update([

        ]);
    }

    // Interação com o componante Livewire Files/Filesservice
    public function hasFile($hasFile = false)
    {
        $this->needFiles = $hasFile;
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



        if ($this->needFiles && SelectOptions::verifyNeedFilesReclaims($this->conclusion)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ARQUIVOS OBRIGATÓRIO',
                'html'     => '<div class="card"><div class="card-body"><p class="text-start">Para o tipo de RI (Resolução Interna) definido pelo solicitante, é obrigatório inserir o PDF do PROJETO em "ADICIONAR PROJETO".
                </p><p class="text-start">Caso a solicitação tenha sido "ALTERAR PROJETO", lembre-se de adicionar apenas o PDF mais RECENTE e todas as FOLHAS desse projeto no mesmo UPLOAD se aplicável.
                </p></div></div>',
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
        DB::beginTransaction();

        try {
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

                //Encerrar RI Caso existir
                if ($this->production->d5) {
                    $d5 = Reclaim::where('production_id', $this->production->id)->first();

                    if ($d5) {
                        $d5->update([
                            'completed' => true,
                            'completed_at' => date('Y-m-d H:i:s'),
                        ]);

                        if ($d5->Viabilities->count()) {
                            foreach ($d5->Viabilities as $viab) {
                                $viab->update([
                                    'status' => 13
                                ]);
                            }
                        }
                    }
                }



                // if (count($this->show_files)) {

                //     foreach ($this->show_files as $temp_file) {

                //         $caminho = '';

                //         if (isset($this->files[$temp_file['id']])) {

                //             $caminho = $this->files[$temp_file['id']]->store('/arquivos/projeto');

                //             if ($caminho) {

                //                 $this->production->Files()->create([
                //                     'note_id'   => $this->production->note_id,
                //                     'user_id'   => Auth()->User()->id,
                //                     'service_id'   => $this->production->service_id,
                //                     'file_name' => $temp_file['name'],
                //                     'path'      => $caminho,
                //                     'ext'       => $temp_file['ext'],
                //                 ]);

                //             }

                //         }

                //     }
                // }


                DB::commit();
                $this->emit('save_files');


            }

        } catch (\Throwable $th) {
            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'NÃO FINALIZADO',
                'html'     => 'Não COnseguimos encerrar a atividade, tente novamente.<br>'.$th->getMessage(),
            ]);

            return;
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
        return view('livewire.services.publication.forms.analise');
    }
}
