<?php

namespace App\Http\Livewire\Admin\Company\Contract;

use App\Models\Contract;
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

    protected $listeners = [
        'open_contract_update' => 'open_update',
        'save_update_contract' => 'update',
    ];

    public function open_update(Contract $contract)
    {

        $this->contract_update = $contract->load('company');

        $this->company      = $this->contract_update->company->name;
        $this->number       = $this->contract_update->number;
        $this->date_end     = $this->contract_update->date_end;
        $this->construction = $this->contract_update->construction;
        $this->service      = $this->contract_update->service;
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

        $chk = $this->contract_update->update([
            'number'       => $this->number,
            'service'      => $this->service ? true : false,
            'construction' => $this->construction ? true : false,
            'date_end'     => date('Y-m-d', strtotime($this->date_end)),
        ]);

        if ($chk) {
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

        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.admin.company.contract.update');
    }
}
