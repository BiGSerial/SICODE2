<?php

namespace App\Http\Livewire\Admin\User;

use App\Models\{Company, Contract, Employee, Service, User};
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Update extends Component
{
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

    public $user_update;

    public $reset_user;

    public $bypassprod;

    public $onlyparner;

    protected $listeners = [
        'save_update_user'  => 'update',
        'toResetPass'       => 'to_reset_password',
        'confirm_user_pass' => 'reset_pass',
    ];

    public function mount(User $user_id)
    {
        $this->user_update = $user_id->load('Employee');

        $this->name         = $this->user_update->name;
        $this->email        = $this->user_update->email;
        $this->superadm     = $this->user_update->superadm;
        $this->admin        = $this->user_update->admin;
        $this->management   = $this->user_update->management;
        $this->engineer     = $this->user_update->engineer;
        $this->operator     = $this->user_update->operator;
        $this->user         = $this->user_update->user;
        $this->contract     = $this->user_update->contract;
        $this->registration = $this->user_update->Registration;
        $this->bypassprod   = $this->user_update->bypassprod;
        $this->onlyparner   = $this->user_update->onlyparner;

        if (isset($this->user_update->Employee)) {
            $this->company_s  = $this->user_update->Employee->Contract->company_id;
            $this->contract_s = $this->user_update->Employee->Contract->id;
            $this->service_s  = $this->user_update->Employee->service_id;
        }

    }

    public function update()
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

        $this->user_update->name         = ucwords(strtolower($this->name));
        $this->user_update->email        = $this->email;
        $this->user_update->Registration = $this->registration;
        $this->user_update->superadm     = $this->superadm ? true : false;
        $this->user_update->admin        = $this->admin ? true : false;
        $this->user_update->management   = $this->management ? true : false;
        $this->user_update->engineer     = $this->engineer ? true : false;
        $this->user_update->operator     = $this->operator ? true : false;
        $this->user_update->user         = $this->user ? true : false;
        $this->user_update->contract     = $this->contract ? true : false;
        $this->user_update->bypassprod   = $this->bypassprod ? true : false;
        $this->user_update->onlyparner   = $this->onlyparner ? true : false;

        if (Auth()->User()->Contract) {
            $this->user_update->contract = true;
        }

        if ($this->user_update->save()) {

            if ($this->contract_s && $this->service_s) {
                $employeer = Employee::where('user_id', $this->user_update->id)->first();

                if ($employeer) {
                    $employeer->update([
                        'contract_id' => $this->contract_s,
                        'service_id'  => $this->service_s,
                    ]);
                } else {
                    Employee::create([
                        'user_id'     => $this->user_update->id,
                        'contract_id' => $this->contract_s,
                        'service_id'  => $this->service_s,
                    ]);
                }
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Usuário atualizado com sucesso',
                'timer'    => 2500,
            ]);

            $this->emit('refresh_table_user');

            $this->clean_all();

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ooops! Ocorreu um erro ao atualizar o usuário.',
                'timer'    => 2500,
            ]);
        }

    }

    public function to_reset_password(User $user)
    {
        $this->reset_user = $user;

        if ($this->reset_user) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'RESET DE SENHA',
                'msg'           => "Você deseja alterar a senha padrão para <strong>{$this->reset_user->name}</strong>?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, altere!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_user_pass',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma senha de usuário foi alterada.',

            ]);
        }
    }

    public function reset_pass()
    {

        dd('Resetando senha do usuário: ' . $this->reset_user->name);
        try {
            $this->reset_user->password   = Hash::make(123456);
            $this->reset_user->first_pass = true;
            $this->reset_user->save();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Senha aterado para o padrão "123456"',
                'timer'    => 2500,
            ]);

            $this->dispatchBrowserEvent('copySicodeAccess', $this->reset_user->email);

        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ooops! Ocorreu um erro ao atualizar o usuário.',
                'timer'    => 2500,
            ]);

            if (env('APP_DEBUG')) {
                $this->dispatchBrowserEvent('torrada', [
                    'status'   => 'warning',
                    'menssage' => $th->getMessage(),
                ]);
            }
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
        $this->bypassprod   = false;
        $this->contract     = false;
        $this->company_s    = '';
        $this->contract_s   = '';
        $this->service_s    = '';
        $this->contracts    = '';
        $this->companies    = '';
        $this->services     = '';
        $this->registration = '';
        $this->onlyparner   = false;

        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        $this->companies = Company::when(!Auth()->User()->superadm, function ($q) {
            return $q->where('id', Auth()->User()->Employee->Contract->company_id);
        })->orderBy('name')->get();
        $this->contracts = Contract::where('company_id', $this->company_s)->orderBy('number')->get();
        $this->services  = Service::orderBy('service')->get();

        return view('livewire.admin.user.update');
    }
}
