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
    public ?External $external = null;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'openEntityProtocol',
    ];

    protected $rules = [
        'external' => 'nullable',
        'external.entity_id' => 'required|integer',
        'external.entidade' => 'string|max:255',
        'external.status' => 'integer',
        'external.completed' => 'boolean',
    ];

    public function mount(Note $note)
    {
        $this->selectedType = null;
        $this->search = null;
        $this->selectedEntity = null;

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



    public function render()
    {
        return view('livewire.services.oexterno.actions.add-entity-protocol', [
            'entities' => $this->entities,
            'entityTypes' => $this->entityTypes,
        ]);
    }
}
