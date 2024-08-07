<?php

namespace App\Http\Livewire\Admin\User\Actions;

use App\Models\User;
use Livewire\Component;

class Usuario extends Component
{
    public ?User $user = null;

    protected $listeners = [
        'openUser' => 'openUser'
    ];

    public function openUser($user)
    {


        $this->user = User::findOrFail($user['id']);


        if ($this->user) {
            dd($this->user);

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'userModal',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.admin.user.actions.usuario');
    }
}
