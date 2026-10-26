<?php

namespace App\Http\Livewire\Admin\Company;

use App\Models\{Andresscompany, Company};
use App\Services\Admin\Company\CompanyBulkSchema;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Create extends Component
{
    protected $listeners = [
        'save_create_company' => 'save',
    ];

    public $name = '';

    public $email = '';

    public $street = '';

    public $complement = '';

    public $uf = '';

    public $city = '';

    public $telephone = '';

    public $isBranch = false;

    public $parent_id = '';

    protected function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'min:2', 'max:255', 'not_regex:/[\/]/'],
            'email'      => ['required', 'email:rfc', 'max:255'],
            'telephone'  => ['nullable', 'string', 'regex:/^[\d\s().+\-]{8,20}$/'],
            'street'     => ['nullable', 'string', 'max:255'],
            'complement' => ['nullable', 'string', 'max:255'],
            'city'       => ['nullable', 'string', 'max:255'],
            'uf'         => ['nullable', 'in:' . implode(',', CompanyBulkSchema::UFS)],
            'parent_id'  => [$this->isBranch ? 'required' : 'nullable', 'exists:companies,id'],
        ];
    }

    protected $messages = [
        'name.required'      => 'Informe o nome da empresa.',
        'name.min'           => 'O nome precisa de ao menos 2 caracteres.',
        'name.not_regex'     => 'O nome não pode conter "/".',
        'email.required'     => 'Informe o e-mail.',
        'email.email'        => 'Informe um e-mail válido (nome@dominio.com).',
        'telephone.regex'    => 'Telefone inválido. Use DDD + número, ex.: (27) 99999-9999.',
        'uf.in'              => 'Escolha uma UF da lista.',
        'parent_id.required' => 'Escolha a empresa concentradora.',
        'parent_id.exists'   => 'Empresa concentradora inválida.',
    ];

    public function updated($property): void
    {
        if ($property === 'isBranch' && !$this->isBranch) {
            $this->parent_id = '';
        }

        if (in_array($property, ['name', 'email', 'telephone', 'uf', 'parent_id'], true)) {
            $this->validateOnly($property);
        }
    }

    public function save(): void
    {
        $this->name  = trim(preg_replace('/\s+/', ' ', (string) $this->name));
        $this->email = mb_strtolower(trim((string) $this->email));
        $this->uf    = strtoupper(trim((string) $this->uf));

        $this->validate();

        $normalized = CompanyBulkSchema::norm($this->name);
        $duplicate  = Company::withTrashed()
            ->where('parent_id', $this->isBranch ? $this->parent_id : null)
            ->get(['name'])
            ->contains(fn ($c) => CompanyBulkSchema::norm($c->name) === $normalized);

        if ($duplicate) {
            $this->addError('name', $this->isBranch ? 'Esta concentradora já possui uma unidade com este nome.' : 'Já existe uma empresa com este nome.');

            return;
        }

        DB::transaction(function () {
            $company = Company::create([
                'parent_id' => $this->isBranch ? $this->parent_id : null,
                'email'     => $this->email,
                'name'      => ucwords(mb_strtolower($this->name)),
                'telephone' => $this->telephone ?: null,
            ]);

            if (trim($this->street . $this->city . $this->uf . $this->complement) !== '') {
                $company->Address()->save(new Andresscompany([
                    'street'     => ucwords(mb_strtolower((string) $this->street)),
                    'city'       => ucwords(mb_strtolower((string) $this->city)),
                    'uf'         => $this->uf,
                    'complement' => $this->complement,
                ]));
            }
        });

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Empresa cadastrada com sucesso',
            'timer'    => 2500,
        ]);

        $this->emit('refresh_table_company');
        $this->clean_all();
    }

    public function clean_all(): void
    {
        $this->reset(['name', 'email', 'street', 'complement', 'uf', 'city', 'telephone', 'isBranch', 'parent_id']);
        $this->resetValidation();
        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.admin.company.create', [
            'parentCompanies' => Company::roots()->orderBy('name')->get(['id', 'name']),
            'ufs'             => CompanyBulkSchema::UFS,
        ]);
    }
}
