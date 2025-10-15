<?php

namespace App\Http\Livewire\Services\Oexterno\Actions;

use App\Models\Entity;
use App\Models\EntityType;
use App\Models\External;
use App\Models\Note;
use Livewire\Component;

class AddEntityProtocol extends Component
{
    public ?Note $note = null;
    public $search;
    public $selectedType;
    public $selectedEntity;
    public $protocol;
    public $observations;
    public $title;
    public $serviceId;


    public ?External $external = null;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'openEntityProtocol',
        'confirm_entity_protocol' => 'confirm_new_protocol',
        'continue',
        'ErrorSaveFiles',
        'savedFiles',
    ];

    protected $rules = [
        'external' => 'nullable',
        'external.entity_id' => 'required|integer',
        'protocol' => 'required|string|max:255',
        'observations' => 'required|string|max:65535',
        'title' => 'required|string|max:255',
    ];

    protected $messages = [
        'external.entity_id.required' => 'Selecione uma entidade.',
        'external.entity_id.integer' => 'Selecione uma entidade.',
        'protocol.required' => 'Protocolo é obrigatório.',
        'protocol.max' => 'Protocolo deve ter no máximo 255 caracteres.',
        'observations.required' => 'Observações são obrigatórias.',
        'title.required' => 'Título é obrigatório.',
    ];

    public function continue()
    {
        $this->dispatchBrowserEvent('swal', [
                   'position' => 'center',
                   'icon'     => 'success',
                   'title'    => 'Entrada de Entidade Protocolar',
                   'html'      => 'ENTRADA DE ENTIDADE PROTOCOLAR REALIZADA COM SUCESSO.',
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

    public function mount(Note $note)
    {
        $this->selectedType = null;
        $this->search = null;
        $this->selectedEntity = null;
        $this->serviceId = request()->route('service');


        if ($note) {
            $this->note = $note;
        }
    }



    public function openEntityProtocol()
    {
        $this->selectedType = null;
        $this->search = null;
        $this->selectedEntity = null;

        $this->external = new External();

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'modalEntityProtocol',
        ]);
    }

    public function saveEntity()
    {
        $this->validate();

        if (!trim($this->protocol) || !trim($this->title) || !trim($this->observations)) {
            $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Protocolo e Observações são obrigatórias.',
                    'html'      => 'NENHUMA ENTIDADE PROTOCOLAR FOI SELECIONADA.',
                    'timer'    => 5000,
                ]);

            return;
        }


        $this->dispatchBrowserEvent('alertar', [
               'title'         => "Entrada de Protocolo",
               'msg'           => "Você deseja adicionar a novo protocolo, para o nova entidade entidade?",
               'icon'          => 'warning',
               'btnOktxt'      => 'Sim, Adicionar!',
               'btnCanceltxt'  => 'Não, Cancelar',
               'action'        => 'confirm_entity_protocol',
               'cancel_titulo' => 'Cancelado!',
               'cancel_msg'    => 'Nenhuma NOTA/OV foi encerrada!',

           ]);



    }

    public function confirm_new_protocol()
    {

        $entidade =  Entity::find($this->external->entity_id)->nick;



        if ($this->external) {
            $this->external->note_id = $this->note->id;
            $this->external->user_id = auth()->user()->id;
            $this->external->entidade = $entidade;
            $this->external->status = $this->title;

            if ($this->external->save()) {


                if (trim($this->protocol)) {
                    $this->external->Protocols()->create([
                        'external_id' => $this->external->id,
                        'protocol'    => $this->protocol,
                        'description' => $this->observations,
                    ]);
                }

                if ($this->title) {
                    $this->external->Comments()->create([
                        'external_id' => $this->external->id,
                        'user_id'     => auth()->user()->id,
                        'title'       => $this->title,
                        'comment'     => $this->observations,
                    ]);
                }

                $this->emitTo(
                    'files.manager.generic-file-uploader',
                    'prepareFileUpload',
                    \App\Models\External::class,
                    $this->external->id
                );

                $this->emitTo('files.manager.generic-file-uploader', 'saveFiles');
            }
        }


    }

    public function getEntitiesProperty()
    {
        return Entity::when(trim($this->selectedType), function ($q) {
            $q->where('entity_type_id', $this->selectedType);
        })->when(trim($this->search), function ($q) {
            $q->where('name', 'like', '%'.trim($this->search).'%');
        })->orderBy('name')->get();
    }

    public function getEntityTypesProperty()
    {
        return EntityType::orderBy('name')->get();
    }


    public function closeAll()
    {
        $this->reset(['protocol', 'observations', 'title', 'search', 'selectedType', 'selectedEntity']);
        $this->dispatchBrowserEvent('hideModal');
        $this->emitUp('refreshComponent');
    }

    public function render()
    {
        return view('livewire.services.oexterno.actions.add-entity-protocol', [
            'entities' => $this->entities,
            'entityTypes' => $this->entityTypes,
        ]);
    }
}
