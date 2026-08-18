<?php

namespace App\Http\Livewire\Admin\User\Actions;

use App\Models\{City, Company, Contract, Service, ServiceUser, User};
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Usuario extends Component
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
        'legal_controller',
        'legal_field',
        'legal_manager',
    ];

    public $user;

    public ?User $userCompany = null;

    public $companyList;

    public $company;

    public $contractList;

    public $contract;

    public $serviceList;

    public $serviceSelect;

    public $regionList;

    public $region;

    public $cities;

    public $city;

    public $companySelect;

    // public $newUser;
    public $temporaryPassword;

    public $temporaryFirstPass;

    public $temporaryServices = [];

    public $temporaryCompanies = [];

    protected $listeners = [
        'openUser'    => 'openUser',
        'refreshuser' => '$refresh',
        'newUser'     => 'newUser',
        'closeAll'    => 'closeAll',
    ];

    protected $rules = [
        'user.email'                             => 'required|email',
        'user.name'                              => 'required|string|max:255',
        'user.Registration'                      => 'string|max:80',
        'company'                                => 'required|exists:companies,id',
        'contract'                               => 'required|exists:contracts,id',
        'user.company_id'                        => 'required|string|max:255',
        'user.superadm'                          => 'boolean',
        'user.admin'                             => 'boolean',
        'user.management'                        => 'boolean',
        'user.engineer'                          => 'boolean',
        'user.operator'                          => 'boolean',
        'user.user'                              => 'boolean',
        'user.onlyparner'                        => 'boolean',
        'user.contract'                          => 'boolean',
        'user.responsible'                       => 'boolean',
        'user.btzero'                            => 'boolean',
        'user.can_dispatch'                      => 'boolean',
        'user.analyst'                           => 'boolean',
        'user.legal_controller'                  => 'boolean',
        'user.legal_field'                       => 'boolean',
        'user.legal_manager'                     => 'boolean',
        'user.permission_locks'                  => 'nullable|array',
        'user.permission_locks.superadm'         => 'boolean',
        'user.permission_locks.admin'            => 'boolean',
        'user.permission_locks.management'       => 'boolean',
        'user.permission_locks.engineer'         => 'boolean',
        'user.permission_locks.responsible'      => 'boolean',
        'user.permission_locks.operator'         => 'boolean',
        'user.permission_locks.user'             => 'boolean',
        'user.permission_locks.btzero'           => 'boolean',
        'user.permission_locks.onlyparner'       => 'boolean',
        'user.permission_locks.can_dispatch'     => 'boolean',
        'user.permission_locks.analyst'          => 'boolean',
        'user.permission_locks.contract'         => 'boolean',
        'user.permission_locks.legal_controller' => 'boolean',
        'user.permission_locks.legal_field'      => 'boolean',
        'user.permission_locks.legal_manager'    => 'boolean',
        'regiaoControle'                         => 'string|in:norte,centroNorte,centroSul,sul',
    ];

    protected $casts = [
        'superadm'   => 'boolean',
        'admin'      => 'boolean',
        'management' => 'boolean',
        // Outros campos booleanos
    ];

    public function mount()
    {
        if (!Auth()->User()->contract) {
            $this->companyList = Company::orderBy('name')->get();
        } elseif (Auth()->User()->Companies->count()) {

            $this->userCompany = auth()->user();
            $this->companyList = $this->userCompany->Companies()->get();
        } else {
            $this->companyList = Company::where('id', Auth()->User()->company_id)->orderBy('name')->get();
        }

        $this->cities     = City::orderBy('cidade')->get();
        $this->regionList = City::orderBy('regiao')->distinct()->pluck('regiao');
    }

    public function updatedRegion()
    {

    }

    public function openUser($user)
    {
        $this->user = User::findOrFail($user['id']);

        if ($this->user) {
            // dd($this->user);

            if (!$this->user->company_id) {
                $this->user->company_id = $this->user->Employee->Contract->company->id ?? null;
                $this->user->save();
            }

            $this->contractList           = $this->contractsForCompany($this->user->company_id);
            $this->company                = $this->user->Employee->Contract->company->id ?? '';
            $this->contract               = $this->user->Employee->Contract->id ?? '';
            $this->user->permission_locks = $this->normalizePermissionLocks((array) ($this->user->permission_locks ?? []));

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'userModal',
            ]);
        }

        $this->emitSelf('refreshuser');

    }

    public function updatedUserCompanyId($value = null)
    {
        $companyId = $value ?: $this->user?->company_id;

        $this->contractList = $this->contractsForCompany($companyId);

        $this->contract      = null;
        $this->serviceList   = null;
        $this->serviceSelect = null;

        if (!$this->user?->exists) {
            $this->temporaryServices = [];
        }

        if ($this->contractList->count() === 1) {
            $this->contract = $this->contractList->first()->id;
            $this->updatedContract($this->contract);
        }
    }

    public function updatedContract($value)
    {
        $contract = $value ? Contract::with('services')->find($value) : null;

        $this->serviceList   = $contract?->services;
        $this->serviceSelect = null;

        if (!$contract) {
            return;
        }

        if (!$this->user?->exists || (!$this->user->ToServices->count() && !count($this->temporaryServices))) {
            $this->applyContractServices();
        }
    }

    public function newUser()
    {

        $this->user                   = new User();
        $this->user->permission_locks = $this->normalizePermissionLocks([]);
        $this->user->user             = true;

        $this->temporaryPassword  = Hash::make(123456);
        $this->temporaryFirstPass = 1;

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'userModal',
        ]);

    }

    public function addService()
    {
        if (!$this->serviceSelect) {
            return;
        }

        if ($this->user->ToServices->count()) {
            ServiceUser::updateOrCreate(
                ['user_id'       => $this->user->id,
                    'service_id' => $this->serviceSelect],
                [
                    'service'  => 0,
                    'dispatch' => 0,
                ]
            );
        } else {
            if (collect($this->temporaryServices)->contains('service_id', $this->serviceSelect)) {

                return;
            }

            $this->temporaryServices[] = [
                'service_id' => $this->serviceSelect,
                'service'    => false,
                'dispatch'   => false,
            ];

        }

        $this->emitSelf('refreshuser');
    }

    public function applyContractServices()
    {
        $contract = $this->contract ? Contract::with('services')->find($this->contract) : null;

        if (!$contract || !$contract->services->count()) {
            return;
        }

        $canDispatch = $this->profileCanDispatch();

        foreach ($contract->services as $service) {
            if ($this->user?->exists) {
                $this->user->ToServices()->updateOrCreate(
                    ['service_id' => $service->uuid],
                    [
                        'service'  => true,
                        'dispatch' => $canDispatch,
                    ]
                );

                continue;
            }

            if (collect($this->temporaryServices)->contains('service_id', $service->uuid)) {
                continue;
            }

            $this->temporaryServices[] = [
                'service_id' => $service->uuid,
                'service'    => true,
                'dispatch'   => $canDispatch,
            ];
        }

        $this->emitSelf('refreshuser');
    }

    public function addCompany()
    {

        $this->user->companies()->syncWithoutDetaching([
            $this->companySelect,
        ]);

        $this->emitSelf('refreshuser');
    }

    public function removeCompany($company_id)
    {

        if ($this->user->companies()->where('company_id', $company_id)->exists()) {
            $this->user->companies()->detach($company_id);

            $this->emitSelf('refreshuser');
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'SEM EMPRESA PARA DESASSOCIAR',
                'html'     => 'A Empresa selecionada não existe ou ja ',
                'timer'    => 2500,
            ]);

            return;
        }

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
        $actor        = auth()->user();
        $isSuperAdm   = (bool) ($actor?->superadm);
        $actorLocks   = $this->normalizePermissionLocks((array) ($actor?->permission_locks ?? []));
        $originalUser = null;

        if ($this->user?->id) {
            $originalUser = User::withTrashed()->find($this->user->id);
        }
        $existingLocks  = $this->normalizePermissionLocks((array) ($originalUser?->permission_locks ?? []));
        $incomingLocks  = $this->normalizePermissionLocks((array) ($this->user->permission_locks ?? []));
        $effectiveLocks = $isSuperAdm ? $incomingLocks : $existingLocks;
        $primaryServiceId = $this->resolvePrimaryServiceId();

        if ($this->selectedContractHasServices() && !$primaryServiceId) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Selecione ao menos uma atividade para o usuário.',
                'timer'    => 2500,
            ]);

            return;
        }

        if (!$isSuperAdm) {
            foreach (self::LOCKABLE_PERMISSIONS as $permission) {
                if (!empty($actorLocks[$permission])) {
                    if ($originalUser) {
                        $this->user->{$permission} = (bool) $originalUser->{$permission};
                    } else {
                        $this->user->{$permission} = false;
                    }
                }
            }
        }

        foreach (self::LOCKABLE_PERMISSIONS as $permission) {
            $this->user->{$permission} = (bool) ($this->user->{$permission} ?? false);
        }

        $this->user->permission_locks = $effectiveLocks;

        if ($this->temporaryFirstPass) {
            $this->user->password   = $this->temporaryPassword;
            $this->user->first_pass = $this->temporaryFirstPass;

            $copyArray = [
                'name'    => $this->user->name,
                'company' => $this->user->Company ? $this->user->Company->name : 'N/A',
                'email'   => $this->user->email,
            ];

            $this->dispatchBrowserEvent('copySicodeAccess', $copyArray);
        }

        $this->user->save();

        if ($this->user->Employee) {
            // Atualiza o Employee existente
            $this->user->Employee()->update([
                'contract_id' => $this->contract,
                'service_id'  => $primaryServiceId,
            ]);
        } else {
            // Cria um novo Employee
            $this->user->Employee()->create([
                'contract_id' => $this->contract,
                'service_id'  => $primaryServiceId,
            ]);
        }

        $this->syncSelectedContractServices();

        $this->emitUp('refresh_table_user');

        $this->closeAll();

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
        $contractServiceId = $contract?->services?->first()?->uuid;
        if ($contractServiceId) {
            return $contractServiceId;
        }

        $temporaryService = collect($this->temporaryServices)
            ->first(fn ($service) => !empty($service['service_id']));

        return $temporaryService['service_id'] ?? null;
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

    private function syncSelectedContractServices(): void
    {
        $contract = $this->contract ? Contract::with('services')->find($this->contract) : null;
        $serviceIds = $contract
            ? $contract->services->pluck('uuid')->filter()->values()->all()
            : collect($this->temporaryServices)->pluck('service_id')->filter()->values()->all();

        if (!$serviceIds) {
            $this->user->ToServices()->delete();
            return;
        }

        $this->user->ToServices()->whereNotIn('service_id', $serviceIds)->delete();
        $canDispatch = $this->profileCanDispatch();
        $existingServices = $this->user->ToServices()
            ->whereIn('service_id', $serviceIds)
            ->get()
            ->keyBy('service_id');
        $temporaryServices = collect($this->temporaryServices)->keyBy('service_id');

        foreach ($serviceIds as $serviceId) {
            $existingService = $existingServices->get($serviceId);
            $temporaryService = $temporaryServices->get($serviceId);

            $this->user->ToServices()->updateOrCreate(
                ['service_id' => $serviceId],
                [
                    'service' => $existingService
                        ? (bool) $existingService->service
                        : (bool) ($temporaryService['service'] ?? true),
                    'dispatch' => $existingService
                        ? (bool) $existingService->dispatch
                        : (bool) ($temporaryService['dispatch'] ?? $canDispatch),
                ]
            );
        }
    }

    private function profileCanDispatch(): bool
    {
        return (bool) (($this->user?->admin ?? false) || ($this->user?->operator ?? false));
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

    public function resetPassword()
    {
        $this->temporaryPassword  = Hash::make(123456);
        $this->temporaryFirstPass = 1;

        // dd($this->user);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'warning',
            'title'    => 'SENHA ALTERADA',
            'html'     => "
            <div class='card'>
                <div class='card-body'>
                <h4 class='text-bg-primary fw-bold p-2'>NOVA SENHA: 123456</h4>
                <p class='fw-bold'>USUÁRIO AFETADO:\n
                <h4 class='fw-bold text-primary'>{$this->user->name}</h4>
                </p>
                <p class='text-bg-danger p-2 rounded'>A nova senha surtirá efeito após <strong>SALVAR</strong>. Caso queira desconsiderar essa modificação, basta <strong>CANCELAR</strong>.</p>
                </div>
            </div>
            ",

        ]);
    }

    public function copyClipboarder()
    {
        $text = "
        SISTEMA SICODE - ACESSO DO USUARIO
        =====================================\n
        NOME: {$this->user->name}\n
        EMAIL: {$this->user->email}\n
        SENHA: 123456\n
        SERVIDOR: http://edpbr1204/es/\n
        =====================================\n
        ";

        $this->dispatchBrowserEvent('copyToBoard', ['text' => $text]);

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => "Copiado para a área de transferência",
        ]);
    }

    public function closeAll()
    {

        $this->temporaryServices = [];

        $this->user = null;

        $this->temporaryPassword  = null;
        $this->temporaryFirstPass = null;

        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        if ($this->contract && $contract = Contract::with('services')->findOrFail($this->contract)) {
            $this->serviceList = $contract->services;
        } else {
            $this->serviceList = null;
        }

        return view('livewire.admin.user.actions.usuario', [
            'contractActivities' => $this->contract
                ? Service::whereRelation('Contracts', 'contracts.id', $this->contract)->orderBy('service')->get()
                : collect(),
        ]);
    }
}
