<?php

namespace App\Http\Livewire\Production\Actions;

use App\Models\Priority;
use App\Models\Production;
use App\Notifications\SystemNotification;
use Livewire\Component;

class SetPriority extends Component
{
    public ?Production $production = null;

    public $priority_reason;

    protected $listeners = [
        'setPriority',
        'fe9a89fd70c5d6fbf08b4de391d145da1b881453' => 'executeSetPriority',

    ];

    public function setPriority(?Production $production)
    {

        $this->production = $production;

        if ($this->production) {
            $action = $this->production->priority ? 'REMOVER' : 'DEFINIR';


            if (!$this->production->priority) {



                $this->dispatchBrowserEvent('showModal', [
                    'id' => 'set_priority',
                ]);

                return;
            }


            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Definir Prioridade',
                'msg'           => "
                    <p class='fw-bold'>Deseja realmente {$action} a prioridade para <strong>{$this->production->note->note}</strong>?</p>
                ",
                'icon'          => 'warning',
                'btnOktxt'      => "Sim, {$action}",
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'fe9a89fd70c5d6fbf08b4de391d145da1b881453',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma prioridade definida.',

            ]);
        }
    }

    public function executeSetPriority()
    {

        if (!$this->production->priority) {
            $this->validate([
                'priority_reason' => 'required',
            ]);
        }

        try {
            $this->production->update([
                'priority' => !$this->production->priority,
            ]);

            if ($this->production->priority) {
                // Cria e associa a prioridade via relacionamento "many-to-many"
                $this->production->priorities()->create([
                    'note_id'    => $this->production->note_id,
                    'user_id'    => auth()->id(),
                    'service_id' => $this->production->service_id,
                    'prioridade' => $this->priority_reason,
                    'global'     => false,
                ]);
            }

            $this->production->User->notify(new SystemNotification(
                $this->production->priority ? 'PRIORIDADE DEFINIDA' : 'PRIORIDADE REMOVIDA',
                'O usuário ' . auth()->user()->name .
                ($this->production->priority ? ' definiu prioridade para a nota/ov ' : ' removeu prioridade da nota/ov <strong>') . $this->production->Note->note .
                '</strong> em <strong>' . $this->production->Service->service . '</strong>.<br> <strong> Motivo: </strong> ' . $this->priority_reason,
                route('services.accompany', ['service' => $this->production->service_id]),
                2 // status
            ));

            $this->emitUp('refresh_list');

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Sucesso',
                'text'     => 'Prioridade definida com sucesso.',
                'timer'    => 2500,
            ]);

            $this->closeAll();

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro',
                'text'     => 'Erro ao definir a prioridade.',
                'timer'    => 2500,
            ]);

            return;
        }
    }

    public function closeAll()
    {

        $this->production = null;
        $this->priority_reason = null;
        $this->dispatchBrowserEvent('hideModal');

    }

    public function render()
    {
        return view('livewire.production.actions.set-priority');
    }
}
