<?php

namespace App\Http\Livewire\Services\Oexterno\Actions;

use App\Models\External;
use Livewire\Component;

class AddComments extends Component
{
    public $observations;
    public $title;
    public $serviceId;

    public $external;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'openAddComment',
        'confirm_add_new_message' => 'confirm_add_new_message',
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
                   'title'    => 'COMENTÁRIO ADICIONADO',
                   'html'      => 'COMENTÁRIO ADICIONADO COM SUCESSO.',
                   'timer'    => 5000,
               ]);

        $this->closeAll();
    }


    public function savedFiles()
    {
        $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'COMENTÁRIO ADICIONADO',
                    'html'      => 'COMENTÁRIO ADICIONADO E EVIDÊNCIAS SALVAS COM SUCESSO.',
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

    public function openAddComment(External $external)
    {
        $this->external = $external;

        if ($this->external) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modalAddComment',
            ]);
        }
    }

    public function saveComment()
    {
        if (!trim($this->title) || !trim($this->observations)) {
            $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Titulo e Observações são obrigatórias.',
                    'html'      => 'NENHUMA ENTIDADE PROTOCOLAR FOI SELECIONADA.',
                    'timer'    => 5000,
                ]);

            return;
        }

        $this->dispatchBrowserEvent('alertar', [
              'title'         => "Entrada de Protocolo",
              'msg'           => "Você deseja adicionar novo comentário para esta entidade?",
              'icon'          => 'warning',
              'btnOktxt'      => 'Sim, Adicionar!',
              'btnCanceltxt'  => 'Não, Cancelar',
              'action'        => 'confirm_add_new_message',
              'cancel_titulo' => 'Cancelado!',
              'cancel_msg'    => 'Nenhuma NOTA/OV foi encerrada!',

          ]);
    }

    public function confirm_add_new_message()
    {
        $this->external->update([
            'status' => $this->title,
        ]);

        $this->external->Comments()->create([
            'user_id' => auth()->user()->id,
            'title' => $this->title,
            'comment' => $this->observations,
        ]);

        $this->emitTo('files.manager.generic-file-uploader', 'saveFiles');
    }

    public function closeAll()
    {
        $this->reset(['title', 'observations']);
        $this->dispatchBrowserEvent('hideModal');
        $this->emitUp('refreshComponent');
    }

    public function render()
    {
        return view('livewire.services.oexterno.actions.add-comments');
    }
}
