<?php

namespace App\Http\Livewire\Admin\User;

use App\Models\User;
use Livewire\Component;

class Delete extends Component
{
    public $delete;

    protected $listeners = [
        'delete_user'           => 'to_delete',
        'confirm_user_delete'   => 'delete',
        'undelete_user'         => 'to_undelete',
        'confirm_user_undelete' => 'undelete',
    ];

    // public function mount(User $user_id)
    // {
    //     $this->delete = $user_id;
    // }

    public function to_delete(User $user_id)
    {
        $this->delete = $user_id;

        if ($this->delete) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Deletar Usuário',
                'msg'           => "Você deseja remover <strong>{$this->delete->name}</strong>?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, delete!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_user_delete',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

            ]);
        }
    }

    public function to_undelete($user_id)
    {
        $this->delete = User::withTrashed()->find($user_id);

        if ($this->delete) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Restaurar Usuário',
                'msg'           => "Você deseja Restaurar <strong>{$this->delete->name}</strong>?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, restaure!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_user_undelete',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

            ]);
        }
    }

    public function delete()
    {
        if ($this->delete->delete()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Usuário removido com sucesso',
                'timer'    => 2500,
            ]);

            $this->emit('refresh_table_user');
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ooops! Ocorreu um erro ao remover o usuário.',
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
                'title'    => 'Usuário restaurado com sucesso',
                'timer'    => 2500,
            ]);

            $this->emit('refresh_table_user');
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ooops! Ocorreu um erro ao restaurar o usuário.',
                'timer'    => 2500,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.admin.user.delete');
    }
}
