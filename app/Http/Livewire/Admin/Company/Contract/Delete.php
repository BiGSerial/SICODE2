<?php

namespace App\Http\Livewire\Admin\Company\Contract;

use App\Models\Contract;
use Livewire\Component;

class Delete extends Component
{
    public $delete;

    protected $listeners = [
        'delete_contract'         => 'to_delete',
        'confirm_contract_delete' => 'delete',
    ];

    public function to_delete(Contract $contract_id)
    {
        $this->delete = $contract_id->load('company');

        if ($this->delete) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Deletar Empresa',
                'msg'           => "Você deseja remover o contrato <stron>{$this->delete->number}</stron> de <strong>{$this->delete->company->name}</strong>?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, remova!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_contract_delete',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma contrato foi removido.',

            ]);
        }
    }

    public function delete()
    {
        if ($this->delete->delete()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Contrato removido com sucesso',
                'timer'    => 2500,
            ]);

            $this->emit('refresh_table_contract');
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ooops! Ocorreu um erro ao tentar remover o Contrato.',
                'timer'    => 2500,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.admin.company.contract.delete');
    }
}
