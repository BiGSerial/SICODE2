<?php

namespace App\Http\Livewire\Components\Transprod;

use App\Models\{Notify, Prodtransfer, Production, User};
use Livewire\Component;

class Transprodlev extends Component
{
    public $production;

    public $search;

    public $transfer_view = false;

    public $user_transfer_id;

    public $user_transfer_info;

    protected $listeners = [
        'transfer_production_lev' => 'transfer_production',
    ];

    public function transfer_production(Production $production)
    {
        $this->production = $production->load('User', 'Note', 'Service');

        if ($this->production) {
            $this->transfer_view = true;
        }

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'transfer_modal',
        ]);
    }

    public function getUserlistProperty()
    {
        return User::Where('user', true)
            ->When($this->search, function ($q, $s) {
                return $q->where('name', 'like', '%' . $s . '%');
            })
            ->orderBy('Name')
            ->get();
    }

    public function transfer_prod()
    {

        $url = route('services.accompany', ['service' => $this->production->service_id]);

        // Check existence user to transfer
        if (!$this->user_transfer_id) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'SEM USUÁRIO SELECIONADO PARA TRANSFERIR!.',
                'timer'    => 2500,
            ]);

            return;
        }

        if (!strlen(trim($this->user_transfer_info)) || strlen(trim($this->user_transfer_info)) <= 2) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'INFORMAÇÃO OBRIGATÓRIA.',
                'html'     => '<strong> (MOTIVO) </strong> A informação do motivo é obrigatório. Seja Claro e Objetivo.',
                'timer'    => 10000,
            ]);

            return;
        }

        try {
            $transfer = Prodtransfer::create([
                'production_id' => $this->production->id,
                'service_id'    => $this->production->service_id,
                'from'          => Auth()->User()->id,
                'to'            => $this->user_transfer_id,
                'info'          => $this->user_transfer_info,
                'read_from'     => true,
                'read_to'       => false,
                'status'        => 19,
            ]);

            $this->production->update([
                'block'     => true,
                'block_wpa' => true,
                'status'    => 19,
            ]);

            Notify::create([
                'user_id' => $transfer->to,
                'title'   => 'TRANSFERÊNCIA PRODUÇÃO',
                'info'    => 'O usuário ' . Auth()->User()->name . ' deseja transferir para você a nota/ov ' . $this->production->Note->note . ' em ' . $this->production->Service->service,
                'status'  => 4,
                'link'    => $url,
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Solicitação de Transferência Enviada com Sucesso.',
                'timer'    => 2500,
            ]);

            $this->close();

        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OOOPS, Algo de errado aconteceu....',
                'timer'    => 2500,
            ]);
        }
    }

    public function close()
    {

        $this->production         = null;
        $this->search             = '';
        $this->transfer_view      = false;
        $this->user_transfer_id   = null;
        $this->user_transfer_info = null;

        $this->dispatchBrowserEvent('hideModal');
        $this->emit('refresh_accomany');
    }

    public function render()
    {
        return view('livewire.components.transprod.transprodlev', [
            'user_list' => $this->Userlist,
        ]);
    }
}
