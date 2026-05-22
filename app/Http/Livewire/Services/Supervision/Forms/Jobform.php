<?php

namespace App\Http\Livewire\Services\Supervision\Forms;

use App\Models\Analise;
use App\Models\D5Return;
use App\Models\EvidenceFile;
use App\Models\FiveNote;
use App\Models\Notetimeline;
use App\Models\Production;
use App\Services\D5\D5WorkflowService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Jobform extends Component
{
    public ?Production $production = null;
    public ?Analise $analise = null;
    public $lastReturnwork = null;
    public $five;
    public $origin = 'FISCALIZACAO';

    public $hasFile = false;
    public $hasEvidence = false;


    public $d5 = 2;
    public $supervisionByPartnerPhotos = '';

    public $return = [
        // 'note' => '',
        'reason' => '',
        'description' => '',
        'loc_install' => '',
        'codify' => '',
        'sintoms' => '',
    ];

    protected $listeners = [
        'showProduction',
        'confirmFinish' => 'save',
        'hasFile',
        'hasEvidence',
        'evidenceSaved',
        'savedFiles'
    ];

    protected $rules = [
        'analise.postes' => 'required|numeric|min:0',
        'analise.info' => 'nullable|string',
        'analise.conclusion' => 'required',
        'return.loc_install' => 'nullable|string',
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

    public function hasEvidence($value)
    {
        $this->hasEvidence = $value;
    }


    public function downloadFile(EvidenceFile $file)
    {
        // dd(Storage::fileExists('public/'.$file->path));

        if (Storage::fileExists('public/'.$file->path)) {
            return Storage::download('public/'.$file->path);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ARQUIVO INEXISTENTE!',
                'timer'    => 5000,
            ]);

            return;
        }
    }

    public function deleteFile(EvidenceFile $file)
    {
        if ($file) {
            $file->delete();
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Arquivo removido com sucesso!',
            ]);
            $this->emit('refreshComponent');
        }
    }

    public function updatedD5($value)
    {

        $this->d5 = $value;
        if (!$value) {
            $this->return = [
                // 'note' => '',
                'reason' => '',
                'description' => '',
                'loc_install' => '',
                'codify' => '',
                'sintoms' => '',
            ];
        } else {
            if ($this->production->dfive) {
                $this->return['reason'] = $this->production->FiveNote->first()->reason;
                $this->return['description'] = $this->production->FiveNote->first()->description;
                $this->return['loc_install'] = $this->production->FiveNote->first()->loc_install;
                $this->return['codify'] = $this->production->FiveNote->first()->codify;
                $this->return['sintoms'] = $this->production->FiveNote->first()->sintoms;
            } else {
                $this->return['reason'] = '';
                $this->return['description'] = '';
                $this->return['loc_install'] = $this->production->Note->WorkForm?->Orders?->sortBy('ordem')->first()?->locInstalacao;
                $this->return['codify'] = '';
                $this->return['sintoms'] = '';
            }
        }
    }



    public function showProduction(Production $production)
    {

        $this->five = null;
        $this->lastReturnwork = null;
        $this->production = $production->load(
            'Note.WorkForm.Company',
            'Note.WorkForm.Orders',
            'Note.WorkForm.LatestReturnwork.User',
            'Note.fiveNote'
        );



        if ($this->production) {
            $this->lastReturnwork = $this->production->Note->WorkForm?->LatestReturnwork;

            $this->return['loc_install'] = $this->production->Note->WorkForm?->Orders?->sortBy('ordem')->first()?->loc_install ?? '';

            if ($this->production->dfive) {
                $this->d5 = 1;
                $this->five = $this->production->note->FiveNote;
            }

            if (isset($this->production->Analise)) {
                $this->analise = $this->production->Analise;
            } else {
                $this->analise = new Analise();
            }

            $this->supervisionByPartnerPhotos = is_null($this->production->supervision_by_partner_photos)
                ? ''
                : ($this->production->supervision_by_partner_photos ? '1' : '0');

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

            $time = 0;
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
        $this->production->update([
            'status' => 27
        ]);
        $this->production->save();
        $this->emitUp('refresh_list');
        $this->dispatchBrowserEvent('hideModal');
    }

    public function to_finish()
    {
        if (!in_array((string) $this->supervisionByPartnerPhotos, ['0', '1'], true)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => '<div class="card"><div class="card-body text-start">Informe se a fiscalização se deu por fotos da parceira.</div></div>',
            ]);

            return;
        }

        if ($this->production->dfive == true) {
            $this->validate([
                'analise.info' => 'required|min:10|string',

            ], [
                'analise.info.required' => 'O campo Observações é obrigatório quando a D5 já está em retorno. Detalhe o motivo do retorno.',
            ]);
        }

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



        $reviewAlert = $this->buildRevisionAlert();

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
                ".$reviewAlert.$alert,
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirmFinish',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',

            ]);
        } elseif ($this->analise->conclusion == 'OBRA NAO EXECUTADA') {

            $this->dispatchBrowserEvent('alertar', [
                'title' => 'ENCERRAMENTO DE SERVIÇO',
                'msg'   => "Você está prestes rejeitar a obra <strong>{$this->production->Note->note}</strong>.
                    <div class='card'>
                        <div class='card-body'>
                            Ao infomar que a obra não foi executada, o informe da parceira será rejeitado por não conformidade, entendemos que você seguiu todos os procedimentos, ANEXOU AS EVIDÊNCIAS e tomou as devidas providências em relação as transações no SAP.\n
                            Uma vez encerrado, essa operação nao poderá ser desfeita.
                            <h4 class='text-center'>DESEJA CONTINAR COM O ENCERRAMENTO DO SERVIÇO?</h4>
                        </div>
                    </div>
                ".$reviewAlert.$alert,
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
                ".$reviewAlert.$alert,
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirmFinish',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',

            ]);
        }


    }

    private function buildRevisionAlert(): string
    {
        $workForm = $this->production?->Note?->WorkForm;
        if (!$workForm || !$workForm->rejected) {
            return '';
        }

        $latestReturn = $workForm->LatestReturnwork ?? $workForm->Returnwork()->latest('id')->first();
        $category = e($latestReturn?->category ?? 'Não informado');
        $reason = nl2br(e($latestReturn?->text_obs ?? 'Não informado'));

        return "
            <div class='card border-warning my-2'>
                <div class='card-body text-start'>
                    <h5 class='mb-2 text-warning'>Informe em revisão</h5>
                    <p class='mb-1'><strong>Por quê:</strong> {$category}</p>
                    <p class='mb-1'><strong>Motivo:</strong></p>
                    <div class='bg-light border rounded p-2'>{$reason}</div>
                </div>
            </div>
        ";
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
                'supervision_by_partner_photos' => (string) $this->supervisionByPartnerPhotos === '1',

            ]);

            // Se for parcial, encerra a supervisão da parcial e libera para pagamento.
            if ($this->production->partial) {



                if ($this->analise->conclusion == 'reject') {

                    $text = $partial->engineer_info ?? '';
                    $text .= "\n ------------------------ \n" . "Nota/OV encerrada com rejeição em Fiscalização. \n" . "Motivo: " . $this->analise->info . "\n" . "Fiscal: " . auth()->user()->name;

                    $this->production->partialReject($text, false);

                } else {
                    $this->production->partialFiscalDone();


                }



            }

            if ($this->d5 == 1 || $this->production->dfive) {

                // $d5 = D5Return::create([
                //     'production_id' => $this->production->id,
                //     'note_id' => $this->production->note_id,
                //     'user_id'    => Auth()->User()->id,
                //     'note' => $this->return['note'] ?? trim($this->return['note']),
                //     'reason' => $this->return['reason'],
                //     'description' => $this->return['description'] ?? trim($this->return['description']),

                // ]);

                if (!$this->production->Note->FiveNote) {
                    $note = $this->production->Note;
                    $order = null;

                    if ($note) {
                        $order = $note->WorkForm?->Orders()->orderBy('ordem', 'asc')->first();
                        $workForm = $note->WorkForm;
                    }

                    $fiveNote = FiveNote::updateOrCreate(
                        [

                            'note_id' => $this->production->note_id
                        ],
                        [
                            'reason' => !$this->production->dfive ? $this->return['reason'] : $this->production->FiveNote->first()->reason,
                            'description' => !$this->production->dfive ? $this->return['description'] ?? $this->return['description'] : $this->production->FiveNote->first()->description,
                            'loc_install' => $this->return['loc_install'] ? trim($this->return['loc_install']) : null,
                            'conjunto' => $this->production->Note->num_material,
                            'pep' => $order?->pep,
                            'e_pep' =>  $order?->pep,
                            'company_id' => $this->production->Note?->WorkForm?->company_id,
                            'codify' => $this->return['codify'] ? trim($this->return['codify']) : null,
                            'sintoms' => $this->return['sintoms'] ? trim($this->return['sintoms']) : null,
                            'codify' => $this->return['codify'] ? trim($this->return['codify']) : null,
                        ]
                    );

                    if ($fiveNote) {
                        $fiveNote->Productions()->syncWithoutDetaching([$this->production->id]);

                        if ($fiveNote->wasRecentlyCreated) {
                            app(D5WorkflowService::class)->onCreatedFromSupervision(
                                $fiveNote,
                                auth()->id(),
                                $this->production
                            );
                        }
                    }
                } else {

                    if (!$this->five) {
                        $this->five = $this->production->note->FiveNote;
                    }

                    $fromStage = app(D5WorkflowService::class)->currentStage($this->five);

                    if ($this->analise->conclusion == 'FISCALIZADO COM PENDENCIAS') {
                        $this->five->update([
                            'is_completed' => false,
                            'completed_at' => null,
                            'returned'     => true,
                        ]);

                        app(D5WorkflowService::class)->onReturnedWithPending(
                            $this->five,
                            $fromStage,
                            auth()->id(),
                            $this->production
                        );
                    } else {
                        $this->five->update([
                            'is_supervisioned' => true,
                            'supervisioned_at' => now(),
                        ]);

                        app(D5WorkflowService::class)->onSupervisionApproved(
                            $this->five,
                            $fromStage,
                            auth()->id(),
                            $this->production
                        );
                    }

                    $this->five->Productions()->syncWithoutDetaching([$this->production->id]);

                }
            }

            if ($this->analise->conclusion == 'OBRA NAO EXECUTADA') {

                $wf = $this->production->Note->WorkForm;

                if ($wf) {
                    $wf->update([
                        'rejected' => true,
                        'approved' => false,
                        'informed_at' => null,
                    ]);


                    $wf->ReturnWork()->create([
                        'service_id' => $this->production->service_id,
                        'user_id'    => Auth()->User()->id,
                        'category'   => 'OBRA NÃO EXECUTADA',
                        'text_obs'   => 'Retorno via Fiscalização: ' . ($this->analise->info ?? 'Não informado.'),
                    ]);
                }




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

            if (isset($fiveNote) && $this->hasEvidence) {

                $this->emitTo('files.evidence.upload-evidence', 'saveEvidences', $fiveNote->id);
            } elseif ($this->hasFile) {

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

    public function evidenceSaved()
    {
        if ($this->hasFile) {
            $this->emitTo('files.manager.create-prod-files', 'saveFiles');
        } else {
            $this->closeAll();
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
        $this->five = null;
        $this->production = null;
        $this->lastReturnwork = null;
        $this->supervisionByPartnerPhotos = '';
        $this->return = [
            'reason' => '',
            'description' => '',
            'loc_install' => '',
            'codify' => '',
            'sintoms' => '',
        ];


        $this->emitTo('services.supervision.main', 'refresh_list');
        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.services.supervision.forms.jobform');
    }
}
