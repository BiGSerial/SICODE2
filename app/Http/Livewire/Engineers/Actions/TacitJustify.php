<?php

namespace App\Http\Livewire\Engineers\Actions;

use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TacitJustify extends Component
{
    public $viability;
    public $description;
    public $hasFile = false;


    protected $listeners = [
        'getResponse',
        'hasfile',
        'ba7a1cd1b224c9f50a4745a56cafc9b4' => 'deferir',
        'b8d2d6b1e8f1b6b1e8f1b6b1e8f1b6b1' => 'indeferir',
    ];

    public function hasFile($value)
    {
        $this->hasFile = $value;
    }

    protected $rules = [
        'description' => 'required|min:10',
    ];

    protected $messages = [
        'description.required' => 'A Justificativa é obrigatória.',
        'description.min' => 'O Texto é muito curto.',
    ];

    public function getResponse(Viability $viability)
    {

        $this->viability = $viability;

        if ($this->viability) {

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'tacitresponse-viab-modal',
            ]);
        }
    }


    public function goDeferir()
    {
        $this->validate();

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'AVALIAÇÂO AO VENCIMENTO TÁCITO',
            'msg'           =>  "<p class='fw-bold'>Você está Deferindo a Justificativa da parceira. O deferimento significa acatar a justificatva e não aplicar qualquer penalidades previstas em contrato à empresa parceira.</p>
                                <p class='fw-bold'>Deseja realmente continuar com o Deferimento? Gentileza ser claro quanto a sua decisão justificando detalhadamente a motivação da decisão.</p>",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Copntinue!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'ba7a1cd1b224c9f50a4745a56cafc9b4',
            // 'chave'         => '',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Justificativa foi Enviada.',
        ]);

        return;
    }


    public function goIndeferir()
    {
        $this->validate();

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'INDEFERINDO JUSTIFICATIVA',
            'msg'           => "<p class='fw-bold'>Você está Indeferindo a Justificativa da parceira. O indeferimento significa aplicar penalidades previstas em contrato à empresa parceira.</p>
                             <p class='fw-bold'>Deseja realmente continuar com o Indeferimento? Gentileza ser claro quanto a sua decisão justificando detalhadamente a motivação da decisão.</p>",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Continue!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'b8d2d6b1e8f1b6b1e8f1b6b1e8f1b6b1',
            // 'chave'         => '',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Justificativa foi Enviada.',
        ]);

        return;
    }

    public function deferir()
    {
        DB::beginTransaction();

        try {

            $this->viability->Justification->update(
                [
                'responsible_id' => auth()->id(),
                'response' => $this->description,
                'answered_at' => now(),
                'granted' => true,
                'dismissed' => false,
                ]
            );

            DB::commit();


            // if ($this->hasFile) {
            //     $this->emitTo('files.manager.create-gen-files', 'saveFiles');
            // }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'OPERAÇÃO CONCLUIDA',
                'html'     => 'A decisão de viabilidade foi aceita com sucesso.',
                'timer'    => 5000,
            ]);

            $this->closeAll();

        } catch (\Throwable $th) {
            DB::rollBack();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO AO CONCLUIR A TRANSAÇÂO',
                'html'     => 'Ocorreu um erro ao tentar enviar a Justificativa.',
                'timer'    => 5000,
            ]);
        }
    }


    public function indeferir()
    {
        DB::beginTransaction();

        try {

            $this->viability->Justification->update(
                [
                'responsible_id' => auth()->id(),
                'response' => $this->description,
                'answered_at' => now(),
                'granted' => false,
                'dismissed' => true,
                ]
            );

            DB::commit();


            // if ($this->hasFile) {
            //     $this->emitTo('files.manager.create-gen-files', 'saveFiles');
            // }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'OPERAÇÃO CONCLUIDA',
                'html'     => 'A decisão de viabilidade foi aceita com sucesso.',
                'timer'    => 5000,
            ]);

            $this->closeAll();

        } catch (\Throwable $th) {
            DB::rollBack();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO AO CONCLUIR A TRANSAÇÂO',
                'html'     => 'Ocorreu um erro ao tentar enviar a Justificativa.',
                'timer'    => 5000,
            ]);
        }
    }

    public function closeAll()
    {
        $this->description = '';
        $this->viability = null;
        $this->clearValidation();

        $this->emitUp('refresh');
        $this->dispatchBrowserEvent('hideModal');

    }

    public function render()
    {
        return view('livewire.engineers.actions.tacit-justify');
    }
}
