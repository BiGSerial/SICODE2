<?php

namespace App\Http\Livewire\Partner\Actions;

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
        'ba7a1cd1b224c9f50a4745a56cafc9b4' => 'justify',
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


    public function goJustify()
    {
        $this->validate();

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'JUSTIFICATIVA AO VENCIMENTO TÁCITO',
            'msg'           => "<p class='fw-bold'>Você deseja enviar a justificativa para avaliação?</p>",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Envie!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'ba7a1cd1b224c9f50a4745a56cafc9b4',
            // 'chave'         => '',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Justificativa foi Enviada.',
        ]);

        return;
    }

    public function justify()
    {
        DB::beginTransaction();

        try {

            $this->viability->Justification()->updateOrCreate(
                [
                    'viability_id' => $this->viability->id,
                ],
                [
                'user_id' => auth()->id(),
                'justification' => $this->description,
                'justified_at' => now(),
                ]
            );




            if ($this->hasFile) {
                $this->emitTo('files.manager.create-gen-files', 'saveFiles');
            }


            DB::commit();

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

        if ($this->hasFile) {
            $this->emitTo('files.manager.create-gen-files', 'cleanFiles');
        }

        $this->emitUp('refresh_list');
        $this->dispatchBrowserEvent('hideModal');

    }

    public function render()
    {
        return view('livewire.partner.actions.tacit-justify');
    }
}
