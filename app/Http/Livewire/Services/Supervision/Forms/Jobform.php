<?php

namespace App\Http\Livewire\Services\Supervision\Forms;

use App\Models\Analise;
use App\Models\D5Return;
use App\Models\Notetimeline;
use App\Models\Production;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Jobform extends Component
{
    public ?Production $production = null;
    public ?Analise $analise = null;

    public $hasFile = false;


    public $d5 = 2;
    public $return = [
        'note' => '',
        'reason' => '',
        'description' => ''
    ];

    protected $listeners = [
        'showProduction',
        'confirmFinish' => 'save',
        'hasFile',
        'savedFiles'
    ];

    protected $rules = [
        'analise.postes' => 'required|numeric|min:0',
        'analise.info' => 'nullable|string',
        'analise.conclusion' => 'required',

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
                session_start();
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
            session_start();
        }


        $_SESSION['waitingForm'] = true;


        $this->saveForm();
        $this->production->update([
            'status' => 27
        ]);
        $this->production->save();
        $this->emitUp('refresh_list');
        $this->dispatchBrowserEvent('hideModal');
    }

    public function to_finish()
    {


        if ($this->d5 == '1') {
            foreach ($this->return as $key => $value) {
                if ($value === null && $key != 'description') {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => 'Erros de Validação',
                        'html'     => '<div class="card"><div class="card-body text-start">O Campo em D5: ' . strToUpper($key) . ' é Obrigatório.</div></div>',
                    ]);

                    return;
                }
            }
        }

        if (!$this->analise->conclusion) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => '<div class="card"><div class="card-body text-start">O Campo Conclusão é Obrigatório.</div></div>',
            ]);

            return;
        }


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



        if ($this->production->partial) {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'ENCERRAMENTO DE SERVIÇO PARCIAL',
                'msg'   => "Você está prestes encerrar fiscalização Parcial de <strong>{$this->production->Note->note}</strong>.
                    <div class='card'>
                        <div class='card-body'>
                            Ao encerrar, entendemos que você seguiu todos os procedimentos em relação as transações no SAP.\n
                            Uma vez encerrado, essa operação nao poderá ser desfeita.
                            <h4 class='text-center'>DESEJA CONTINAR COM O ENCERRAMENTO DO SERVIÇO?</h4>
                        </div>
                    </div>
                ".$alert,
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirmFinish',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',

            ]);
        } else {
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
                ".$alert,
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirmFinish',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',

            ]);
        }


    }

    public function save()
    {
        $this->saveForm(true);

        DB::beginTransaction();

        try {
            $user = Auth()->User()->name;

            $chk = $this->production->update([
                'status'       => 5,
                'completed_at' => date('Y-m-d H:i:s'),
                'postes_u'     => $this->analise ? $this->analise->postes : null,
                'completed'    => true,
                'priority'     => false,

            ]);

            // Se for parcial, encerra a supervisão da parcial e libera para pagamento.
            if ($this->production->partial) {
                if ($partial = $this->production->Note->Partials->last()) {

                    if ($partial->allow && !$partial->supervision && !$partial->payment) {
                        $partial->update([
                            'supervision' => true,
                            'supervision_at' => date('Y-m-d H:i:s'),
                            'supervision_id' => Auth()->User()->id,
                        ]);
                    }
                }
            }

            if ($this->d5 == '1') {
                $d5 = D5Return::create([
                    'production_id' => $this->production->id,
                    'note_id' => $this->production->note_id,
                    'user_id'    => Auth()->User()->id,
                    'note' => $this->return['note'] ?? trim($this->return['note']),
                    'reason' => $this->return['reason'],
                    'description' => $this->return['description'] ?? trim($this->return['description']),

                ]);
            }

            Notetimeline::Create([
                'note_id'    => $this->production->note_id,
                'service_id' => $this->production->service_id,
                'user_id'    => Auth()->User()->id,
                'info'       => "Usuário {$user} encerrou a Nota/OV.",
                'status'     => 5,
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'ENVIADO COM SUCESSO',
            ]);

            // $this->emitTo('files.filesupervision', 'save_files');
            DB::commit();

            if ($this->hasFile) {
                $this->emitTo('files.manager.create-prod-files', 'saveFiles');
            } else {
                $this->closeAll();

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

    public function savedFiles()
    {

        $this->emitTo('files.manager.create-prod-files', 'cleanFiles');
        $this->closeAll();
    }

    public function closeAll()
    {
        $this->analise = null;
        $this->return = [
            'note' => null,
            'reason' => null,
            'description' => null
        ];


        $this->emitTo('services.supervision.main', 'refresh_list');
        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.services.supervision.forms.jobform');
    }
}
