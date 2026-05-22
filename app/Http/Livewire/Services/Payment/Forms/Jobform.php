<?php

namespace App\Http\Livewire\Services\Payment\Forms;

use App\Models\Analise;
use App\Models\Company;
use App\Models\Notetimeline;
use App\Models\Production;
use App\Services\D5\D5WorkflowService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Jobform extends Component
{
    public ?Production $production = null;
    public ?Analise $analise = null;
    public $companies;
    public $five;

    protected $listeners = [
        'refresh' => '$refresh',
        'showProduction',
        'confirmFinish' => 'save',
    ];

    protected $rules = [
        'analise.postes' => 'required|numeric',
        'analise.info' => 'nullable|string',
        'analise.conclusion' => 'required|min:1',
        'five.note_d5' => 'required|numeric',
        'five.reason' => 'nullable|string',
        'five.description' => 'nullable|string',
        'five.loc_install' => 'nullable|string',
        'five.codify' => 'nullable|string',
        'five.sintoms' => 'nullable|string',
        'five.pep' => 'nullable|string',
        'five.conjunto' => 'nullable|string',
        'five.dispatch_at' => 'nullable|date',
        'five.supervisioned_at' => 'nullable|date',
        'five.payed_at' => 'nullable|date',
        'five.company_id' => 'required|exists:companies,id'



    ];

    public function messages()
    {
        return [
            'analise.postes.required' => 'O campo [Qtd de Ativos] é obrigatório.',
            'analise.postes.numeric' => 'O campo [Qtd de Ativos] só aceita números.',
            'analise.conclusion.required' => 'O campo [Resultado] é Obrigatório.',
            'five.note_d5.required' => 'O campo [Número da D5] é obrigatório.',
            'five.note_d5.numeric' => 'O campo [Número da D5] dev ser apenas números.',
            'five.note_d5.unique' => 'O D5 informado já está em uso.',
            'five.company_id.required' => 'O campo [Empresa] é obrigatório.',
        ];
    }

    public function mount()
    {
        $this->companies = Company::orderBy('name')->get();
    }

    public function showProduction(Production $production)
    {
        $this->five = null;
        $this->production = $production;

        if ($this->production) {

            if ($this->production->note->FiveNote?->exists()) {
                $this->five = $this->production->note->FiveNote;
            }

            // Garantir a existência de Analise
            $this->analise = $this->production->Analise()->firstOrCreate(
                [], // Condições de busca (vazias pois já está relacionado à production)
                [   // Valores padrão caso precise criar
                    'postes' => 0,
                    'info' => null,
                    'conclusion' => null,
                ]
            );

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
            } else {
                $time = 0;
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
                if ($this->five) {
                    $this->validate();
                } else {
                    $this->validate([
                        'analise.postes' => 'required|numeric',
                        'analise.info' => 'nullable|string',
                        'analise.conclusion' => 'required|min:1',
                    ]);
                }
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
        $this->validate(['analise.conclusion' => 'required|min:1']);

        if ($this->production->partial) {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'ENCERRAMENTO DE SERVIÇO PARCIAL',
                'msg'   => "Você está prestes encerrar o pagamento Parcial de <strong>{$this->production->Note->note}</strong>.
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
                'action'        => 'confirmFinish',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',

            ]);
        } else {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'ENCERRAMENTO DE SERVIÇO',
                'msg'   => "Você está prestes encerrar o pagamento de <strong>{$this->production->Note->note}</strong>.
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
            $chk = $this->production->update([
                'status'       => 5,
                'completed_at' => date('Y-m-d H:i:s'),
                'postes_u'     => $this->analise->postes ? $this->analise->postes : 0,
                'completed'    => true,
                'confirmed'    => false,
                'priority'     => false,
            ]);

            if ($this->production->partial) {

                if ($partial = $this->production->Note->Partials->sortByDesc('created_at')->first()) {


                    if ($partial->allow && !$partial->deny && $partial->supervision && !$partial->payment) {
                        $partial->update([
                            'payment' => true,
                            'complete' => true,
                            'payment_at' => date('Y-m-d H:i:s'),
                            'payment_id' => Auth()->User()->id,
                        ]);
                    }
                }
            }


            if ($this->five) {
                $fromStage = app(D5WorkflowService::class)->currentStage($this->five);

                if (!$this->five->is_supervisioned) {
                    $this->validate([
                        'five.note_d5' => 'required|numeric',
                        'five.company_id' => 'required|exists:companies,id'
                    ]);
                    $this->five->dispatch_at = now();
                    $this->five->visible_partner = true;
                    $this->five->is_payed = true;
                    $this->five->payed_at = now();

                } else {
                    $this->five->is_archived = true;
                }

                $this->five->save();
                if (!$this->five->is_supervisioned) {
                    app(D5WorkflowService::class)->onReleasedToPartner(
                        $this->five,
                        $fromStage,
                        auth()->id(),
                        $this->production
                    );
                } else {
                    app(D5WorkflowService::class)->onArchived(
                        $this->five,
                        $fromStage,
                        auth()->id(),
                        $this->production
                    );
                }

                $this->five->Productions()->syncWithoutDetaching([$this->production->id]);
            }

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


                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Encerrado com Sucesso',
                ]);

                $this->closeAll();
            }

        } catch (\Throwable $th) {

            DB::rollback();

            $this->addError('general', 'Não conseguimos encerrar a atividade. '.$th->getMessage());

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'NÃO FINALIZADO',
                'html'     => 'Não Conseguimos encerrar a atividade, tente novamente.<br>' . $th->getMessage(),
            ]);

            $this->emitSelf('refresh');

            return;
        }
    }

    public function closeAll()
    {
        $this->production = null;
        $this->analise = null;
        $this->five = null;
        $this->emitUp('refresh_list');
        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.services.payment.forms.jobform');
    }
}
