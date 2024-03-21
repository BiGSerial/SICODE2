<?php

namespace App\Http\Livewire\Admin\Company;

use App\Models\Company;
use Livewire\Component;

class Delete extends Component
{
    public $delete;

    protected $listeners = [
        'delete_company'           => 'to_delete',
        'confirm_company_delete'   => 'delete',
        'undelete_company'         => 'to_undelete',
        'confirm_company_undelete' => 'undelete',
    ];

    // public function mount(User $company_id)
    // {
    //     $this->delete = $company_id;
    // }

    public function to_delete(Company $company_id)
    {
        $this->delete = $company_id->load('Address');

        if ($this->delete) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Deletar Empresa',
                'msg'           => "Você deseja remover <strong>{$this->delete->name}</strong>?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, delete!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_company_delete',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma Empresa foi removido.',

            ]);
        }
    }

    public function to_undelete($company_id)
    {
        $this->delete = Company::withTrashed()->find($company_id);

        if ($this->delete) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Restaurar Empresa',
                'msg'           => "Você deseja Restaurar <strong>{$this->delete->name}</strong>?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, restaure!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_company_undelete',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma Empresa foi removida.',

            ]);
        }
    }

    public function delete()
    {
        // Remove all address related with Company
        if ($this->delete->address) {
            foreach ($this->delete->address as $address) {
                $address->delete();
            }
        }

        // Remove thw company
        if ($this->delete->delete()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Empresa removido com sucesso',
                'timer'    => 2500,
            ]);

            $this->emit('refresh_table_company');
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ooops! Ocorreu um erro ao remover o Empresa.',
                'timer'    => 2500,
            ]);
        }
    }

    public function undelete()
    {
        if ($this->delete->restore()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Empresa restaurado com sucesso',
                'timer'    => 2500,
            ]);

            $this->emit('refresh_table_company');
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ooops! Ocorreu um erro ao restaurar o Empresa.',
                'timer'    => 2500,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.admin.company.delete');
    }
}
