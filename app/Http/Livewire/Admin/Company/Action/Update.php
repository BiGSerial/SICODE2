<?php

namespace App\Http\Livewire\Admin\Company\Action;

use App\Exports\Admin\Company\UserRegistrationWorkbookExport;
use App\Exports\Admin\Company\UserRegistrationErrorsExport;
use App\Models\Andresscompany;
use App\Models\Centerjob;
use App\Models\Company;
use App\Models\Contract;
use App\Models\PartnerCompanyPermissionGrant;
use App\Models\Service;
use App\Services\Admin\Company\UserRegistrationWorkbookService;
use App\Services\PartnerAccess\PartnerPermissionCatalog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class Update extends Component
{
    use WithFileUploads;

    public $photo0;
    public $photo1;
    public $photo2;
    public $photo3;
    public ?Company $company = null;
    public $addresses = [];
    public ?Andresscompany $newAddress = null;
    public ?Centerjob $centerjob = null;
    public ?Contract $contractForm = null;
    public $contractNumber = '';
    public $contractDateEnd = '';
    public $contractService = false;
    public $contractConstruction = false;
    public $contractSelectedServices = [];
    public $contractServiceDispatch = [];
    public $contractActivitySearch = '';
    public $showContractForm = false;
    public $contractDeleteId;
    public $branchName = '';
    public $branchEmail = '';
    public $branchTelephone = '';
    public $showBranchForm = false;
    public $branchSearch = '';
    public $branchAttachId = '';
    public $registrationWorkbook;
    public array $registrationValidation = [];
    public array $partnerGrantPermissions = [];
    public bool $partnerGrantConfigured = false;

    protected $listeners = [
        'refreshlist' => '$refresh',
        'openModal',
        'confirm_remove_company_contract' => 'removeContractConfirmed',
    ];



    protected $rules = [
        'company.name' => 'required|string|max:255',
        'company.email' => 'required|email',
        'company.telephone' => 'required|string',
        'company.partner_user_inactivity_days' => 'nullable|integer|min:1|max:3650',
        'addresses' => 'nullable|array',
        'addresses.*.street' => 'nullable|string',
        'addresses.*.city' => 'nullable|string',
        'addresses.*.uf' => 'nullable|string|size:2',
        'addresses.*.complement' => 'nullable|string',
        'newAddress.street' => 'nullable|string',
        'newAddress.city' => 'nullable|string',
        'newAddress.uf' => 'nullable|string|size:2',
        'newAddress.complement' => 'nullable|string',
        'centerjob.center' => 'nullable|string',
        'centerjob.deposit' => 'nullable|string',
        'centerjob.centerjob' => 'nullable|string',
        'photo0' => 'nullable|image|max:2048',
        'photo1' => 'nullable|image|max:2048',
        'photo2' => 'nullable|image|max:2048',
        'photo3' => 'nullable|image|max:2048',
        'registrationWorkbook' => 'nullable|file|mimes:xlsx,xls|max:10240',
    ];

    public function mount()
    {
        for ($i = 0; $i < 4; $i++) {
            $this->{"photo$i"} = null;
        }
    }


    public function title_img($id)
    {
        switch ($id) {
            case '0':
                return (object) [
                    'title' => 'Logo para Fundo Claro',
                    'name' => 'img_w_path',
                ];
                break;
            case '1':
                return (object) [
                    'title' => 'Logo para Fundo Escuro',
                    'name' => 'img_b_path',
                ];
                break;
            case '2':
                return (object) [
                    'title' => 'Logo para Reduzido Fundo Claro',
                    'name' => 'img_rw_path',
                ];
                break;
            case '3':
                return (object) [
                    'title' => 'Logo para Reduzido Fundo Escuro',
                    'name' => 'img_rb_path',
                ];
                break;
            default:
                return 'ERROR';
                break;
        }
    }

    public function openModal(Company $company)
    {

        $this->company = $company->load('parent', 'branches.Address', 'branches.contracts.services', 'Address', 'Centerjobs', 'contracts.services');
        $this->addresses = $this->company->Address;
        $this->photo0 = null;
        $this->photo1 = null;
        $this->photo2 = null;
        $this->photo3 = null;
        $this->resetContractForm();
        $this->resetBranchForm();
        $this->loadPartnerGrantPermissions();

        // dd($this->company);

        if ($this->company) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'companyModal',
            ]);
        }

        $this->emitSelf('refreshlist');

    }

    public function addAddress()
    {
        $this->newAddress = new Andresscompany();

    }


    public function cancelAddress()
    {
        $this->newAddress = null;
    }

    public function saveAddress()
    {
        try {
            $this->validate([
                'newAddress.street' => 'nullable|string',
                'newAddress.city' => 'nullable|string',
                'newAddress.uf' => 'nullable|string|size:2',
                'newAddress.complement' => 'nullable|string'
            ]);


        } catch (ValidationException $e) {
            dd($e->errors());
        }

        if ($this->newAddress) {

            $this->newAddress->company_id = $this->company->id;

            if ($this->newAddress->save()) {
                $this->newAddress = null;
                $this->emitSelf('refreshlist');
            }
        }


    }

    public function removeAddress(Andresscompany $address)
    {
        if ($address) {
            $address->delete();
            $this->emitSelf('refreshlist');
        }
    }

    public function addCenterjob()
    {
        $this->centerjob = new Centerjob();

    }

    public function cancelCenterjob()
    {
        $this->centerjob = null;
    }

    public function saveCenterjob()
    {
        $this->validate([
            'centerjob.center' => 'nullable|string',
            'centerjob.deposit' => 'nullable|string',
            'centerjob.centerjob' => 'nullable|string',
        ]);

        if ($this->centerjob) {

            $this->centerjob->company_id = $this->company->id;
            $this->centerjob->center = strtoupper(trim($this->centerjob->center));
            $this->centerjob->deposit = strtoupper(trim($this->centerjob->deposit));
            $this->centerjob->centerjob = strtoupper(trim($this->centerjob->centerjob));

            if ($this->centerjob->save()) {
                $this->centerjob = null;
                $this->reloadCompany();
                $this->emitSelf('refreshlist');
            }
        }


    }

    public function removeCenterjob(Centerjob $centerjob)
    {
        if ($centerjob) {
            $centerjob->delete();
            $this->reloadCompany();
            $this->emitSelf('refreshlist');
        }
    }

    public function newContract()
    {
        $this->resetContractForm();
        $this->contractForm = new Contract();
        $this->contractService = true;
        $this->showContractForm = true;
    }

    public function editContract(Contract $contract)
    {
        $this->contractForm = $contract->load('services');
        $this->contractNumber = $contract->number;
        $this->contractDateEnd = $contract->date_end;
        $this->contractService = (bool) $contract->service;
        $this->contractConstruction = (bool) $contract->construction;
        $this->contractSelectedServices = $contract->services
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
        $this->contractServiceDispatch = $contract->services
            ->mapWithKeys(fn ($service) => [(string) $service->id => (bool) $service->pivot->dispatch])
            ->all();
        $this->showContractForm = true;
    }

    public function cancelContract()
    {
        $this->resetContractForm();
    }

    public function saveContract()
    {
        if (!$this->company) {
            return;
        }

        if (!trim($this->contractNumber) || !$this->contractDateEnd) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Informe o numero e a validade do contrato.',
                'timer'    => 2500,
            ]);

            return;
        }

        if (!($this->contractService || $this->contractConstruction)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Selecione o tipo do contrato.',
                'timer'    => 2500,
            ]);

            return;
        }

        if (!count($this->contractSelectedServices)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Selecione ao menos uma atividade liberada.',
                'timer'    => 2500,
            ]);

            return;
        }

        $contract = $this->contractForm?->exists ? $this->contractForm : new Contract();
        $contract->fill([
            'number'       => trim($this->contractNumber),
            'service'      => (bool) $this->contractService,
            'construction' => (bool) $this->contractConstruction,
            'date_end'     => date('Y-m-d', strtotime($this->contractDateEnd)),
        ]);

        if (!$contract->exists) {
            $contract->company_id = $this->company->id;
        }

        $contract->save();
        $this->syncContractServices($contract);
        $this->reloadCompany();
        $this->resetContractForm();
        $this->emitUp('refresh_table_company');

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Contrato salvo com sucesso.',
            'timer'    => 2000,
        ]);
    }

    public function confirmRemoveContract(Contract $contract)
    {
        $this->contractDeleteId = $contract->id;

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Remover contrato',
            'msg'           => "Deseja remover o contrato <strong>{$contract->number}</strong> desta empresa?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, remover',
            'btnCanceltxt'  => 'Cancelar',
            'action'        => 'confirm_remove_company_contract',
            'cancel_titulo' => 'Cancelado',
            'cancel_msg'    => 'Nenhum contrato removido.',
        ]);
    }

    public function removeContractConfirmed()
    {
        $contract = $this->contractDeleteId ? Contract::find($this->contractDeleteId) : null;

        if ($contract) {
            $contract->delete();
            $this->reloadCompany();
            $this->emitUp('refresh_table_company');
        }

        $this->contractDeleteId = null;
    }

    public function newBranch()
    {
        $this->resetBranchForm();
        $this->showBranchForm = true;
    }

    public function cancelBranch()
    {
        $this->resetBranchForm();
    }

    public function saveBranch()
    {
        if (!$this->company || !trim($this->branchName) || !trim($this->branchEmail)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Informe nome e email da unidade.',
                'timer'    => 2500,
            ]);

            return;
        }

        $parentId = $this->company->parent_id ?: $this->company->id;

        Company::create([
            'parent_id'  => $parentId,
            'name'       => ucwords(mb_strtolower($this->branchName)),
            'email'      => $this->branchEmail,
            'telephone'  => $this->branchTelephone,
        ]);

        $this->reloadCompany();
        $this->resetBranchForm();
        $this->emitUp('refresh_table_company');

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Unidade cadastrada com sucesso.',
            'timer'    => 2000,
        ]);
    }

    public function attachExistingBranch()
    {
        if (!$this->company || !$this->branchAttachId || $this->branchAttachId === $this->company->id) {
            return;
        }

        $branch = Company::find($this->branchAttachId);

        if (!$branch) {
            return;
        }

        $branch->parent_id = $this->company->parent_id ?: $this->company->id;
        $branch->save();

        $this->branchAttachId = '';
        $this->branchSearch = '';
        $this->reloadCompany();
        $this->emitUp('refresh_table_company');

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Empresa associada como unidade.',
            'timer'    => 2000,
        ]);
    }

    public function downloadUserRegistrationWorkbook()
    {
        if (!$this->company) {
            return null;
        }

        $root = $this->company->parent ?: $this->company;
        $filename = 'ficha-cadastro-usuarios-' . Str::slug($root->name) . '-' . now()->format('Ymd-His') . '.xlsx';

        error_reporting(error_reporting() & ~E_DEPRECATED);

        return Excel::download(new UserRegistrationWorkbookExport($root), $filename);
    }

    public function processUserRegistrationWorkbook()
    {
        $this->validate([
            'registrationWorkbook' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $service = app(UserRegistrationWorkbookService::class);
        $path = $this->registrationWorkbook->store('tmp/user-registration-workbooks');
        $this->registrationValidation = $service->validate($this->company, $path, 'local');

        $summary = $this->registrationValidation['summary'] ?? [];
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => (($summary['users_invalid'] ?? 0) || ($summary['units_invalid'] ?? 0)) ? 'warning' : 'success',
            'title'    => 'Ficha processada',
            'html'     => sprintf(
                'Usuarios validos: <strong>%s</strong><br>Usuarios com erro: <strong>%s</strong><br>Filiais validas: <strong>%s</strong><br>Filiais com erro: <strong>%s</strong>',
                $summary['users_valid'] ?? 0,
                $summary['users_invalid'] ?? 0,
                $summary['units_valid'] ?? 0,
                $summary['units_invalid'] ?? 0
            ),
            'timer'    => 4000,
        ]);
    }

    public function exportUserRegistrationErrors()
    {
        if (!$this->registrationValidation) {
            return null;
        }

        error_reporting(error_reporting() & ~E_DEPRECATED);

        return Excel::download(
            new UserRegistrationErrorsExport($this->registrationValidation),
            'inconsistencias-ficha-usuarios-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function confirmUserRegistrationWorkbook()
    {
        if (!$this->registrationValidation) {
            return;
        }

        $service = app(UserRegistrationWorkbookService::class);
        $result = $service->processValid($this->company, $this->registrationValidation);
        $this->registrationValidation = [];
        $this->registrationWorkbook = null;
        $this->reloadCompany();
        $this->emitUp('refresh_table_company');

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Importacao concluida',
            'html'     => sprintf(
                'Filiais criadas: <strong>%s</strong><br>Usuarios criados: <strong>%s</strong><br>Usuarios atualizados: <strong>%s</strong><br>Usuarios removidos: <strong>%s</strong>',
                $result['createdUnits'] ?? 0,
                $result['createdUsers'] ?? 0,
                $result['updatedUsers'] ?? 0,
                $result['removedUsers'] ?? 0
            ),
            'timer'    => 4500,
        ]);
    }

    public function detachBranch(Company $branch)
    {
        if (!$this->company || $branch->parent_id !== ($this->company->parent_id ?: $this->company->id)) {
            return;
        }

        $branch->parent_id = null;
        $branch->save();

        $this->reloadCompany();
        $this->emitUp('refresh_table_company');
    }

    public function save()
    {
        $this->validate([
            'photo0' => 'nullable|image|max:2048',
            'photo1' => 'nullable|image|max:2048',
            'photo2' => 'nullable|image|max:2048',
            'photo3' => 'nullable|image|max:2048',
            'company.partner_user_inactivity_days' => 'nullable|integer|min:1|max:3650',
        ]);


        if ($this->company) {
            $this->company->partner_user_inactivity_days = $this->company->partner_user_inactivity_days ?: null;
            $this->savePartnerPermissionGrants();

            for ($i = 0; $i < 4; $i++) {
                $photo = $this->title_img($i);

                if ($this->{"photo$i"}) {

                    if ($this->company->{$photo->name} && Storage::disk('public')->exists($this->company->{$photo->name})) {
                        Storage::disk('public')->delete($this->company->{$photo->name});
                    }

                    $extension = $this->{"photo$i"}->getClientOriginalExtension();
                    $originalName = pathinfo($this->{"photo$i"}->getClientOriginalName(), PATHINFO_FILENAME);
                    $filename = Str::slug($originalName) . '-' . now()->format('YmdHis') . '.' . $extension;
                    $folder = 'logos/' . $this->company->id;
                    $this->company->{$photo->name} = $this->{"photo$i"}->storeAs($folder, $filename, 'public');
                }
            }




            $this->company->save();
            $this->reloadCompany();
            $this->emitSelf('refreshlist');
            $this->dispatchBrowserEvent('hideModal');
            $this->emitUp('refresh_table_company');
        }
    }

    public function logoPreviewUrl(int $index): string
    {
        $photo = $this->{"photo$index"} ?? null;

        if ($photo) {
            return $photo->temporaryUrl();
        }

        $column = $this->title_img($index)->name;
        $path = $this->company?->{$column};

        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return asset('img/edp-img/edp-avatar.jpg');
    }

    public function permissionInputKey(string $permissionKey): string
    {
        return str_replace('.', '__', $permissionKey);
    }


    public function render()
    {
        return view('livewire.admin.company.action.update', [
            'partnerPermissionCatalog' => PartnerPermissionCatalog::groups(),
            'services_l' => Service::query()
                ->when($this->contractService && !$this->contractConstruction, fn ($q) => $q->where('project', true))
                ->when($this->contractConstruction && !$this->contractService, fn ($q) => $q->where('construction', true))
                ->when($this->contractActivitySearch, fn ($q, $search) => $q->where('service', 'like', '%' . $search . '%'))
                ->orderBy('service')
                ->get(),
            'branchCandidates' => Company::query()
                ->whereNull('parent_id')
                ->when($this->company?->id, fn ($q) => $q->where('id', '!=', $this->company->id))
                ->when($this->branchSearch, fn ($q, $search) => $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                }))
                ->orderBy('name')
                ->limit(15)
                ->get(),
        ]);
    }

    private function syncContractServices(Contract $contract): void
    {
        $payload = [];

        foreach ($this->contractSelectedServices as $serviceId) {
            $payload[(int) $serviceId] = [
                'posts'    => false,
                'qtd'      => 0,
                'days'     => 0,
                'dispatch' => (bool) ($this->contractServiceDispatch[$serviceId] ?? false),
            ];
        }

        $contract->services()->sync($payload);
    }

    private function resetContractForm(): void
    {
        $this->contractForm = null;
        $this->contractNumber = '';
        $this->contractDateEnd = '';
        $this->contractService = false;
        $this->contractConstruction = false;
        $this->contractSelectedServices = [];
        $this->contractServiceDispatch = [];
        $this->contractActivitySearch = '';
        $this->showContractForm = false;
    }

    private function reloadCompany(): void
    {
        if (!$this->company) {
            return;
        }

        $this->company = Company::with('parent', 'branches.Address', 'branches.contracts.services', 'Address', 'Centerjobs', 'contracts.services')->find($this->company->id);
        $this->addresses = $this->company?->Address ?? [];
        $this->loadPartnerGrantPermissions();
    }

    private function resetBranchForm(): void
    {
        $this->branchName = '';
        $this->branchEmail = '';
        $this->branchTelephone = '';
        $this->showBranchForm = false;
        $this->branchSearch = '';
        $this->branchAttachId = '';
    }

    private function loadPartnerGrantPermissions(): void
    {
        $root = $this->partnerPermissionCompany();

        if (!$root) {
            $this->partnerGrantConfigured = false;
            $this->partnerGrantPermissions = [];

            return;
        }

        $grants = PartnerCompanyPermissionGrant::query()
            ->where('company_id', $root->id)
            ->get()
            ->keyBy('permission_key');

        $this->partnerGrantConfigured = $grants->isNotEmpty();
        $this->partnerGrantPermissions = [];

        foreach (PartnerPermissionCatalog::allPermissionKeys() as $permissionKey) {
            $this->partnerGrantPermissions[$this->permissionInputKey($permissionKey)] = $this->partnerGrantConfigured
                ? (bool) ($grants->get($permissionKey)?->enabled ?? false)
                : true;
        }
    }

    private function savePartnerPermissionGrants(): void
    {
        $root = $this->partnerPermissionCompany();

        if (!$root) {
            return;
        }

        foreach (PartnerPermissionCatalog::allPermissionKeys() as $permissionKey) {
            PartnerCompanyPermissionGrant::query()->updateOrCreate(
                [
                    'company_id' => $root->id,
                    'permission_key' => $permissionKey,
                ],
                [
                    'scope_type' => array_key_exists($permissionKey, PartnerPermissionCatalog::groups()) ? 'group' : 'item',
                    'enabled' => (bool) ($this->partnerGrantPermissions[$this->permissionInputKey($permissionKey)] ?? false),
                ]
            );
        }

        $this->partnerGrantConfigured = true;
    }

    private function partnerPermissionCompany(): ?Company
    {
        if (!$this->company) {
            return null;
        }

        return $this->company->parent ?: $this->company;
    }
}
