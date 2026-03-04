<?php

namespace App\Http\Livewire\Production\Actions;

use App\Models\Company;
use App\Models\Production;
use App\Models\User;
use App\Services\D5\D5WorkflowService;
use Livewire\Component;

class ToAssign extends Component
{
    public ?Production $production = null;
    public $companies;
    public $users;
    public $companySelected;
    public $userSelected;
    public $ri = false;

    protected $listeners = [
        'toAssign',
        '700e54930f6ada5cdd88f7d276f022319f0a488b' => 'executeRemoveAssign',
        '685b132ff8d7da3aec7bd63f5291227952034495' => 'executeAssign',



    ];

    public function updatedCompanySelected($value)
    {
        $this->users = null;
        $this->userSelected = null;

        $this->users = User::whereRelation('ToServices', function ($q) {
            $q->where('service_id', $this->production->service_id);
        })->where('company_id', $value)->orderBy('name')->get();
    }

    public function toAssign(?Production $production)
    {
        $this->production = $production;

        if ($this->production && $this->production->user_id) {
            $this->toRemoveAssign();
        } elseif ($this->production && !$this->production->user_id) {
            if ($this->production) {

                $this->companies = Company::whereRelation('contracts.services', function ($q) {
                    $q->where('uuid', $this->production->service_id);
                })->orderBy('name')->get();

                // $this->users = User::whereRelation('ToServices', function ($q) {
                //     $q->where('service_id', $this->production->service_id);
                // })->orderBy('name')->get();

                $this->ri = $this->production->d5;

                $this->dispatchBrowserEvent('showModal', [
                    'id' => 'assign_production',
                ]);
            }
        }
    }

    private function toRemoveAssign()
    {
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Desatribuir Usuário',
            'msg'           => "
                <p class='fw-bold'>Deseja realmente desatribuir o usuário <strong>{$this->production->user?->name}</strong> de <strong>{$this->production->note->note}</strong>?</p>
            ",
            'icon'          => 'question',
            'btnOktxt'      => "Sim, Desatribuir",
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => '700e54930f6ada5cdd88f7d276f022319f0a488b',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum Usuário Desatribuído.',

        ]);
    }

    public function executeRemoveAssign()
    {
        $previousUserId = $this->production->user_id;

        try {
            $this->production->update([
                'user_id' => null,
                'status'  => 1,
            ]);

            $five = $this->production->note?->FiveNote;
            if ($five && $previousUserId) {
                app(D5WorkflowService::class)->onProductionUnassigned(
                    $five,
                    $this->production,
                    auth()->id(),
                    $previousUserId
                );
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Sucesso',
                'text'     => 'Usuário desatribuído com sucesso.',
                'timer'    => 2500,
            ]);

            $this->emitUp('refresh_list');

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro',
                'text'     => 'Erro ao desatribuir o usuário.',
                'timer'    => 2500,
            ]);

            return;
        }
    }


    public function goAssign()
    {
        $this->validate(
            [
                'companySelected' => 'required',
                'userSelected'    => 'required',
            ],
            [
                'companySelected.required' => 'Selecione a Empresa.',
                'userSelected.required'    => 'Selecione o Usuário.',
            ]
        );

        $users = clone $this->users;
        $user = $users->where('id', $this->userSelected)->first();

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Atribuir Usuário',
            'msg'           => "
                <p class='fw-bold'>Deseja realmente atribuir o usuário <strong>{$user?->name}</strong> a <strong>{$this->production->note->note}</strong>?</p>
            ",
            'icon'          => 'question',
            'btnOktxt'      => "Sim, Atribuir",
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => '685b132ff8d7da3aec7bd63f5291227952034495',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum Usuário Atribuído.',

        ]);
    }

    public function executeAssign()
    {
        $previousUserId = $this->production->user_id;

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

            $five = $this->production->note?->FiveNote;
            if ($five) {
                $five->productions()->syncWithoutDetaching([$this->production->id]);

                app(D5WorkflowService::class)->onProductionAssigned(
                    $five,
                    $this->production,
                    auth()->id(),
                    $previousUserId
                );
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Sucesso',
                'text'     => 'Usuário atribuído com sucesso.',
                'timer'    => 2500,
            ]);

            $this->emitUp('refresh_list');

            $this->closeall();

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro',
                'text'     => 'Erro ao atribuir o usuário.',
                'timer'    => 2500,
            ]);

            return;
        }
    }

    public function closeall()
    {
        $this->production = null;
        $this->companySelected = null;
        $this->userSelected = null;
        $this->companies = null;
        $this->users = null;

        $this->dispatchBrowserEvent('hideModal');
        $this->emitUp('refresh_list');
    }

    public function view()
    {
        return view('livewire.production.actions.to-assign');
    }
}
