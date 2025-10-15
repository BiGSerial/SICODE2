<?php

namespace App\Http\Livewire\Services\Oexterno\Actions;

use App\Models\External;
use Livewire\Component;

class AddProtocol extends Component
{
    public $protocol;
    public $observations;
    public $title;
    public $serviceId;
    public $external;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'openAddProtocol',
        'confirm_add_new_protocol' => 'confirm_add_new_protocol',
        'continue',
        'ErrorSaveFiles',
        'savedFiles',
    ];

    public function mount()
    {

        $this->serviceId = request()->route('service');


    }

    public function continue()
    {
        $this->dispatchBrowserEvent('swal', [
                   'position' => 'center',
                   'icon'     => 'success',
                   'title'    => 'Entrada de Protocolar',
                   'html'      => 'ENTRADA DE PROTOCOLAR REALIZADA COM SUCESSO.',
                   'timer'    => 5000,
               ]);

        $this->closeAll();
    }


    public function savedFiles()
    {
        $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Entrada de Entidade Protocolar',
                    'html'      => 'ENTRADA PROTOCOLAR E EVIDÊNCIAS FORAM SALVAS COM SUCESSO.',
                    'timer'    => 5000,
                ]);

        $this->closeAll();

    }

    public function ErrorSaveFiles()
    {
        $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'ERRO AO SALVAR EVIDÊNCIAS',
                    'html'      => 'ENCONTRAMOS ERRO AO TENTAR SALVAR AS EVIDÊNCIAS, NENHUMA EVIDÊNCIA FOI SALVA. ADICIONE AS EVIDÊNCIAS EM UM NOVO COMENTÁRIO',

                ]);

        $this->closeAll();

    }

    public function openAddProtocol(External $external)
    {
        $this->external = $external;

        if ($this->external) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modalAddProtocol',
            ]);
        }
    }

    public function saveProtocol()
    {
        if (!trim($this->protocol) || !trim($this->observations)) {
            $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Protocolo e Obsrvações são obrigatórias.',
                    'html'      => 'NENHUMA ENTIDADE PROTOCOLAR FOI SELECIONADA.',
                    'timer'    => 5000,
                ]);

            return;
        }

        $this->dispatchBrowserEvent('alertar', [
                'title'         => "Entrada de Protocolo",
                'msg'           => "Você deseja adicionar a novo protocolo {$this->protocol}, para o nova entidade entidade?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Adicionar!',
                'btnCanceltxt'  => 'Não, Cancelar',
                'action'        => 'confirm_add_new_protocol',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma NOTA/OV foi encerrada!',
        ]);
    }

    public function confirm_add_new_protocol()
    {
        // $this->external->status = $this->title;
        $this->external->save();


        $this->external->Protocols()->updateOrCreate(['protocol' => $this->protocol], [
            'protocol' => $this->protocol,
            'description' => $this->observations,
        ]);

        if ($this->title) {
            $this->external->Comments()->create([
                'external_id' => $this->external->id,
                'user_id'     => auth()->user()->id,
                'title'       => $this->title,
                'comment'     => $this->observations,
            ]);
        }

        $this->emitTo('files.manager.generic-file-uploader', 'saveFiles');
    }

    public function closeAll()
    {
        $this->reset(['protocol', 'observations', 'title']);
        $this->dispatchBrowserEvent('hideModal');
        $this->emitUp('refreshComponent');
    }

    public function render()
    {
        return view('livewire.services.oexterno.actions.add-protocol');
    }
}
