<?php

namespace App\Http\Livewire\Config\Services;

use App\Models\{Company, Contract, Service};
use Livewire\Component;

class Addrules extends Component
{
    public $showAddRules = false;

    public $service;

    public $companies;

    public $contracts;

    public $company_s;

    public $contract_s;

    public $posts = false;

    public $qtd;

    public $days;

    public $dispatch;

    protected $listeners = [
        'open_add_rules' => 'open_add_rules',
        'save_add_rules' => 'add_rules',
    ];

    public function open_add_rules(Service $service)
    {
        $this->service = $service;

        $this->showAddRules = true;

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'add_rules_modal',
        ]);
    }

    public function add_rules()
    {
        $contract = Contract::find($this->contract_s);

        if ($this->service->contracts()->where('contract_id', $this->contract_s)->exists()) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'warning',
                'menssage' => 'Regra ja existente para esse contrato neste serviço.',
            ]);

            $this->clean_all();

            return;
        }

        try {
            $this->service->contracts()->attach($contract, [
                'posts'    => $this->posts ? true : false,
                'qtd'      => (int) $this->qtd < 0 ? (int) $this->qtd * -1 : (int) $this->qtd,
                'days'     => (int) $this->days < 0 ? (int) $this->days * -1 : (int) $this->days,
                'dispatch' => $this->dispatch ? true : false,
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Regra Adicionada com com Sucesso!',
                'timer'    => 2500,
            ]);

            $this->clean_all();

        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Oooops! ocorreu algum erro ao tentar adicionar a regra!',
                'timer'    => 2500,
            ]);

            if (env('APP_DEBUG')) {
                $this->dispatchBrowserEvent('torrada', [
                    'status'   => 'warning',
                    'menssage' => $th->getMessage(),
                ]);
            }
        }
    }

    public function clean_all()
    {
        $this->showAddRules = false;
        $this->service      = '';
        $this->companies    = '';
        $this->contracts    = '';
        $this->company_s    = '';
        $this->contract_s   = '';

        $this->posts = false;
        $this->qtd   = 0;
        $this->days  = 0;

        $this->emit('refresh_service_list');

        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        $this->companies = Company::has('contracts')->orderBy('name')->get();
        $this->contracts = Contract::Where('company_id', $this->company_s)->orderBy('number')->get();

        return view('livewire.config.services.addrules');
    }
}
