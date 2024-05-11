<?php

namespace App\Http\Livewire\Construction\Hiring\Actions;

use App\Models\Company;
use App\Models\HiringWaiting;
use App\Models\Order;
use App\Models\User;
use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Waitinghiring extends Component
{
    public $orders;
    public $orderSelected = [];
    public $selectAllorder;

    public $company;
    public $user;
    public $hiring = false;


    protected $listeners = [
        'getOrders',
        'conf_viability' => 'confirm_viability'
    ];

    public function cancelarViab()
    {
        $this->orders = '';
        $this->orderSelected = [];
        $this->selectAllorder = false;
        $this->company = "";
        $this->user = "";
        $this->hiring = false;

        $this->emitUp('cleanAll');

    }

    public function getOrders($orders_id)
    {
        $this->orders = Order::whereIn('id', $orders_id)
                    ->with(['Operations' => function ($q) {
                        $q->where('operacao', '0010');
                    }])
                    ->orderBy('note_id')
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

        $company = Company::find($this->company)->name;
        $user = User::find($this->user)->name;
        $count = $this->orders->whereIn('id', $this->orderSelected)->count();

        $this->dispatchBrowserEvent('alertar', [
            'title'         => "ENVIAR VIABILIDADE",
            'msg'           => "
                <p>Deseja enviar <span class='fw-bold'>{$count}</span> obra(s) para <span class='fw-bold'>{$company}</span>?</p>
                <div class='card'>
                    <div class='card-body text-left'>
                        <p class='fw-bold'>Responsável:<span class='fw-normal'> {$user}</span></p>
                    </div>
                </div>
            ",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Envie!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'conf_viability',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Ordem foi Enviada!',

        ]);

        return;
    }

    public function confirm_viability()
    {
        if (empty($this->orderSelected) || !$this->orders) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'SEM FONTE DE ORIGEM',
                'html'     => 'Por algum motivo, os dados NÃO EXISTEM ou FORAM perdidos. Verifique novamente os dados e tente novamente.',
                'timer'    => 10000,
            ]);

            return;
        }

        DB::beginTransaction();

        $error = false;

        foreach ($this->orders->whereIn('id', $this->orderSelected) as $order) {

            $ifExistingViab = Viability::where('order_id', $order->id)->count();


            if (!$ifExistingViab) {

                try {

                    $viability = Viability::Create([
                        'order_id'    => $order->id,
                        'company_id'  => $this->company,
                        'user_id'     => Auth()->User()->id,
                        'engineer_id' => $this->user,
                        'sended_at'   => date('Y-m-d H:i:s'),
                        'hired'       => $this->hiring ? true : false,
                        'hired_at'    => $this->hiring ? date('Y-m-d H:i:s') : null,
                        'status'      => 1,
                    ]);

                } catch (\Throwable $th) {
                    $error = true;
                }

            }
        }

        $waitinglistRegs = $this->orders->whereIn('id', $this->orderSelected)->pluck('note_id')->toArray();

        $completeWaitingList = HiringWaiting::WhereIn('note_id', $waitinglistRegs)
                                        ->update([
                                            'complete' => true
                                        ]);

        if ($error) {

            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO ENVIAR VIABILIDADE',
                'html'     => 'Encontramos problemas ao tentar registrar a viabilidade. Verifique os dados e tente novamente.',
                'timer'    => 10000,
            ]);

            return;
        } else {

            DB::commit();

            $this->cancelarViab();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'VIABILIDAE ENVIADA',
                'timer'    => 2500,
            ]);

            return;
        }
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
