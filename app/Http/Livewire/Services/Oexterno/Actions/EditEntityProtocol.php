<?php

namespace App\Http\Livewire\Services\Oexterno\Actions;

use App\Models\Entity;
use App\Models\EntityType;
use App\Models\External;
use Livewire\Component;

class EditEntityProtocol extends Component
{
    public $external;
    public $search;
    public $selectedType;
    public $selectedEntity;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'openEdityEntityProtocol',
        'confirm_new_protocol' => 'confirm_new_protocol',
    ];

    protected $rules = [
        'external' => 'nullable',
        'external.entity_id' => 'required|integer',
    ];

    public function openEdityEntityProtocol(External $external)
    {

        $this->external = $external;

        if ($this->external) {
            $this->dispatchBrowserEvent('showModal', [
            'id' => 'modalEditEntityProtocol',
            ]);
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

    public function saveEdit()
    {
        
        if ($this->external) {
            $this->external->entidade = Entity::find($this->external->entity_id)->nick;
            $this->external->save();
        }

        $this->closeAll();

    }

    public function closeAll()
    {
        $this->dispatchBrowserEvent('hideModal');
        $this->external = null;
        $this->emitUp('refreshComponent');
    }


    public function render()
    {
        return view('livewire.services.oexterno.actions.edit-entity-protocol', [
            'entities' => $this->entities,
            'entityTypes' => $this->entityTypes,
        ]);
    }
}
