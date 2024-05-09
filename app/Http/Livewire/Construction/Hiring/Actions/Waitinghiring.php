<?php

namespace App\Http\Livewire\Construction\Hiring\Actions;

use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use Livewire\Component;

class Waitinghiring extends Component
{
    public $orders;
    public $orderSelected = [];
    public $selectAllorder;

    public $company;
    public $user;


    protected $listeners = [
        'getOrders'
    ];

    public function cancelarViab()
    {
        $this->orders = '';
        $this->orderSelected = [];
        $this->selectAllorder = false;
        $this->company = "";
        $this->user = "";

        $this->emitUp('cleanAll');

    }

    public function getOrders($orders_id)
    {
        $this->orders = Order::whereIn('id', $orders_id)
                    ->with(['Operations' => function ($q) {
                        $q->where('operacao', '0010');
                    }])
                    ->with('Note.Files')
                    ->get();


        if ($this->orders) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => "modal_toviability",
            ]);
        }
    }

    public function goViability()
    {
        if (!$this->user || !$this->company) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'SEM ORIENTAÇÃO DE DESTINO',
                'html'     => 'É obrigatório a indicação da empreiteira e o responsável pela obra antes de enviar para viabilidade.',

            ]);

            return;
        }

        if (empty($this->orderSelected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ORDEM(NS) NAO SELECIONADA(S)',
                'html'     => 'Não foi(ram) selecionada(s), nenhuma ordem(ns) para envio a Vaibilidade.',
                'timer'    => 10000,
            ]);

            return;
        }

        //Confirma se realmente todos as Notas possuem arquivos.
        foreach ($this->orders->whereIn('id', $this->orderSelected) as $order) {
            if (!$order->Note->Files->isNotEmpty()) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'SEM ARQUIVOS',
                    'html'     => 'Existem NOTAS/OVs sem arquivo sendo enviado. Gentileza verificar e tentar novamente.',
                    'timer'    => 10000,
                ]);

                return;
            }
        }



        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'PARTIU!',

            'timer'    => 2500,
        ]);
    }

    public function getCompaniesProperty()
    {
        return Company::select('id', 'name')->orderBy('name')->get();
    }

    public function getUsersProperty()
    {
        return User::where('engineer', true)->select('id', 'name')->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.construction.hiring.actions.waitinghiring', [
            'companies' => $this->companies,
            'users' => $this->users
        ]);
    }
}
