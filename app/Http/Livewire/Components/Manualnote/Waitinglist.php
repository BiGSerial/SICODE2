<?php

namespace App\Http\Livewire\Components\Manualnote;

use App\Models\{Manualnote, Service};
use Livewire\{Component, WithPagination};

class Waitinglist extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $service;

    public $production;

    protected $listeners = [
        'refresh_waitinglist'         => '$refresh',
        'confirm_encerramento_manual' => 'go_confirm',
        'confirm_cencelamento_manual' => 'go_cancel',

    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();
    }

    public function to_confirm($id)
    {
        $this->production = Manualnote::find($id);

        if ($this->production) {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'ENCERRAMENTO MANUAL',
                'msg'   => "
                Você deseja encerrar a NOTA/OV {$this->production->note}?</br></br>
                <div class='card card-light'>
                <div class='card-body'>
                <p>O encerramento da NOTA de entrada manual permanecerá até a próxima onda de atualização. 
                Quando o sistema identificar a entrada da nota, a mesma será acrescentada na sua 
                produção automaticamente.</p>
                <strong class='text-center'>DESEJA CONTINUAR O ENCERRAMENTO DA NOTA?</strong>
                </div>
                </div>  
                ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Encerre!',
                'btnCanceltxt'  => 'Não, Cancele!',
                'action'        => 'confirm_encerramento_manual',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nota foi confirmada.',

            ]);
        }
    }

    public function go_confirm()
    {
        $chk = $this->production->update([
            'completed' => true,
            'finish_at' => date('Y-m-d H:i:s'),
        ]);

        if ($chk) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'ENCERRAMENTO',
                'html'     => 'NOTA ENCERRADA COM SUCESSO!',
            ]);

            $this->clean();
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ENCERRAMENTO',
                'html'     => 'OOOPS! Por algum motivo ocorreu um erro.',
            ]);
        }
    }

    public function to_cancel($id)
    {
        $this->production = Manualnote::find($id);

        if ($this->production) {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'CANCELAMENTO MANUAL',
                'msg'   => "
                Você deseja marcar para cancelar a NOTA/OV {$this->production->note}?</br></br>
                <div class='card card-light'>
                <div class='card-body'>
                <p>Ao Marcar para cancelamento, o sistema se removerá na próxima ONDA de atualização. Lembrando que a nota continuará registrada para auditoria.</p>
                <strong class='text-center'>DESEJA CONTINUAR O CANCELAMENTO DA NOTA?</strong>
                </div>
                </div>  
                ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Cancele!',
                'btnCanceltxt'  => 'Não!',
                'action'        => 'confirm_cencelamento_manual',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nota foi marcada para cancelamento.',

            ]);
        }
    }

    public function go_cancel()
    {
        $chk = $this->production->update([
            'cancel' => true,
        ]);

        if ($chk) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'CANCELAMENTO',
                'html'     => 'NOTA COLOCADA PARA CANCELAMENTO!',
            ]);

            $this->clean();
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'CANCELAMENTO',
                'html'     => 'OOOPS! Por algum motivo ocorreu um erro.',
            ]);
        }

    }

    public function getWaitinglistProperty()
    {
        return Manualnote::where('service_id', $this->service->uuid)
            ->where('user_id', Auth()->User()->id)
            ->where('confirmed', false)
            ->with('Service')
            ->paginate($this->perPage);
    }

    public function clean()
    {
        $this->emit('refresh_waitinglist');
        $this->production = '';
    }

    public function render()
    {
        return view('livewire.components.manualnote.waitinglist', [
            'lists' => $this->waitinglist,
        ]);
    }
}
