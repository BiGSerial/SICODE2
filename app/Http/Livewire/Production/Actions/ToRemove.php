<?php

namespace App\Http\Livewire\Production\Actions;

use App\Models\Production;
use Livewire\Component;

class ToRemove extends Component
{
    public ?Production $production = null;

    protected $listeners = [
        'toRemove',
        '39d6f40d1eee60d96c9385749ccacec94e0cf01a' => 'executeToRemove',

    ];

    public function toRemove(?Production $production)
    {
        $this->production = $production;

        if ($this->production) {

            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Remover Atividade',
                'msg'           => "
                    <p class='fw-bold'>Deseja remover atividade <strong>{$this->production->note->note}</strong> de <strong>{$this->production->user?->name}</strong>?</p>
                    <div class='card shadow edp-bg-sprucegreen-70 text-white'>
                    <div class='card-body'>
                    <p>Ao remover a atividade, você estará removendo a produção caso seja, e será removido do log do BI</p>
                    </div>

                </div>
                <p class='fw-bold text-center'>Deseja realmente Continuar?</p>
                    ",
                'icon'          => 'warning',
                'btnOktxt'      => "Sim, Remova",
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => '39d6f40d1eee60d96c9385749ccacec94e0cf01a',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma atividade retornada.',

            ]);
        }
    }

    public function executeToRemove()
    {
        try {
            $this->production->delete();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Sucesso',
                'text'     => 'Atividade removida com sucesso.',
                'timer'    => 2500,
            ]);

            $this->emitUp('refresh_list');

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro',
                'text'     => 'Erro ao remover a atividade.',
                'timer'    => 2500,
            ]);

            return;
        }
    }

    public function render()
    {
        return view('livewire.production.actions.to-remove');
    }
}
