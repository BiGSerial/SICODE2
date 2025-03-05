<?php

namespace App\Http\Livewire\Admin\Company\Action;

use App\Models\Andresscompany;
use App\Models\Centerjob;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;
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

    protected $listeners = [
        'refreshlist' => '$refresh',
        'openModal',
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

        $this->company = $company;
        $this->addresses = $company->Address;

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
                $this->emitSelf('refreshlist');
            }
        }


    }

    public function removeCenterjob(Centerjob $centerjob)
    {
        if ($centerjob) {
            $centerjob->delete();
            $this->emitSelf('refreshlist');
        }
    }

    public function save()
    {


        if ($this->company) {

            for ($i = 0; $i < 4; $i++) {
                $photo = $this->title_img($i);

                if ($this->{"photo$i"}) {

                    if ($this->company->{$photo->name} && Storage::disk('public')->exists($this->company->{$photo->name})) {
                        Storage::disk('public')->delete($this->company->{$photo->name});
                    }

                    $filename = $this->{"photo$i"}->getClientOriginalName();
                    $folder = 'logos/' . $this->company->id;
                    $this->company->{$photo->name} = $this->{"photo$i"}->storeAs($folder, $filename, 'public');
                }
            }




            $this->company->save();
            $this->emitSelf('refreshlist');
            $this->dispatchBrowserEvent('hideModal');
            $this->emitUp('refresh_table_company');
        }
    }


    public function render()
    {
        return view('livewire.admin.company.action.update');
    }
}
