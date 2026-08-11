<?php

namespace App\Http\Livewire\Admin\Company\Action;

use App\Models\Andresscompany;
use App\Models\Centerjob;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Service;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

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

    protected $listeners = [
        'refreshlist' => '$refresh',
        'openModal',
        'confirm_remove_company_contract' => 'removeContractConfirmed',
    ];



    protected $rules = [
        'company.name' => 'required|string|max:255',
        'company.email' => 'required|email',
        'company.telephone' => 'required|string',
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

        $this->company = $company->load('Address', 'Centerjobs', 'contracts.services');
        $this->addresses = $this->company->Address;
        $this->photo0 = null;
        $this->photo1 = null;
        $this->photo2 = null;
        $this->photo3 = null;
        $this->resetContractForm();

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

    public function save()
    {
        $this->validate([
            'photo0' => 'nullable|image|max:2048',
            'photo1' => 'nullable|image|max:2048',
            'photo2' => 'nullable|image|max:2048',
            'photo3' => 'nullable|image|max:2048',
        ]);


        if ($this->company) {

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


    public function render()
    {
        return view('livewire.admin.company.action.update', [
            'services_l' => Service::query()
                ->when($this->contractService && !$this->contractConstruction, fn ($q) => $q->where('project', true))
                ->when($this->contractConstruction && !$this->contractService, fn ($q) => $q->where('construction', true))
                ->when($this->contractActivitySearch, fn ($q, $search) => $q->where('service', 'like', '%' . $search . '%'))
                ->orderBy('service')
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

        $this->company = Company::with('Address', 'Centerjobs', 'contracts.services')->find($this->company->id);
        $this->addresses = $this->company?->Address ?? [];
    }
}
