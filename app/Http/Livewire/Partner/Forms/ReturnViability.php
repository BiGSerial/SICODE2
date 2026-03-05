<?php

namespace App\Http\Livewire\Partner\Forms;

use App\Models\Edp_depc\City;
use App\Models\Form;
use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReturnViability extends Component
{
    public $viability;
    public $cities;
    public $reason = null;
    public $hasFile = false;
    public $hasFVTO = false;

    // Controle
    public $changes;

    protected $listeners = [
        'openViability',
        'hasFile',
        'hasFVTO',
        'savedFiles' => 'closeAll',
        '71848750afd86770dfc52096788e78c6' => 'save',
    ];


    protected $rules = [
        'reason.reason' => 'required|string|in:AJUSTE MATERIAL,AJUSTE DE PROJETO,PROPOSTA MELHORIA',
        'reason.description' => 'required|string|max:1000',
        'reason.changes' => 'required|integer|min:0|max:10',
        'reason.responsible' => 'required|string|max:100',

    ];

    protected $messages = [
        'reason.reason.required' => 'O motivo da alteração é obrigatório.',
        'reason.reason.string' => 'O motivo da alteração deve ser um texto válido.',
        'reason.reason.in' => 'Escolha uma das opções válidas: AJUSTE MATERIAL, AJUSTE DE PROJETO ou PROPOSTA MELHORIA.',

        'reason.description.required' => 'A descrição é obrigatória.',
        'reason.description.string' => 'A descrição deve ser um texto válido.',
        'reason.description.max' => 'A descrição não pode ter mais de 1000 caracteres.',

        'reason.changes.required' => 'O nível de alteração é obrigatório.',
        'reason.changes.integer' => 'O nível de alteração deve ser um número inteiro.',
        'reason.changes.min' => 'O nível de alteração deve ser no mínimo 0.',
        'reason.changes.max' => 'O nível de alteração deve ser no máximo 10.',

        'reason.responsible.required' => 'O responsável pelo informe é obrigatório.',
        'reason.responsible.string' => 'O nome do responsável deve ser um texto válido.',
        'reason.responsible.max' => 'O nome do responsável não pode ter mais de 100 caracteres.',
    ];



    public function mount()
    {
        $this->cities = City::all();
    }

    public function hasFile($reason)
    {
        $this->hasFile = $reason;
    }

    public function hasFVTO($reason)
    {

        $this->hasFVTO = $reason;
    }

    public function openViability(Viability $viability)
    {
        $this->viability = $viability;

        if ($this->viability) {

            if ($this->viability->Form) {
                $this->reason = $this->viability->Form;
            } else {
                $this->reason = new Form();
            }

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'returnViabilityModal',
            ]);
        }
    }

    public function toSave()
    {
        if (!$this->hasFile) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ARQUIVO OBRIGATÓRIO',
                'html'     => " <div class='card text-bg-danger'><div class='card-body'>
                <p>É Obrigatório Anexar a Ficha de VIABILIDADE TÉCNICA DE EXECUÇÃO DE OBRAS, e demais documentos previsto no contrato nesta etapa.</p>
                </div></div> ",
            ]);

            return;

        }



        $this->dispatchBrowserEvent('alertar', [
            'title' => 'ENTREGAR ANALISE DE VIABILIDADE',
            'msg'   => "<p class='fw-bold'>Você deseja entregar esta análise de viabilidade?</p>
            <p class='text-center my-2'>
                A entrega desta analise seguirá para avaliação do corpo responsável, dependendo do resultado deste.
            </p>
            ",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Entregar Analise',
            'btnCanceltxt'  => 'Não, Desisto',
            'action'        => '71848750afd86770dfc52096788e78c6',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma analise foi salva.',
        ]);









    }


    public function save()
    {


        if ($this->changes === 'SIM') {
            $this->validate();

            // VIABILITY
            $this->viability->status = 4;
            $this->viability->rejected = true;
            $this->viability->approved = false;

            // FORM
            $this->reason->rejected = true;
            $this->reason->approved = false;

        } else {
            $this->validate([
                'reason.responsible' => 'required|string|max:100',
            ]);

            if ($this->viability->hired) {

                $this->viability->status = 9;
                $this->viability->completed = true;
                $this->viability->completed_at = date('Y-m-d H:i:s');

            } else {

                $this->viability->status = 14;
            }

            // VIABILITY
            $this->viability->rejected = false;
            $this->viability->approved = true;


            // FORM
            $this->reason->reason = "VIABILIZADO";
            $this->reason->description = "<< [by System] O Usuário indicou que não houveram alterações no projeto, e entende-se que o mesmo segue conforme o projeto. >>";
            $this->reason->rejected = false;
            $this->reason->approved = true;
        }

        // FORM
        $this->reason->user_id = Auth()->User()->id;

        // VIABILITY
        $this->viability->partner_id = Auth()->User()->id;
        $this->viability->returned_at = date('Y-m-d H:i:s');


        DB::beginTransaction();


        try {
            Form::updateOrCreate(['viability_id' => $this->viability->id], $this->reason->toArray());
            $this->viability->save();




            if ($this->hasFile) {

                if (!$this->hasFVTO) {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => '<i class="fas fa-exclamation-triangle text-warning"></i> Ficha de Viabilidade Técnica',
                        'html'     => "
                        <div class='alert alert-warning border-0 shadow-sm' style='border-radius: 12px;'>
                            <div class='d-flex align-items-center mb-3'>
                                <div class='flex-shrink-0 me-3'>
                                    <i class='fas fa-file-alt fa-2x text-warning'></i>
                                </div>
                                <div class='flex-grow-1'>
                                    <h6 class='mb-1 fw-bold text-dark'>Documento Obrigatório Não Identificado</h6>
                                    <small class='text-muted'>Ficha de Viabilidade Técnica de Execução de Obras</small>
                                </div>
                            </div>
                            <p class='mb-0 text-dark-emphasis'>
                                <i class='fas fa-info-circle me-2'></i>
                                Não foi possível identificar a <strong>Ficha de Viabilidade Técnica de Execução de Obras</strong>.
                                Por favor, selecione o tipo de arquivo (FICHA VIAB. TECNICA) correspondente e faça o upload novamente. Caso tenha anexado como outro tipo, remova o arquivo correspondente e anexe novamente com o tipo de envio correspondente.
                            </p>
                        </div>
                        ",
                    ]);

                    return;
                }

                DB::commit();

                $this->emitTo('files.manager.create-gen-files', 'saveFiles');

            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'ARQUIVO OBRIGATÓRIO',
                    'html'     => " <div class='card text-bg-danger'><div class='card-body'>
                    <div class='alert alert-danger border-0 shadow-sm' style='border-radius: 12px;'>
                        <div class='d-flex align-items-center mb-3'>
                            <div class='flex-shrink-0 me-3'>
                                <i class='fas fa-upload fa-2x text-danger'></i>
                            </div>
                            <div class='flex-grow-1'>
                                <h6 class='mb-1 fw-bold text-dark'>Arquivo Obrigatório</h6>
                                <small class='text-muted'>Documentação necessária para prosseguir</small>
                            </div>
                        </div>
                        <p class='mb-0 text-dark-emphasis'>
                            <i class='fas fa-exclamation-circle me-2'></i>
                            É obrigatório anexar a <strong>Ficha de Viabilidade Técnica de Execução de Obras</strong>
                            e demais documentos previstos no contrato nesta etapa.
                        </p>
                    </div>
                    </div></div> ",
                ]);

                return;
            }


        } catch (\Throwable $th) {
            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO NAS TRANSAÇÕES',
                'html'     => " <div class='card text-bg-danger'><div class='card-body'>
                <p>Ocorreu algum erro durante as transações das innformações. Verifique os dados e tente novamente.</p>

                <p class='text-bg-warning'>{$th->getMessage()}.</p>
                </div></div> ",
            ]);
        }

    }



    public function closeAll()
    {

        if ($this->hasFile) {
            $this->emitTo('files.manager.create-gen-files', 'cleanFiles');
        }

        $this->viability = null;
        $this->reason = null;
        $this->changes = null;
        $this->hasFile = false;
        $this->hasFVTO = false;
        $this->resetErrorBag();
        $this->resetValidation();


        $this->emitUp('refresh_list');
        $this->dispatchBrowserEvent('hideModal');

    }


    public function render()
    {
        return view('livewire.partner.forms.return-viability');
    }
}
