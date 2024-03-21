<?php

namespace App\Http\Livewire\Config\Services;

use App\Models\{Contract, Service};
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Removerules extends Component
{
    public $service;

    public $contract;

    public $action_id;

    protected $listeners = [
        'confirm_remove_rules' => 'remove',
    ];

    public function mount(Service $service, Contract $contract, $action_id)
    {
        $this->service   = $service;
        $this->contract  = $contract;
        $this->action_id = $action_id;
    }

    public function to_remove()
    {

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Remover Regra',
            'msg'           => "Você deseja remover <strong>{$this->contract->number}</strong> de <strong>{$this->service->service}</strong>?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Remova!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_remove_rules',
            'action_id'     => $this->action_id,
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

        ]);

    }

    public function remove($action_id)
    {

        // Block remove rule if component differnt of $action_id.
        if ($this->action_id !== $action_id) {
            return;
        }

        try {
            DB::table('service_contract_rules')
                ->where('service_id', $this->service->id)
                ->where('contract_id', $this->contract->id)
                ->delete();

            $this->clean_all();

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Regra removida com sucesso',
            ]);

        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'ERRO ao tentar remover a regra.',
            ]);
        }
    }

    public function clean_all()
    {
        $this->contract = '';

        $this->emit('refresh_service_list');

        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.config.services.removerules');
    }
}
