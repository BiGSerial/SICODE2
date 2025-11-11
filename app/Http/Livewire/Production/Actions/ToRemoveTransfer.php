<?php

namespace App\Http\Livewire\Production\Actions;

use App\Models\Production;
use Livewire\Component;

class ToRemoveTransfer extends Component
{
    public ?Production $production = null;

    protected $listeners = [
        'toRemoveTransfer',
        '27987d546a2e2730545ad8c02ffb96314cbccf08' => 'executeRemoveTransfer',

    ];

    public function toRemoveTransfer(?Production $production)
    {
        $this->production = $production;

        if ($this->production) {

            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Cancelar Transferência',
                'msg'           => "
                    <p class='fw-bold'>Deseja cancelar transferencia de <strong>{$this->production->note->note}</strong>?</p>

                    ",
                'icon'          => 'question',
                'btnOktxt'      => "Sim, Cancelar",
                'btnCanceltxt'  => 'Não, Abortar',
                'action'        => '27987d546a2e2730545ad8c02ffb96314cbccf08',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma transferencia removida.',

            ]);
        }
    }

    public function executeRemoveTransfer()
    {
        try {
            $this->production->Transfer()->where('status', 19)->delete();
            $this->production->update([
                'status' => 2,
                'completed' => false,
                'completed_at' => null,
                'block' => false,
                'block_wpa' => false,
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Sucesso',
                'text'     => 'Transferencia Cancelada com Sucesso.',
                'timer'    => 2500,
            ]);

            $this->emitUp('refresh_list');

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro',
                'text'     => 'Erro ao cancelar a trasferencia.',
                'timer'    => 2500,
            ]);

            return;
        }
    }

    public function render()
    {
        return view('livewire.production.actions.to-remove-transfer');
    }
}
