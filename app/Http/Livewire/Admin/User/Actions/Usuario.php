<?php

namespace App\Http\Livewire\Admin\User\Actions;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Service;
use App\Models\ServiceUser;
use App\Models\User;
use Livewire\Component;

class Usuario extends Component
{
    public ?User $user = null;
    public $companyList;
    public $company;
    public $contractList;
    public $contract;
    public $serviceList;
    public $serviceSelect;

    public $temporaryServices = [];



    protected $listeners = [
        'openUser' => 'openUser',
        'refreshuser' => '$refresh',
        'newUser' => 'newUser',
    ];

    protected $rules = [
        'user.email' => 'required|email',
        'user.name' => 'required|string|max:255',
        'user.Registration' => 'string|max:80',
        'company' => 'required|exists:companies,id',
        'contract' => 'required|exists:contracts,id',
        'user.superadm' => 'boolean',
        'user.admin' => 'boolean',
        'user.management' => 'boolean',
        'user.engineer' => 'boolean',
        'user.operator' => 'boolean',
        'user.user' => 'boolean',
        'user.onlyparner' => 'boolean',
        'regiaoControle' => 'string|in:norte,centroNorte,centroSul,sul',
    ];

    public function mount()
    {
        $this->companyList = Company::orderBy('name')->get();

    }

    public function openUser($user)
    {


        $this->user = User::findOrFail($user['id']);


        if ($this->user) {
            // dd($this->user);

            $this->company = isset($this->user->Employee->Contract->company->id) ? $this->user->Employee->Contract->company->id : '';
            $this->contract = isset($this->user->Employee->Contract->id) ? $this->user->Employee->Contract->id : '';

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'userModal',
            ]);
        }

        $this->emitSelf('refreshuser');

    }

    public function newUser()
    {


        $this->user = new User();
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'userModal',
        ]);

    }

    public function addService()
    {

        if ($this->user->ToServices->count()) {
            ServiceUser::updateOrCreate(
                ['user_id' => $this->user->id,
                'service_id' => $this->serviceSelect],
                [
                    'service' => 0,
                    'dispatch' => 0,
                ]
            );
        } else {
            if (collect($this->temporaryServices)->contains('service_id', $this->serviceSelect)) {

                return;
            }


            $this->temporaryServices[] = [
                'service_id' => $this->serviceSelect,
                'service' => 0,
                'dispatch' => 0,
            ];

        }

        $this->emitSelf('refreshuser');
    }

    public function ServiceOption($id, $column)
    {
        $service = ServiceUser::findOrFail($id);

        if ($service) {


            $service->$column = !$service->$column;
            $service->save();
        }

        $this->emitSelf('refreshuser');
    }

    public function removeService($index)
    {
        if ($this->user->ToServices->count()) {
            ServiceUser::find($index)->delete();
        } else {
            unset($this->temporaryServices[$index]);
            $this->temporaryServices = array_values($this->temporaryServices);
        }

        $this->emitSelf('refreshuser');
    }

    public function Save()
    {
        $this->user->save();

        if ($this->user->Employee) {
            // Atualiza o Employee existente
            $this->user->Employee()->update([
                'contract_id' => $this->contract
            ]);
        } else {
            // Cria um novo Employee
            $this->user->Employee()->create([
                'contract_id' => $this->contract
            ]);
        }

        if (count($this->temporaryServices)) {
            foreach ($this->temporaryServices as $service) {
                ServiceUser::updateOrCreate(
                    ['user_id' => $this->user->id],
                    $service
                );
            }
        }

        $this->emitUp('refresh_table_user');

        $this->closeAll();

    }

    public function closeAll()
    {

        $this->temporaryServices = [];

        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        $this->contractList = Contract::when($this->company, function ($q) {
            $q->where('company_id', $this->company);
        })->get();

        if ($this->contract && $contract = Contract::findOrFail($this->contract)) {
            $this->serviceList = $contract->services;
        } else {
            $this->serviceList = null;
        }



        return view('livewire.admin.user.actions.usuario');
    }
}
