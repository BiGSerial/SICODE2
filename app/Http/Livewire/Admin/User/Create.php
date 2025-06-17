<?php

namespace App\Http\Livewire\Admin\User;

use App\Models\{Company, Contract, Employee, Service, User};
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Create extends Component
{
    protected $listeners = [
        'save_create_user' => 'save',
    ];

    public $name;

    public $email;

    public $superadm;

    public $admin;

    public $management;

    public $operator;

    public $user;

    public $contract;

    public $company_s;

    public $contract_s;

    public $service_s;

    public $contracts;

    public $companies;

    public $services;

    public $registration;

    public $engineer;

    public $onlyparner;

    public function save()
    {
        if (!$this->email || !trim($this->name)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Os campos de usuário ou email, devem estar preenchidos',
                'timer'    => 2500,
            ]);

            return;
        }

        // if (($this->superadm +
        //     $this->admin +
        //     $this->management +
        //     $this->engineer +
        //     $this->operator) == 0) {

        //     $this->user = true;
        // }

        if (Auth()->User()->Contract) {
            $this->contract = true;
        }

        $user = User::Create([
            'email'        => $this->email,
            'Registration' => $this->registration,
            'name'         => ucwords(mb_strtolower($this->name)),
            'password'     => Hash::make(123456),
            'superadm'     => $this->superadm ? true : false,
            'admin'        => $this->admin ? true : false,
            'management'   => $this->management ? true : false,
            'engineer'     => $this->engineer ? true : false,
            'operator'     => $this->operator ? true : false,
            'user'         => $this->user ? true : false,
            'contract'     => $this->contract ? true : false,
            'onlyparner'   => $this->onlyparner ? true : false,
        ]);

        if ($user) {

            if ($this->contract_s && $this->service_s) {
                Employee::create([
                    'contract_id' => $this->contract_s,
                    'user_id'     => $user->id,
                    'service_id'  => $this->service_s,
                ]);
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Usuário Criado com sucesso',
                'timer'    => 2500,
            ]);

            $this->emit('refresh_table_user');

            $this->dispatchBrowserEvent('copySicodeAccess', $this->email);

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
        $this->name         = '';
        $this->email        = '';
        $this->superadm     = false;
        $this->admin        = false;
        $this->management   = false;
        $this->engineer     = false;
        $this->operator     = false;
        $this->user         = false;
        $this->contract     = false;
        $this->company_s    = '';
        $this->contract_s   = '';
        $this->service_s    = '';
        $this->contracts    = '';
        $this->companies    = '';
        $this->services     = '';
        $this->registration = '';
        $this->onlyparner = false;

        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        $this->companies = Company::when(!Auth()->User()->superadm, function ($q) {
            return $q->where('id', Auth()->User()->Employee->Contract->company_id);
        })->orderBy('name')->get();
        $this->contracts = Contract::where('company_id', $this->company_s)->orderBy('number')->get();
        $this->services  = Service::orderBy('service')->get();

        return view('livewire.admin.user.create');
    }
}
