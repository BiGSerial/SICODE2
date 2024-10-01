<?php

namespace App\Http\Livewire\Partner\Actions;

use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ViabResponse extends Component
{
    public $viability;
    public $description;


    protected $listeners = [
        'getResponse',
        '39beb0fd12279525c40b08540e0453a8' => 'granted',
        '5e268c029a73eda837695918a6a94597' => 'dismissed',
    ];

    protected $rules = [
        'description' => 'required|min:10',
    ];

    protected $messages = [
        'description.required' => 'A descrição é obrigatória.',
        'description.min' => 'O Texto é muito curto.',
    ];

    public function getResponse(Viability $viability)
    {

        $this->viability = $viability;

        if ($this->viability) {

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'response-viab-modal',
            ]);
        }
    }

    public function goGranted()
    {
        $this->validate();

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'RESPONDER DECISÂO DE VIABILIDADE',
            'msg'           => "<p class='fw-bold'>Você deseja aceitar a decisão?</p>",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Concorde!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => '39beb0fd12279525c40b08540e0453a8',
            // 'chave'         => '',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Resposta foi Enviada.',
        ]);

        return;
    }

    public function goDismissed()
    {
        $this->validate();

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'RESPONDER DECISÂO DE VIABILIDADE',
            'msg'           => "<p class='fw-bold'>Você deseja rejeitar a decisão?</p>",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Discorde!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => '5e268c029a73eda837695918a6a94597',
            // 'chave'         => '',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Resposta foi Enviada.',
        ]);

        return;
    }

    public function granted()
    {
        DB::beginTransaction();

        try {

            $this->viability->update([
                'status' => $this->viability->hired ? 9 : 14,
                'rejected' => false,
                'approved' => true,
                'parner_ok' => true,
                'treplica' => true,
                'completed' => $this->viability->hired ? true : false,
            ]);

            $this->viability->Comments()->create([
                'message' => $this->description,
                'user_id' => auth()->user()->id,
                'granted' => true,
                'dismissed' => false,
            ]);

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
                'html'     => 'Ocorreu um erro ao tentar aceitar a decisão de viabilidade.',
                'timer'    => 5000,
            ]);
        }
    }

    public function dismissed()
    {
        DB::beginTransaction();

        try {

            $this->viability->update([
                'status' => 4,
                'rejected' => true,
                'approved' => false,
                'parner_ok' => true,
                'treplica' => true,

            ]);

            $this->viability->Comments()->create([
                'message' => $this->description,
                'user_id' => auth()->user()->id,
                'granted' => false,
                'dismissed' => true,
            ]);

            DB::commit();

            $this->closeAll();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'OPERAÇÃO CONCLUIDA',
                'html'     => 'A decisão de viabilidade foi rejeitada com sucesso.',
                'timer'    => 5000,
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO AO CONCLUIR A TRANSAÇÂO',
                'html'     => 'Ocorreu um erro ao tentar rejeitar a decisão de viabilidade.',
                'timer'    => 5000,
            ]);

            return;
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
        return view('livewire.partner.actions.viab-response');
    }
}
