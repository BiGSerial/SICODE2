<?php

namespace App\Http\Livewire\Production\Actions;

use App\Models\Company;
use App\Models\Production;
use App\Models\User;
use Livewire\Component;

class NewProduction extends Component
{
    public ?Production $production = null;
    public $companies;
    public $users;
    public $companySelected;
    public $userSelected;
    public $ri = false;



    protected $listeners = [
        'editProduction',
        '5e07c135fc01c20191c6ed1e75db04895e29ef7b' => 'executeTransferProduction',
        '91acb014fc601a16b9fb7c9540067b99bab74fd8' => 'executeCreateNewProduction',
    ];

    protected $messages = [
        'companySelected.required' => 'A empresa é obrigatória.',
        'userSelected.required'  => 'O usuário é obrigatório.',
    ];

    public function editProduction(?Production $production)
    {
        $this->production = $production;


        if ($this->production) {

            $this->companies = Company::whereRelation('contracts.services', function ($q) {
                $q->where('uuid', $this->production->service_id);
            })->orderBy('name')->get();

            // $this->users = User::whereRelation('ToServices', function ($q) {
            //     $q->where('service_id', $this->production->service_id);
            // })->orderBy('name')->get();

            $this->ri = $this->production->d5;

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'edit_production',
            ]);
        }
    }

    public function updatedCompanySelected($value)
    {
        $this->users = null;
        $this->userSelected = null;

        $this->users = User::whereRelation('ToServices', function ($q) {
            $q->where('service_id', $this->production->service_id);
        })->where('company_id', $value)->orderBy('name')->get();
    }


    public function toTransferProduction()
    {
        $this->validate([
            'companySelected' => 'required',
            'userSelected' => 'required',
        ]);


        $users = clone $this->users;
        $user = $users->where('id', $this->userSelected)->first();
        $oldUser = $this->production->user;

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Transferir Produção',
            'msg'           => "
                <p>Transferir a Produção de <strong>{$this->production->note->note}</strong> de:</p>
                <div class='card shadow edp-bg-sprucegreen-70 text-white'>
                    <div class='card-body'>
                    <p><strong class='text-edp-verde'>{$oldUser?->name}</strong> para <strong class='text-edp-verde'>{$user?->name}</strong></p>
                <p><strong class='text-edp-verde'>{$oldUser?->company?->name}</strong> para <strong class='text-edp-verde'>{$user?->company?->name}</strong></p>
                    </div>
                    <div class='card-footer  edp-bg-sprucegreen-100'>
                        <p class=''>A Transferência de produção, fará o usuário original perder a produção que tiver realizado.</p>
                    </div>
                </div>



                <p class='fw-bold'>Tem certeza que deseja transferir?</p>
            ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Transferir!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => '5e07c135fc01c20191c6ed1e75db04895e29ef7b',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma produção foi transferida.',

        ]);


    }


    public function toCreateNewProduction()
    {
        $this->validate([
            'companySelected' => 'required',
            'userSelected' => 'required',
        ]);

        $users = clone $this->users;
        $user = $users->where('id', $this->userSelected)->first();


        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Transferir Produção',
            'msg'           => "
                <p>Criar uma nova Produção <strong>{$this->production->note->note}</strong>:</p>
                <div class='card shadow edp-bg-sprucegreen-70 text-white'>
                    <div class='card-body'>
                    <p>Voce está criando uma nova produção para <strong class='text-edp-verde'>{$user?->name}</strong> da Empresa {$user?->company?->name}</p>
                    </div>
                </div>



                <p class='fw-bold'>Tem certeza que deseja criar uma nova produção?</p>
            ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Criar!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => '91acb014fc601a16b9fb7c9540067b99bab74fd8',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma produção foi criada.',

        ]);


    }

    public function executeTransferProduction()
    {
        try {

            $this->production->update([
                'user_id' => $this->userSelected,
                'company_id' => $this->companySelected,
                'att_by' => auth()->id(),
                'att_at' => now(),
                'completed_at' => null,
                'completed' => false,
                'status' => 2,
                'd5' => $this->ri,
            ]);

        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao transferir produção',
                'text'     => 'Erro ao transferir produção, tente novamente.',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Produção Transferida',
            'text'     => 'Produção transferida com sucesso.',
            'timer'    => 2500,
        ]);

        $this->closeall();

    }


    public function executeCreateNewProduction()
    {


        try {

            Production::create([
                'note_id' => $this->production->note_id,
                'service_id' => $this->production->service_id,
                'user_id' => $this->userSelected,
                'company_id' => $this->companySelected,
                'dispatch_by' => auth()->id(),
                'dispatch_at' => now(),
                'att_by' => auth()->id(),
                'att_at' => now(),
                'status_note' => $this->production->status_note,
                'dt_note' => $this->production->dt_note,
                'dh_stats' => $this->production->dh_stats,
                'centroTrab' => $this->production->centroTrab,
                'status' => 2,
                'd5' => $this->ri,
            ]);

        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro em Criar produção',
                'text'     => 'Erro ao criar produção, tente novamente.',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Produção Criada',
            'text'     => 'Produção criada com sucesso.',
            'timer'    => 2500,
        ]);

        $this->closeall();

    }


    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->production = null;
        $this->companySelected = null;
        $this->userSelected = null;
        $this->companies = null;
        $this->users = null;


        $this->emitUp('refresh_list');
    }


    public function render()
    {
        return view('livewire.production.actions.new-production');
    }
}
