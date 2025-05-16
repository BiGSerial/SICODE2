<?php

namespace App\Http\Livewire\Dispatchs\Users;

use App\Models\Company;
use App\Models\Reclaim;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class RichangeUser extends Component
{
    public ?Reclaim $reclaim = null;
    public $selectAll = false;
    public $selected = [];
    public $user;
    public $user_s;
    public $search;
    public $company;
    public $companies;


    protected $listeners = [
        'goChangeUser',
        'confirm_transferUser'
    ];

    public function mount()
    {
        $this->companies = Company::OrderBy('name')->get();
    }


    public function UpdatedUserS($user)
    {
        $this->user = $this->users->where('id', $user)->first();
    }

    public function getUsersProperty()
    {
        return User::WhereRelation('Employee.Contract.company', 'id', $this->company)
                    ->when(trim($this->search), function ($q) {
                        $q->where('name', 'like', "%".trim($this->search)."%");
                    })
                    ->orderBy('name')->get();
    }


    public function goChangeUser(Reclaim $reclaim)
    {
        $this->reclaim = $reclaim;

        if ($this->reclaim) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => "ri_change_user",
            ]);
        }
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->gotoPage(1);


        $this->selectAll = false;
        $this->selected = [];


        $this->emit('refresh_list');
    }

    public function toChangeUser()
    {
        if (!$this->company || !$this->user_s) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'A Seleção do Usuário de destino é Obrigatória',
                'timer'    => 5000,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Confirmar Transferência',
            'msg'           => "Você está prestes a transferir {$this->reclaim->Note->note} para {$this->user->name}, Deseja continuar?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Transfira!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_transferUser',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nenhuma nota foi Transferida.',

        ]);

        return;
    }

    public function confirm_transferUser()
    {
        if (($user = User::find($this->user_s)) && $this->reclaim->Production) {

            DB::beginTransaction();

            $check = $this->reclaim->Production->update([
                'company_id' => $user->Employee->Contract->company->id,
                'user_id' => $user->id,
                'att_by' => Auth()->User()->id,
                'att_at' => date('Y-m-d H:i:s'),
                'status' => 2,
            ]);

            if ($check) {

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'USUARIO DO RI ALERADO COM SUCESSO',
                    'timer'    => 5000,
                ]);

                DB::commit();

                $this->cancelRIAction();

                $this->emitUp('cleanAll');

                return;

            } else {

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'werror',
                    'title'    => 'FALHA AO ALTERAR USUARIO',
                    'html'     => 'Ocorreu alguma falha no processo que não foi possível alterar o usuário. Cheque os dados e tente novamente.',
                    'timer'    => 5000,
                ]);

                DB::rollback();



                return;

            }

        }
    }

    public function cancelRIAction()
    {
        $this->cleanThis();
        $this->dispatchBrowserEvent('hideModal');
        $this->emitUp('cleaAll');
        $this->emit('refreshComponent');


    }

    public function cleanThis()
    {
        $this->reclaim = null;
        $this->selectAll = false;
        $this->selected = [];
        $this->user_s = "";
        $this->search = "";
        $this->company = "";


    }


    public function render()
    {
        return view('livewire.dispatchs.users.richange-user', [
            'users' => $this->users
        ]);
    }
}
