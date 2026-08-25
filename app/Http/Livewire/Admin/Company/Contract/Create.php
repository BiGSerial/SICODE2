<?php

namespace App\Http\Livewire\Admin\Company\Contract;

use App\Models\{Company, Contract, Service};
use Livewire\Component;

class Create extends Component
{
    public $company_s;

    public $number;

    public $date_end;

    public $construction;

    public $service;

    public $selectedServices = [];

    public $serviceDispatch = [];

    public $activitySearch = '';

    protected $listeners = [
        'save_create_contract' => 'save',
    ];

    public function save()
    {
        if (!$this->company_s) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Favor selecionar uma empresa para adicionar o contrato',
                'timer'    => 2500,
            ]);

            return;
        }

        if (!trim($this->number)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Entrar com o número do contrato',
                'timer'    => 2500,
            ]);

            return;
        }

        if (!trim($this->date_end)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Informar a Validade do contrato.',
                'timer'    => 2500,
            ]);

            return;
        }

        if (($this->construction + $this->service) == 0) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Selecione um tipo de contrato.',
                'timer'    => 2500,
            ]);

            return;
        }

        if (!count($this->selectedServices)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Selecione ao menos uma atividade liberada para o contrato.',
                'timer'    => 2500,
            ]);

            return;
        }

        $company = Company::find($this->company_s);

        if ($company) {
            $contract = new Contract([
                'number'       => $this->number,
                'service'      => $this->service ? true : false,
                'construction' => $this->construction ? true : false,
                'date_end'     => date('Y-m-d', strtotime($this->date_end)),
            ]);

            if ($company->contracts()->save($contract)) {
                $this->syncServices($contract);

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => "Contrato Adicionad com Sucesso para {$company->name}",
                    'timer'    => 2500,
                ]);

                $this->emit('refresh_table_contract');

                $this->clean_all();
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'Ooops! Ocorreu um erro a tentar criar o contrato.',
                    'timer'    => 2500,
                ]);
            }
        }
    }

    public function clean_all()
    {
        $this->company_s    = '';
        $this->number       = '';
        $this->date_end     = '';
        $this->construction = false;
        $this->service      = false;
        $this->selectedServices = [];
        $this->serviceDispatch  = [];
        $this->activitySearch   = '';

        $this->dispatchBrowserEvent('hideModal');
    }

    private function syncServices(Contract $contract): void
    {
        $payload = [];

        foreach ($this->selectedServices as $serviceId) {
            $payload[(int) $serviceId] = [
                'posts'    => false,
                'qtd'      => 0,
                'days'     => 0,
                'dispatch' => (bool) ($this->serviceDispatch[$serviceId] ?? false),
            ];
        }

        $contract->services()->sync($payload);
    }

    public function render()
    {
        return view('livewire.admin.company.contract.create', [
            'companies_l' => Company::orderBy('name')->get(),
            'services_l'  => Service::query()
                ->when($this->service && !$this->construction, fn ($q) => $q->where('project', true))
                ->when($this->construction && !$this->service, fn ($q) => $q->where('construction', true))
                ->when($this->activitySearch, fn ($q, $search) => $q->where('service', 'like', '%' . $search . '%'))
                ->orderBy('service')
                ->get(),
        ]);
    }
}
