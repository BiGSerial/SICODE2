<?php

namespace App\Http\Livewire\Admin\User\Actions;

use Livewire\Component;

class UserAccessInfo extends Component
{
    public $email;
    public $password;
    public $url;

    protected $listeners = [
        'copySuccess'
    ];

    public function mount($email, $password, $url)
    {
        $this->email = $email;
        $this->password = $password;
        $this->url = $url;
    }

    public function copyToClipboard()
    {
        // Monta a string que será copiada
        $data = "ACESSO AO SICODE
==========================
Email: {$this->email}
Senha: {$this->password}
Endereço: {$this->url}";

        // Dispara o evento para o navegador
        $this->dispatchBrowserEvent('copyToClipboardTxt', ['text' => $data]);
    }

    public function copySuccess()
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'COPIADO COM SUCESSO!',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.user.actions.user-access-info');
    }
}
