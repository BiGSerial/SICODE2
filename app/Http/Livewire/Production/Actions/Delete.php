<?php

namespace App\Http\Livewire\Production\Actions;

use App\Models\{Production};
use Livewire\Component;

class Delete extends Component
{
    public ?Production $production = null;

    public $chave;

    public $listeners = [
        'confirm_delete' => 'confirm_delete',
    ];

    public function mount($production, $chave)
    {

        $this->production = $production;
        $this->chave      = $chave;
    }

    public function to_delete()
    {

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'REMOVER DESPACHO',
            'msg'   => "Você está prestes a remover {$this->production->load('Note')->Note->note} da produção. Esteja ciente ao fazer isso de forma inadequada poderá prejudicar a medição do usuário ou empresa. 
                Lembrando que a exclusão também removerá do LOG do BI. \n Deseja Continuar?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Remova!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_delete',
            'chave'         => $this->chave,
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

        ]);
    }

    public function confirm_delete($chave)
    {
        if ($chave === $this->chave) {

            if (app(\App\Services\Production\ProductionActions::class)->remove($this->production)) {

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Produção removida com sucesso',
                    'timer'    => 2500,
                ]);

                $this->emit('refresh_list');

            } else {

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'Ocorreu algum problema ao tentar remover a produção',
                    'timer'    => 6000,
                ]);

                $this->emit('refresh_list');
            }
        }
    }

    public function render()
    {
        return view('livewire.production.actions.delete');
    }
}
