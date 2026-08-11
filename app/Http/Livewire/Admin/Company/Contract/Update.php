<?php

namespace App\Http\Livewire\Admin\Company\Contract;

use App\Models\{Contract, Service};
use Livewire\Component;

class Update extends Component
{
    public $company;

    public $number;

    public $date_end;

    public $construction;

    public $service;

    public $show_update = false;

    public $contract_update;

    public $selectedServices = [];

    public $serviceDispatch = [];

    public $activitySearch = '';

    protected $listeners = [
        'open_contract_update' => 'open_update',
        'save_update_contract' => 'update',
    ];

    public function open_update(Contract $contract)
    {

        $this->contract_update = $contract->load('company', 'services');

        $this->company      = $this->contract_update->company->name;
        $this->number       = $this->contract_update->number;
        $this->date_end     = $this->contract_update->date_end;
        $this->construction = $this->contract_update->construction;
        $this->service      = $this->contract_update->service;
        $this->selectedServices = $this->contract_update->services
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
        $this->serviceDispatch = $this->contract_update->services
            ->mapWithKeys(fn ($service) => [(string) $service->id => (bool) $service->pivot->dispatch])
            ->all();
        $this->show_update  = true;

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'update_modal',
        ]);

    }

    public function update()
    {
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

        $chk = $this->contract_update->update([
            'number'       => $this->number,
            'service'      => $this->service ? true : false,
            'construction' => $this->construction ? true : false,
            'date_end'     => date('Y-m-d', strtotime($this->date_end)),
        ]);

        if ($chk) {
            $this->syncServices();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => "Contrato Atualizado com Sucesso para {$this->contract_update->company->name}",
                'timer'    => 2500,
            ]);

            $this->emit('refresh_table_contract');

            $this->clean_all();
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ooops! Ocorreu um erro a tentar atualizar o contrato.',
                'timer'    => 2500,
            ]);
        }
    }

    public function clean_all()
    {
        $this->company      = '';
        $this->number       = '';
        $this->date_end     = '';
        $this->construction = false;
        $this->service      = false;
        $this->selectedServices = [];
        $this->serviceDispatch  = [];
        $this->activitySearch   = '';
        $this->show_update      = false;

        $this->dispatchBrowserEvent('hideModal');
    }

    private function syncServices(): void
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

        $this->contract_update->services()->sync($payload);
    }

    public function render()
    {
        return view('livewire.admin.company.contract.update', [
            'services_l' => Service::query()
                ->when($this->service && !$this->construction, fn ($q) => $q->where('project', true))
                ->when($this->construction && !$this->service, fn ($q) => $q->where('construction', true))
                ->when($this->activitySearch, fn ($q, $search) => $q->where('service', 'like', '%' . $search . '%'))
                ->orderBy('service')
                ->get(),
        ]);
    }
}
