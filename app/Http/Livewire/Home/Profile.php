<?php

namespace App\Http\Livewire\Home;

use App\Models\User;
use Livewire\Component;

class Profile extends Component
{
    public User $user;

    protected $rules = [
        'user.name'       => 'string|max:255',
        'user.email'      => 'string|email|max:255|unique:users,email',
        'user.Registration'  => 'nullable|string|max:20',
        'user.company.name' => 'nullable|string|max:255',
        'user.company.email' => 'nullable|string|email|max:255',
        'user.company.telephone' => 'nullable|string|max:20',
        'user.company.address.0.street' => 'nullable|string|max:255',
        'user.company.address.0.city' => 'nullable|string|max:255',
        'user.company.address.0.uf' => 'nullable|string|max:255',

    ];


    public function mount(User $user)
    {
        $this->user = $user;

        if (!$this->user) {
            abort(403);
        }
    }

    public function render()
    {
        return view('livewire.home.profile');
    }
}
