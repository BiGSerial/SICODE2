<?php

namespace App\Http\Livewire\Admin\Company;

use App\Models\{Andresscompany, Company};
use Livewire\Component;

class Update extends Component
{
    public $name;

    public $email;

    public $street;

    public $complement;

    public $uf;

    public $city;

    public $telephone;

    public $company_update;

    protected $listeners = [
        'save_update_company' => 'update',
    ];

    public function mount(Company $company_id)
    {
        $this->company_update = $company_id->load('address');

        $this->name      = $this->company_update->name;
        $this->email     = $this->company_update->email;
        $this->telephone = $this->company_update->telephone;
        $address         = $this->company_update->address()->first();

        if ($address) {
            $this->street     = $address->street;
            $this->complement = $address->complement;
            $this->uf         = $address->uf;
            $this->city       = $address->city;
        }

    }

    public function update()
    {
        if (!$this->email || !trim($this->name)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Os campos de usuário ou email devem estar preenchidos',
                'timer'    => 2500,
            ]);

            return;
        }

        $upd = $this->company_update->update([
            'email'     => $this->email,
            'name'      => ucwords(mb_strtolower($this->name)),
            'telephone' => $this->telephone,
        ]);

        if ($upd) {
            $address = $this->company_update->address->first();

            if ($address) {
                $address->update([
                    'street'     => ucwords(mb_strtolower($this->street)),
                    'city'       => ucwords(mb_strtolower($this->city)),
                    'uf'         => strtoupper($this->uf),
                    'complement' => $this->complement,
                ]);
            } else {

                $address = new Andresscompany([
                    'street'     => ucwords(mb_strtolower($this->street)),
                    'city'       => ucwords(mb_strtolower($this->city)),
                    'uf'         => strtoupper($this->uf),
                    'complement' => $this->complement,
                ]);

                $this->company_update->address()->save($address);
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Usuário atualizado com sucesso',
                'timer'    => 2500,
            ]);

            $this->emit('refresh_table_company');

            $this->clean_all();
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Oops! Ocorreu um erro ao atualizar o usuário.',
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
        return view('livewire.admin.company.update');
    }
}
