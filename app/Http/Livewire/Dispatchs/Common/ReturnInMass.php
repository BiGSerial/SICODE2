<?php

namespace App\Http\Livewire\Dispatchs\Common;

use App\Models\Company;
use App\Models\Reclaim;
use App\Models\Service;
use App\Models\Production;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReturnInMass extends Component
{
    public $reclaims;
    public $service;
    public $companySelected;
    public $userSelected;
    public $user;
    public $search = '';

    protected $listeners = [
        'goOpenMassAtt',
        'closeall',
        'a22c4b18e39bd31698eb4bb3890b2e67e667ae400e2829d8ce3f276e02606d19' => 'changeInMassUser',
    ];

    public function mount(Service $service)
    {
        $this->service = $service;
    }

    public function getCompaniesProperty()
    {
        return Company::query()
            ->linkedToService($this->service->uuid)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function updatedUserSelected($user_id)
    {

        $this->user = User::find($user_id);
    }


    public function goOpenMassAtt(array $reclaims)
    {
        $this->cleanAll();

        $this->reclaims = Reclaim::whereIn('id', $reclaims)->get();

        if ($this->reclaims->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'SEM OBRAS SELECIONADAS',
                'html'      => 'Verifique a seleção das obras e tente novamente.',
                'timer'    => 5000,
            ]);
            return;
        } else {


            $this->dispatchBrowserEvent('showModal', [
                'id' => 'massReturnAtt',
            ]);
        }
    }

    public function getUsersProperty()
    {
        if (!$this->companySelected) {
            return collect();
        }

        return User::where('company_id', $this->companySelected)
                    ->whereRelation('ToServices', function ($q) {
                        $q->where('service_id', $this->service->uuid)
                            ->where('service', true);
                    })
                    ->when(trim($this->search), function ($q) {
                        $q->where('name', 'like', "%".trim($this->search)."%");
                    })
                    ->select('id', 'name', 'company_id')
                    ->orderBy('name')
                    ->get();
    }

    public function goChangeInMassUser()
    {
        if (!$this->user) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'A Seleção do Usuário de destino é Obrigatória',
                'timer'    => 5000,
            ]);
            return;
        }



        if ($this->reclaims->filter(function ($reclaim) {
            return !is_null($reclaim->production);
        })->count() > 0) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Confirmar Transferência',
                'msg'           => "<div class='alert alert-warning' role='alert'>
                                      <h4 class='alert-heading'><i class='fas fa-exclamation-triangle'></i> Atenção!</h4>
                                      <p>Foram detectadas <strong>".count($this->reclaims->filter(function ($reclaim) { return !is_null($reclaim->production); }))." produções</strong> que já possuem usuários atribuídos.</p>
                                      <hr>
                                      <p class='mb-0'>Ao continuar, você <span class='text-danger font-weight-bold'>substituirá</span> a titularidade das produções existentes.</p>
                                      <p class='mt-2'>Esta ação não pode ser desfeita. Confirma esta operação?</p>
                                    </div>",
                'icon'          => 'warning',
                'btnOktxt'      => '<i class="fas fa-exchange-alt"></i> Sim, Transfira!',
                'btnCanceltxt'  => '<i class="fas fa-times"></i> Não, Cancele',
                'action'        => 'a22c4b18e39bd31698eb4bb3890b2e67e667ae400e2829d8ce3f276e02606d19',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nota foi transferida.',
            ]);

            return;
        } else {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Confirmar Transferência',
                'msg'           => "Você está preste de enviar ".count($this->reclaims)." obras. \n Deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Transfira!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'a22c4b18e39bd31698eb4bb3890b2e67e667ae400e2829d8ce3f276e02606d19',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nenhuma nota foi Transferida.',

            ]);

            return;
        }
    }

    public function changeInMassUser()
    {
        if (!$this->reclaims) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO INTERNO',
                'html'      => 'Reclaims está vazio....',
                'timer'    => 5000,
            ]);
            return;
        }

        $error = false;

        DB::beginTransaction();



        foreach ($this->reclaims as $reclaim) {

            if ($reclaim->production) {
                try {
                    $reclaim->production->update([
                        'user_id' => $this->user->id,
                        'att_at' => now(),
                        'att_by' => auth()->user()->id,
                        'dispatch_at' => now(),
                        'dispatch_by' => auth()->user()->id,
                        'status' => 2,
                        'completed_at' => null,
                        'confirmed_at' => null,
                        'company_id' => $this->user->company_id,
                        'note_id' => $reclaim->note->id,
                        'dt_note' => $reclaim->note->dt_note,
                        'd5' => true,
                        'centroTrab' => $reclaim->note->centerjob,
                        'status_note' => $reclaim->note->nstats,
                    ]);
                } catch (\Exception $e) {

                    $error = true;
                }

            } else {

                try {

                    $production = Production::create([
                        'user_id' => $this->user->id,
                        'att_at' => now(),
                        'att_by' => auth()->user()->id,
                        'service_id' => $this->service->uuid,
                        'dispatch_at' => now(),
                        'dispatch_by' => auth()->user()->id,
                        'status' => 2,
                        'completed_at' => null,
                        'confirmed_at' => null,
                        'company_id' => $this->user->company_id,
                        'note_id' => $reclaim->note->id,
                        'dt_note' => $reclaim->note->dt_note,
                        'd5' => true,
                        'centroTrab' => $reclaim->note->centerjob,
                        'status_note' => $reclaim->note->nstats,
                    ]);

                    if ($production) {
                        $reclaim->update([
                            'production_id' => $production->id,
                        ]);
                    };

                } catch (\Throwable $th) {

                    $error = true;
                }
            }

        }

        if ($error) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO INTERNO',
                'html'      => 'Ocorreum um erro interno, refaça as etapas e tente novamente.',
                'timer'    => 5000,
            ]);
            DB::rollback();
            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'ATRIBUIÇÃO COM SUCESSO',
            'html'      => 'As obras foram atribuídas com sucesso.',
            'timer'    => 5000,
        ]);


        DB::commit();

        $this->dispatchBrowserEvent('hideModal');
        $this->emit('refresh_list');
        $this->emit('cleanAll');
        $this->cleanAll();
    }

    public function cleanAll()
    {
        $this->companySelected = null;
        $this->userSelected = null;
        $this->reclaims = null;
        $this->user = null;
        $this->search = null;

    }



    public function cancelReturnMass()
    {
        $this->dispatchBrowserEvent('hideModal');
        $this->cleanAll();
    }

    public function render()
    {


        return view('livewire.dispatchs.common.return-in-mass', [
            'companies' => $this->companies,
            'users' => $this->users,
        ]);
    }
}
