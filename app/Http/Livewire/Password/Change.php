<?php

namespace App\Http\Livewire\Password;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Change extends Component
{
    public $password;

    public $re_password;

    public function change_password()
    {

        if (!$this->password || !$this->re_password) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Ambos campos precisam ser preenchidos.',
                'timer'    => 2500,
            ]);

            return;
        }

        if ($this->password === $this->re_password) {

            if (strpos($this->password, ' ') !== false) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'A Senha não pode conter espaços.',
                    'timer'    => 2500,
                ]);

                return;
            }

            if (strlen(trim($this->password)) < 6) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'A Senha tem de conter mais que 6 caracteres.',
                    'timer'    => 2500,
                ]);

                return;
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'OK TUDO CERTO.',
                'timer'    => 2500,
            ]);

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Senhas não conferem.',
                'timer'    => 2500,
            ]);

            return;
        }

        $user = User::find(Auth()->User()->id);

        if ($user->update([
            'password'   => Hash::make($this->password),
            'first_pass' => false,
        ])) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Senha Alterada com Sucesso..',
                'timer'    => 2500,
            ]);

            return redirect(route('home'));

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Algo desconhecido aconteceu ao tentar alterar a senha',
                'timer'    => 2500,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.password.change');
    }
}
