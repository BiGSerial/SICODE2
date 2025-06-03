<?php

namespace App\Http\Livewire\Components\Entity;

use App\Models\EntityType;
use Livewire\Component;

class AddEntityType extends Component
{
    public $name;
    public $search;
    public $editEntityType;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'openEntityType',
    ];

    protected $rules = [
        'editEntityType' => 'nullable',
        'editEntityType.name' => 'required|string|max:255',
    ];

    public function openEntityType()
    {
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'modalEntityType',
        ]);
    }

    public function addType()
    {
        if ($this->name && !$this->lists->contains('name', $this->name)) {
            EntityType::updateOrCreate([
                'name' => $this->name,
            ], [
                'name' => mb_strtoupper($this->name),
            ]);
        }

        $this->closeAll();
    }

    public function deleteType(EntityType $entityType)
    {

        if ($entityType) {
            $entityType->delete();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'SUCESSO',
                'html'     => 'Entidade excluída com sucesso.',
            ]);

            $this->closeAll();
        }


    }


    public function editEntityType(EntityType $entityType)
    {
        $this->editEntityType = $entityType;

        $this->closeAll();
    }

    public function saveEntityType()
    {
        $this->validate();

        if ($this->editEntityType) {
            $this->editEntityType->save();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'SUCESSO',
                'html'     => 'Entidade editada com sucesso.',
            ]);

            $this->editEntityType = null;
        }

        $this->closeAll();
    }

    public function getListsProperty()
    {
        return EntityType::when(trim($this->search), function ($q) {
            $q->where('name', 'like', '%'.trim($this->search).'%');
        })->orderBy('name')->get();
    }

    public function closeAll()
    {
        // $this->dispatchBrowserEvent('hideModal');
        $this->emitSelf('refreshComponent');
        $this->emitUp('refreshComponent');
    }

    public function render()
    {
        return view('livewire.components.entity.add-entity-type', [
            'lists' => $this->lists,
        ]);
    }
}
