<?php

namespace App\Http\Livewire\Dispatchs\Users;

use App\Models\Company;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class RiattUser extends Component
{
    public ?Reclaim $reclaim = null;
    public ?Service $service = null;
    public $selectAll = false;
    public $selected = [];
    public $user;
    public $user_s;
    public $search;
    public $company;
    public $companies;


    protected $listeners = [
        'goAttUser',
        'confirm_attuser'
    ];

    public function mount($service)
    {
        $this->service = $service;
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
                $q->where('name', 'like', "%" . trim($this->search) . "%");
            })
            ->orderBy('name')->get();
    }


    public function goAttUser(Reclaim $reclaim)
    {
        $this->reclaim = $reclaim;

        if ($this->reclaim) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => "ri_att_user",
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

    public function toAttUser()
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
            'title'         => 'Confirmar Atribuição?',
            'msg'           => "Você está prestes a atribuir a NOTA/OV {$this->reclaim->Note->note} para {$this->user->name}, Deseja continuar?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Atribua!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_attuser',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nenhuma nota foi Transferida.',

        ]);

        return;
    }

    public function confirm_attuser()
    {
        if (($user = User::find($this->user_s)) && !$this->reclaim->Production) {


            DB::beginTransaction();

            // Cria uma nova produção;
            $production = Production::create([
                'note_id' => $this->reclaim->Note->id,
                'service_id' => $this->service->uuid,
                'company_id' => $user->Employee->Contract->company->id,
                'user_id' => $user->id,
                'dispatch_by' => Auth()->User()->id,
                'dispatch_at' => date('Y-m-d H:i:s'),
                'att_by' => Auth()->User()->id,
                'att_at' => date('Y-m-d H:i:s'),
                'status' => 2,
                'd5' => true,
            ]);

            // Associa a Produção ao Retorno Interno para manter o controle.
            $check = $this->reclaim->update(['production_id' => $production->id]);

            if ($check) {

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'NOTA ATRIBUIDA AO USUÁRIO COM SUCESSO',
                    'timer'    => 5000,
                ]);

                DB::commit();

                // DB::rollback();

                $this->cleanThis();

                $this->emitUp('cleanAll');

                return;
            } else {

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'FALHA AO ATRIBUITR USUARIO',
                    'html'     => 'Ocorreu alguma falha no processo que não foi possível alterar o usuário. Cheque os dados e tente novamente.',
                    'timer'    => 5000,
                ]);

                DB::rollback();



                return;
            }
        } else {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'FALHA AO REGISTRAR USUARIO',
                'html'     => 'Por algum motivo os dados foram perdidos ou não antedem a essa solicitação especíca. Revise as informações e tente novamente.',
                'timer'    => 5000,
            ]);

            return;
        }
    }

    public function cancelRIAction()
    {
        $this->cleanThis();
        $this->dispatchBrowserEvent('hideModal');
        $this->emitUp('cleaAll');

        return;
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
        return view('livewire.dispatchs.users.riatt-user', [
            'users' => $this->users
        ]);
    }
}
