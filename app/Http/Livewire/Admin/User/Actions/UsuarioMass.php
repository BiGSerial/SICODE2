<?php

namespace App\Http\Livewire\Admin\User\Actions;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Service;
use App\Models\ServiceUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class UsuarioMass extends Component
{
    private const LOCKABLE_PERMISSIONS = [
        'superadm',
        'admin',
        'management',
        'engineer',
        'responsible',
        'operator',
        'user',
        'btzero',
        'onlyparner',
        'can_dispatch',
        'analyst',
        'contract',
    ];

    public $users = null;
    public $companyList;
    public $company;
    public $contractList;
    public $contract;
    public $serviceList;
    public $serviceSelect;
    public $temporaryServices = [];
    public $changePermission = false;

    public $permissions = [
        'company_id' => false,
        'superadm' => false,
        'admin' => false,
        'management' => false,
        'engineer' => false,
        'responsible' => false,
        'operator' => false,
        'user' => true,
        'btzero' => false,
        'onlyparner' => false,
        'contract' => false,
        'analyst' => false,
    ];



    protected $listeners = [
        'alterUsers',
        'refreshuser' => '$refresh',
        'closeAll' => 'closeAll',
        'confirmResetPassword' => 'resetPassword',
        'confirmAlterUser' => 'Save'
    ];

    protected $rules = [

        'company' => 'required|exists:companies,id',
        'contract' => 'required|exists:contracts,id',
        'permissions.company_id' => 'required|string|max:255',
        'permissions.superadm' => 'boolean',
        'permissions.admin' => 'boolean',
        'permissions.management' => 'boolean',
        'permissions.engineer' => 'boolean',
        'permissions.operator' => 'boolean',
        'permissions.user' => 'boolean',
        'permissions.onlyparner' => 'boolean',
        'permissions.contract' => 'boolean',
        'permissions.responsible' => 'boolean',
        'permissions.btzero' => 'boolean',
        'permissions.analyst' => 'boolean',
        'user.can_dispatch' => 'boolean',
        'temporaryServices.*.service'  => 'boolean',
        'temporaryServices.*.dispatch'  => 'boolean',

    ];


    public function mount()
    {
        $this->companyList = Company::orderBy('name')->get();
    }

    public function alterUsers($selected)
    {

        if (!count($selected)) {
            return;
        }

        $this->users = User::find($selected);




        if ($this->users) {


            $this->dispatchBrowserEvent('showModal', [
                'id' => 'userMassEditModal',
            ]);
        }

        $this->emitSelf('refreshuser');

    }

    public function updatedCompany()
    {
        $this->contractList = $this->contractsForCompany($this->company);
        $this->contract = null;
        $this->serviceList = null;
        $this->temporaryServices = [];

        if ($this->contractList->count() === 1) {
            $this->contract = $this->contractList->first()->id;
            $this->updatedContract($this->contract);
        }
    }

    public function updatedContract($value)
    {
        $contract = $value ? Contract::with('services')->find($value) : null;
        $this->serviceList = $contract?->services;
        $this->temporaryServices = [];

        if (!$contract) {
            return;
        }

        $primaryService = $contract->services->first();

        if ($primaryService) {
            $this->serviceSelect = $primaryService->uuid;
            $this->temporaryServices = [[
                'service_id' => $primaryService->uuid,
                'service'    => true,
                'dispatch'   => $this->profileCanDispatch(),
            ]];
        }
    }




    public function addService()
    {
        if (!$this->serviceSelect) {
            return;
        }

        $this->temporaryServices = [[
            'service_id' => $this->serviceSelect,
            'service' => true,
            'dispatch' => $this->profileCanDispatch(),
        ]];

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
        unset($this->temporaryServices[$index]);
        $this->temporaryServices = array_values($this->temporaryServices);

        $this->emitSelf('refreshuser');
    }

    public function toSave()
    {
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Confirmar Alterações em Massa',
            'msg'           => "Você está prestes a alterar {$this->users->count()} usuários. <p>Deseja Continuar?</p> ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Altere!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirmAlterUser',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum usuário alterado.',

        ]);
    }



    public function Save()
    {
        $actor = auth()->user();
        $isSuperAdm = (bool) ($actor?->superadm);
        $actorLocks = $this->normalizePermissionLocks((array) ($actor?->permission_locks ?? []));
        $primaryServiceId = $this->resolvePrimaryServiceId();

        if ($this->selectedContractHasServices() && !$primaryServiceId) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Selecione ao menos uma atividade para os usuarios.',
                'timer'    => 2500,
            ]);

            return;
        }

        if ($this->users->count()) {

            foreach ($this->users as $user) {
                $shouldPreserveServices = $this->shouldPreserveExistingServices($user);
                $employeePayload = [
                    'contract_id' => $this->contract,
                ];

                if (!$shouldPreserveServices) {
                    $employeePayload['service_id'] = $primaryServiceId;
                }

                if ($user->Employee) {
                    // Atualiza o Employee existente
                    $user->Employee()->update($employeePayload);
                } else {
                    // Cria um novo Employee
                    $user->Employee()->create($employeePayload + [
                        'service_id' => $primaryServiceId,
                    ]);
                }


                if ($this->changePermission) {


                    $user->company_id  = $this->company;
                    $locks = $this->normalizePermissionLocks((array) ($user->permission_locks ?? []));

                    foreach (self::LOCKABLE_PERMISSIONS as $permission) {
                        if (!array_key_exists($permission, $this->permissions)) {
                            continue;
                        }

                        if (!$isSuperAdm && !empty($actorLocks[$permission])) {
                            continue;
                        }

                        $user->{$permission} = (bool) $this->permissions[$permission];
                    }

                    if (auth()->user()?->superadm) {
                        $user->permission_locks = $locks;
                    }
                }



                $user->save();
                $this->syncUserServices($user, $shouldPreserveServices);

            }

        }

        $this->emitUp('refresh_table_mass_user');

        $this->closeAll();

    }

    public function toResetMassPassword()
    {
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Confirmar Reiniciar Senha em Massa',
            'msg'           => "Você está prestes a reiniciar a senha de  {$this->users->count()} usuários. <p>Deseja Continuar?</p> ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Reinicie!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirmResetPassword',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum usuário teve o passeord reiniciado.',

        ]);
    }

    public function resetPassword()
    {
        $count = 0;

        if ($this->users->count()) {
            foreach ($this->users as &$user) {
                $user->password = Hash::make(123456);
                $user->first_pass = true;
                $count++;
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'SENHA ALTERADA',
                'html'     => "
                <div class='card'>
                    <div class='card-body'>
                    <h4 class='text-bg-primary fw-bold p-2'>NOVA SENHA: 123456</h4>
                    <p class='fw-bold'>USUÁRIO AFETADOS:\n
                    <h4 class='fw-bold text-primary'>{$count}</h4>
                    </p>
                    <p class='text-bg-danger p-2 rounded'>A nova senha surtirá efeito após <strong>SALVAR</strong>. Caso queira desconsiderar essa modificação, basta <strong>CANCELAR</strong>.</p>
                    </div>
                </div>
                ",

            ]);

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'SEM USUÁRIOS PARA ALTERAR',
                'html'     => "
                <div class='card'>
                    <div class='card-body'>
                    <h4 class='text-bg-primary fw-bold p-2'>NOVA SENHA: 123456</h4>
                    <p class='fw-bold'>USUÁRIOS AFETADOS:\n
                    <h4 class='fw-bold text-primary'>{$this->user->name}</h4>
                    </p>
                    <p class='text-bg-danger p-2 rounded'>A nova senha surtirá efeito após <strong>SALVAR</strong>. Caso queira desconsiderar essa modificação, basta <strong>CANCELAR</strong>.</p>
                    </div>
                </div>
                ",

            ]);
        }


    }

    public function closeAll()
    {

        $this->temporaryServices = [];

        $this->users = null;

        $this->permissions = [
            'company_id' => false,
            'superadm' => false,
            'admin' => false,
            'management' => false,
            'engineer' => false,
            'responsible' => false,
            'operator' => false,
            'user' => true,
            'btzero' => false,
            'onlyparner' => false,
            'contract' => false,
            'analyst' => false,
        ];



        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        if ($this->contract && $contract = Contract::with('services')->findOrFail($this->contract)) {
            $this->serviceList = $contract->services;
        } else {
            $this->serviceList = null;
        }



        return view('livewire.admin.user.actions.usuario-mass');
    }

    private function normalizePermissionLocks(array $locks): array
    {
        $normalized = [];

        foreach (self::LOCKABLE_PERMISSIONS as $permission) {
            $normalized[$permission] = (bool) ($locks[$permission] ?? false);
        }

        return $normalized;
    }

    private function resolvePrimaryServiceId(): ?string
    {
        $contract = $this->contract ? Contract::with('services')->find($this->contract) : null;
        $temporaryService = collect($this->temporaryServices)
            ->first(fn ($service) => !empty($service['service_id']));

        if ($temporaryService) {
            return $temporaryService['service_id'];
        }

        return null;
    }

    private function selectedContractHasServices(): bool
    {
        if (!$this->contract) {
            return false;
        }

        return Contract::query()
            ->whereKey($this->contract)
            ->whereHas('services')
            ->exists();
    }

    private function syncUserServices(User $user, bool $preserveExistingServices = false): void
    {
        if ($preserveExistingServices) {
            return;
        }

        $serviceId = $this->resolvePrimaryServiceId();

        if (!$serviceId || $user->ToServices()->where('service_id', $serviceId)->exists()) {
            return;
        }

        $canExecute = (bool) ($user->user || $user->operator || $user->admin);
        $canDispatch = (bool) ($user->operator || $user->admin);

        $user->ToServices()->create([
            'service_id' => $serviceId,
            'service' => $canExecute,
            'dispatch' => $canDispatch,
        ]);
    }

    private function shouldPreserveExistingServices(User $user): bool
    {
        return $user->ToServices()->exists();
    }

    private function profileCanDispatch(): bool
    {
        return (bool) (($this->permissions['admin'] ?? false) || ($this->permissions['operator'] ?? false));
    }

    private function contractsForCompany($companyId)
    {
        $company = $companyId ? Company::with('parent')->find($companyId) : null;
        $companyIds = collect([$company?->id, $company?->parent_id])->filter()->values();

        return Contract::with('services', 'company')
            ->whereIn('company_id', $companyIds)
            ->orderBy('number')
            ->get();
    }
}
