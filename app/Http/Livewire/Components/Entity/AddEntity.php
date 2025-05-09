<?php

namespace App\Http\Livewire\Components\Entity;

use App\Models\Entity;
use App\Models\EntityType;
use Livewire\Component;

class AddEntity extends Component
{
    public $name;
    public $search;
    public $selectedType;
    public $entityEdit;
    public $newDoc;


    protected $listeners = [
        'refreshComponent' => '$refresh',
        'openEntity',
    ];

    protected $rules = [
        'entityEdit' => 'nullable',
        'entityEdit.entity_type_id' => 'integer',
        'entityEdit.name' => 'string|max:255',
        'entityEdit.nick' => 'string|max:20',
        'entityEdit.approve' => 'boolean',
        'entityEdit.eon' => 'boolean',
        'entityEdit.cad' => 'boolean',
        'entityEdit.map' => 'boolean',
        'entityEdit.docs' => 'nullable|array',
        'entityEdit.observations' => 'nullable|string',
    ];

    public function openEntity()
    {
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'modalEntity',
        ]);
    }

    public function addEntity()
    {
        if (!$this->selectedType || !$this->name) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'IFORMAÇÕES OBRIGATÓRIAS',
                'html'     => 'É necessário informar o tipo e o nome da entidade.',
            ]);
            return;
        }

        if ($this->name && !$this->lists->contains('name', $this->name)) {
            $this->name = mb_strtoupper(trim($this->name));
            Entity::updateOrCreate([
                'entity_type_id' => $this->selectedType,
                'name' => $this->name,
            ], [
                'entity_type_id' => $this->selectedType,
                'name'           => $this->name,
            ]);
        }

        $this->emitSelf('refreshComponent');
    }

    public function addDoc()
    {
        if ($this->newDoc) {

            $docs = $this->entityEdit->docs ?? [];


            $docs[] = mb_strtoupper(trim($this->newDoc));


            $this->entityEdit->docs = $docs;


            $this->newDoc = null;

            $this->emitSelf('refreshComponent');
        }
    }

    public function removeDoc(int $index)
    {
        $docs = $this->entityEdit->docs ?? [];

        if (array_key_exists($index, $docs)) {

            unset($docs[$index]);

            $docs = array_values($docs);

            sort($docs, SORT_STRING);

            $this->entityEdit->docs = $docs;

            $this->emitSelf('refreshComponent');
        }
    }

    public function editEntity(Entity $entity)
    {
        $this->entityEdit = $entity;
    }

    public function saveEntity()
    {
        $this->validate();

        if ($this->entityEdit) {
            $this->entityEdit->save();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'SUCESSO',
                'html'     => 'Entidade editada com sucesso.',
            ]);

            $this->entityEdit = null;
        }

        $this->emitSelf('refreshComponent');
    }

    public function deleteType()
    {

        $this->emitSelf('refreshComponent');
    }

    public function getListsProperty()
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
        return view('livewire.components.entity.add-entity', [
            'lists' => $this->lists,
            'entityTypes' => $this->entityTypes,
        ]);
    }
}
