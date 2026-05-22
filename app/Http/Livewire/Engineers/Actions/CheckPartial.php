<?php

namespace App\Http\Livewire\Engineers\Actions;

use App\Models\Partial;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CheckPartial extends Component
{
    public ?Partial $form = null;

    public $engineer_feedback;

    protected $listeners = [
        'show_form',
        'confirm_approved',
        'confirm_reject'
    ];

    public function show_form(Partial $form)
    {

        $this->form = $form->load([
            'Note',
            'Company',
            'User',
            'Engineer',
            'Supervisor',
            'Payer',
            'Orders',
            'Files.Service',
        ]);

        // dd($this->form);

        if ($this->form) {



            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_partial_info',
            ]);
        }
    }

    public function toApprove()
    {
        if (!trim($this->engineer_feedback)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'PARECER OBIGATÓRIO',
                'html'     => 'O parecer do engenheiro é obrigatório para aprovação.',
                'timer'    => 5000,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'APROVAR OBRA PARCIAL',
            'msg'   => "
            Você deseja dar seguimento da obra {$this->form->note->note} parcialmente?</br></br>
            <div class='card card-light'>
            <div class='card-body'>
            <p>A obra será liberada para Fiscalização e posteriormente para Pagamento.</p>
            </div>
            </div>
            ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Aprovar!',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'confirm_approved',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma solicitação foi aprovada.',

        ]);
    }

    public function toReject()
    {
        if (!trim($this->engineer_feedback)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'PARECER OBIGATÓRIO',
                'html'     => 'O parecer do engenheiro é obrigatório para rejeição.',
                'timer'    => 5000,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Rejeitar OBRA PARCIAL',
            'msg'   => "
            Você deseja rejeitar obra {$this->form->note->note} parcial?</br></br>
            <div class='card card-light'>
            <div class='card-body'>
            <p>A requisição será reprovada, permitindo um novo envio por parte da parceira.</p>
            </div>
            </div>
            ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'confirm_reject',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Solicitação foi Rejeitada.',

        ]);
    }

    public function confirm_approved()
    {

        DB::beginTransaction();

        try {
            $this->form->update([
                'engineer_id' => auth()->id(),
                'engineer_info' => $this->engineer_feedback,
                'decision_at' => date('Y-m-d H:i:s'),
                'allow' => true,
                'deny' => false,
            ]);

            DB::commit();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'SOLICITAÇÃO APROVADA',
                'html'     => 'A solicitação foi aprovada com sucesso.',
                'timer'    => 5000,
            ]);

            $this->close();

        } catch (\Throwable $th) {
            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO AO APROVAR',
                'html'     => 'A solicitação não foi Aprovada'.$th->getMessage(),
                'timer'    => 5000,
            ]);
        }

    }


    public function confirm_reject()
    {


        DB::beginTransaction();

        try {

            $this->form->update([
                'engineer_id' => auth()->id(),
                'engineer_info' => $this->engineer_feedback,
                'decision_at' => date('Y-m-d H:i:s'),
                'allow' => false,
                'deny' => true,
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'SOLICITAÇÃO REJEITADA',
                'html'     => 'A solicitação foi rejeitada com sucesso.',
                'timer'    => 5000,
            ]);

            DB::commit();

            $this->close();

        } catch (\Throwable $th) {
            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO AO REJEITAR',
                'html'     => 'A solicitação não foi rejeitada. <br>'.$th->getMessage(),
                'timer'    => 5000,
            ]);
        }

    }

    public function close()
    {
        $this->engineer_feedback = '';
        $this->emitUp('refresh');
        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.engineers.actions.check-partial');
    }
}
