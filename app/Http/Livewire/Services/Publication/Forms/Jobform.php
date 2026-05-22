<?php

namespace App\Http\Livewire\Services\Publication\Forms;

use App\Models\Analise;
use App\Models\Notetimeline;
use App\Models\Production;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Jobform extends Component
{
    public ?Production $production = null;
    public ?Analise $analise = null;
    public $needFiles = false;
    public $hasFile = false;

    protected $listeners = [
        'showProduction',
        'confirmFinish' => 'save',
        'confirmParcial' => 'savePublish',
        'closeAll',
        'hasFile',
        'savedFiles',
        'continue' => 'toContinue',
    ];

    protected $rules = [
        'analise.postes' => 'required|numeric',
        'analise.info' => 'nullable|string',
        'analise.conclusion' => 'required|min:1'
    ];

    public function messages()
    {
        return [
            'analise.postes.required' => 'O campo [Qtd de Ativos] é obrigatório.',
            'analise.postes.numeric' => 'O campo [Qtd de Ativos] só aceita números.',
            'analise.conclusion.required' => 'O campo [Resultado] é Obrigatório.',
        ];
    }


    public function hasFile($value)
    {
        $this->hasFile = $value;
    }

    public function showProduction(Production $production)
    {
        $this->production = $production;

        if ($this->production) {

            if (isset($this->production->Analise)) {
                $this->analise = $this->production->Analise;
            } else {
                $this->analise = new Analise();
            }

            $this->status();

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'formProductionModal',
            ]);
        }
    }

    public function status()
    {
        if ($this->production->status != 4) {

            if (!(session_status() == PHP_SESSION_ACTIVE)) {
                if (!session()->isStarted()) { session()->start(); }
            }

            if (isset($_SESSION['waitingForm'])) {
                $_SESSION['waitingForm'] = false;
                unset($_SESSION['waitingForm']);
            }

            $this->production->update(['status' => 3]);
            $this->production->save();
        } else {
            $hist = Notetimeline::where('note_id', $this->production->note_id)->Where('service_id', $this->production->service_id)->where('status', 4)->orderBy('created_at', 'DESC')->first();

            if ($hist) {
                $time = (Carbon::parse($hist->created_at))->diffInSeconds(Carbon::now());
                $hist->update(['return_stop' => date('Y-m-d H:i:s')]);
            }

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
        }

        $this->emitUp('refresh_list');
    }

    public function saveForm($end = false)
    {


        try {
            if ($end) {
                $this->validate();
            }

            $this->production->Analise()->updateOrCreate([], $this->analise->toArray());

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'SALVO COM SUCESSO',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors()->all();
            $html = '<ul>';
            foreach ($errors as $error) {
                $html .= '<li>' . $error . '</li>';
            }

            $html .= '</ul>';

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => '<div class="card"><div class="card-body text-start">' . $html . '</div></div>',
            ]);

            return;
        }
    }

    public function waitingForm()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }


        $_SESSION['waitingForm'] = true;


        $this->saveForm();
        $this->production->update(['status' => 27]);
        $this->production->save();
        $this->emitUp('refresh_list');
        $this->dispatchBrowserEvent('hideModal');
    }

    public function to_finish()
    {

        if (!$this->analise->postes) {
            $alert = "
        <div class='card text-bg-danger py-0 my-1'>
            <div class='card-body'>
                <h4 class='fw-bold'>ATENÇÃO</h4>
                <p class='my-0'>Sua produção consta como <strong>ZERO</strong>. Este aviso é exibido mesmo que sua produção seja definida realmente como 0. Se não for seu caso, verifique novamente as informações inseridas e submeta novamente.</p>
            </div>
        </div>
    ";
        } else {
            $alert = "";
        }

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'ENCERRAMENTO DE SERVIÇO',
            'msg'   => "Você está prestes encerrar <strong>{$this->production->Note->note}</strong>.
                <div class='card'>
                    <div class='card-body'>
                        Ao encerrar, entendemos que você seguiu todos os procedimentos em relação as transações no SAP.\n
                        Uma vez encerrado, essa operação nao poderá ser desfeita.
                        <h4 class='text-center'>DESEJA CONTINAR COM O ENCERRAMENTO DO SERVIÇO?</h4>
                    </div>
                </div>
            " . $alert,
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Continue!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirmFinish',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Ação Cancelada.',

        ]);
    }

    public function to_Publish()
    {
        $this->dispatchBrowserEvent('alertar', [
            'title' => 'ENCERRAMENTO PARCIAL DE SERVIÇO',
            'msg'   => "Você está prestes encerrar parcialmente <strong>{$this->production->Note->note}</strong>.
            <div class='card text-bg-danger mt-2'>
                <h4 class='text-center my-2'> FAVOR NÃO CONFIRMAR A 20 NO SAP</h4>
            </div>
                <div class='card'>
                    <div class='card-body'>
                        <p class='text-justify'>Neste momento <strong>não existe confirmação de conclusão de obra</strong> por parte da parceira,
                        por isso, confirmaremos a publicação e esta obra aguardará a emissão do Informe de Obra da Parceira.</p>
                       <p class='text-justify'>Porém ela ficará em um status imutável até que a parceira confirme a conclusão da obra.
                        Está obra não fará parte da restrição de novas atribuições.</p>
                        <h4 class='text-center'>DESEJA CONTINAR COM O ENCERRAMENTO PARCIAL DO SERVIÇO?</h4>
                    </div>
                </div>
            ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Continue!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirmParcial',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Ação Cancelada.',

        ]);
    }

    public function save()
    {
        $this->saveForm(true);

        DB::beginTransaction();

        try {
            $chk = $this->production->update([
                'status'       => 5,
                'completed_at' => date('Y-m-d H:i:s'),
                'postes_u'     => $this->analise->postes ? $this->analise->postes : 0,
                'completed'    => true,
                'confirmed'    => false,
                'priority'     => false,
            ]);

            if ($chk) {
                $user = Auth()->User()->name;

                Notetimeline::Create([
                    'note_id'    => $this->production->note_id,
                    'service_id' => $this->production->service_id,
                    'user_id'    => Auth()->User()->id,
                    'info'       => "Usuário {$user} encerrou a Nota/OV.",
                    'status'     => 5,
                ]);

                DB::commit();

                 $this->emitTo('files.manager.create-prod-files', 'saveFiles');

                // $this->dispatchBrowserEvent('swal', [
                //     'position' => 'center',
                //     'icon'     => 'success',
                //     'title'    => 'Encerrado com Sucesso',
                //     'timer'    => 2500
                // ]);

                // $this->closeAll();
            }
        } catch (\Throwable $th) {

            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'NÃO FINALIZADO',
                'html'     => 'Não COnseguimos encerrar a atividade, tente novamente.<br>' . $th->getMessage(),
            ]);

            return;
        }
    }

    public function savePublish()
    {
        $this->saveForm(true);

        DB::beginTransaction();

        try {
            $chk = $this->production->update([
                'status'       => 28,
                // 'completed_at' => now(),
                'partial_at' => now(),
                'postes_u'     => $this->analise->postes ? $this->analise->postes : 0,
                'completed'    => false,
                'confirmed'    => false,
                'priority'     => false,
            ]);

            if ($chk) {
                $user = Auth()->User()->name;

                Notetimeline::Create([
                    'note_id'    => $this->production->note_id,
                    'service_id' => $this->production->service_id,
                    'user_id'    => Auth()->User()->id,
                    'info'       => "Usuário {$user} Publicou Parcial a Nota/OV.",
                    'status'     => 28,
                ]);

                DB::commit();

                $this->emitTo('files.manager.create-prod-files', 'saveFiles');

                // $this->dispatchBrowserEvent('swal', [
                //     'position' => 'center',
                //     'icon'     => 'success',
                //     'title'    => 'Publicado com Sucesso',
                //     'timer'    => 2500
                // ]);

                // $this->closeAll();
            }
        } catch (\Throwable $th) {

            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'NÃO PUBLICADO',
                'html'     => 'Não Conseguimos publicar a atividade, tente novamente.<br>' . $th->getMessage(),
            ]);

            return;
        }
    }

    public function savedFiles()
    {
        $this->closeAll();
        $this->emitTo('files.manager.create-prod-files', 'cleanFiles');


    }

    public function toContinue()
    {
        $this->closeAll();
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'ENCERRADO COM SUCESSO',
            'html'     => 'Nota/OV encerrada com sucesso.',
            'timer'   => 2500,
        ]);

    }


    public function closeAll()
    {
        $this->emitUp('refresh_list');
        $this->dispatchBrowserEvent('hideModal');
        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.services.publication.forms.jobform');
    }
}
