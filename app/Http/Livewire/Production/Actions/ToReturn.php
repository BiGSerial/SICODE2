<?php

namespace App\Http\Livewire\Production\Actions;

use App\Models\Production;
use Livewire\Component;

class ToReturn extends Component
{
    public ?Production $production = null;

    protected $listeners = [
        'toReturn',
        'c545f3b1d292f08f87838219905b5d48a501103a' => 'executeReturn',

    ];

    public function toReturn(?Production $production)
    {
        $this->production = $production;

        if ($this->production) {

            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Retornar Atividade',
                'msg'           => "
                    <p class='fw-bold'>Deseja retornar <strong>{$this->production->note->note}</strong> para <strong>{$this->production->user?->name}</strong>?</p>
                ",
                'icon'          => 'warning',
                'btnOktxt'      => "Sim, Retorne",
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'c545f3b1d292f08f87838219905b5d48a501103a',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma atividade retornada.',

            ]);
        }
    }

    public function executeReturn()
    {
        try {
            $this->production->update([
                'att_by' => auth()->id(),
                'att_at' => now(),
                'status' => 2,
                'completed_at' => null,
                'completed' => false,
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Sucesso',
                'text'     => 'Atividade Retornada com sucesso.',
                'timer'    => 2500,
            ]);

            $this->emitUp('refresh_list');

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro',
                'text'     => 'Erro ao retornar a atividade. Tente novamente.',
                'timer'    => 2500,
            ]);

            return;
        }
    }

    public function render()
    {
        return view('livewire.production.actions.to-return');
    }
}
