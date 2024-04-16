<?php

namespace App\Http\Livewire\Partner\Actions;

use Livewire\Component;

class Approveaction extends Component
{
    public $list;

    protected $listeners = [
        'teste' => 'testando'
    ];

    public function agree()
    {
        $this->dispatchBrowserEvent('swal2', [
            'title'         => 'Confirmar Atribuir',
            'msg'           => "TESTES",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Despache!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'teste',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

        ]);
    }

    public function testando()
    {
        dd($this->list);
    }

    public function render()
    {
        return view('livewire.partner.actions.approveaction');
    }
}
