<?php

namespace App\Http\Livewire\Admin\Company;

use App\Models\{Andresscompany, Company};
use Livewire\Component;

class Create extends Component
{
    protected $listeners = [
        'save_create_company' => 'save',
    ];

    public $name;

    public $email;

    public $street;

    public $complement;

    public $uf;

    public $city;

    public $telephone;

    public function save()
    {
        if (!$this->email || !trim($this->name)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Os campos de nome ou email, devem estar preenchidos',
                'timer'    => 2500,
            ]);

            return;
        }

        $company = Company::Create([
            'email'     => $this->email,
            'name'      => ucwords(mb_strtolower($this->name)),
            'telephone' => $this->telephone,

        ]);

        $address = new Andresscompany([
            'street'     => ucwords(mb_strtolower($this->street)),
            'city'       => ucwords(mb_strtolower($this->city)),
            'uf'         => strtoupper($this->uf),
            'complement' => $this->complement,
        ]);

        if ($company) {

            if ($company->address()->save($address)) {

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Empresa Cadastrada com Sucesso',
                    'timer'    => 2500,
                ]);
            }

            $this->emit('refresh_table_company');

            $this->clean_all();

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ooops! Ocorreu um erro ao criar o usuário.',
                'timer'    => 2500,
            ]);
        }

    }

    public function clean_all()
    {
        $this->name       = '';
        $this->email      = '';
        $this->street     = '';
        $this->complement = '';
        $this->uf         = '';
        $this->city       = '';
        $this->telephone  = '';

        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.admin.company.create');
    }
}
