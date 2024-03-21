<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ChangePass extends Component
{
    public $password = 1;

    public $re_password;

    public function change_password()
    {
        dd('teste');

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
    }

    public function render()
    {
        return view('livewire.change-pass');
    }
}
