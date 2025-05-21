<?php

namespace App\Http\Livewire\Components\Entity;

use App\Helpers\TextFormatter;
use App\Models\Entity;
use App\Models\EntityContact;
use App\Models\EntityType;
use Livewire\Component;

class AddEntity extends Component
{
    use TextFormatter;

    public $name;
    public $search;
    public $selectedType;
    public $entityEdit;
    public $newDoc;
    public $newPortal;
    public $newContact;

    // Show
    public $showContactForm = false;
    public $showPortalForm = false;




    protected $listeners = [
        'refreshComponent' => '$refresh',
        'openEntity',
    ];

    protected $rules = [
        'entityEdit' => 'nullable',
        'entityEdit.entity_type_id' => 'integer',
        'entityEdit.name' => 'string|max:255',
        'entityEdit.nick' => 'string|max:80',
        'entityEdit.approve' => 'boolean',
        'entityEdit.eon' => 'boolean',
        'entityEdit.cad' => 'boolean',
        'entityEdit.map' => 'boolean',
        'entityEdit.docs' => 'nullable|array',
        'entityEdit.observations' => 'nullable|string',
        'newPortal' => 'nullable',
        'newPortal.url' => 'nullable|string|max:255',
        'newPortal.user' => 'nullable|string|max:255',
        'newPortal.password' => 'nullable|string|max:255',
        'newContact' => 'nullable',
        'newContact.name' => 'nullable|string|max:255',
        'newContact.email' => 'nullable|string|max:255',

    ];

    public function addConctact()
    {
        $this->newContact = new EntityContact();
        $this->newPortal = null;
    }

    public function addPortal()
    {
        $this->newPortal = new EntityContact();
        $this->newContact = null;
    }

    public function openEntity()
    {
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'modalEntity',
        ]);
    }

    public function savePortal()
    {
        $this->entityEdit->contacts()->updateOrCreate([
             'entity_id' => $this->entityEdit->id,
             'url' => $this->newPortal['url'],
         ], [
             'entity_id' => $this->entityEdit->id,
             'url' => $this->newPortal['url'],
             'user' => $this->newPortal['user'],
             'password' => $this->newPortal['password'],
         ]);

        $this->newPortal = null;

        $this->closeAll();
    }

    public function saveContact()
    {
        $this->entityEdit->contacts()->updateOrCreate([
            'entity_id' => $this->entityEdit->id,
            'name' => $this->newContact['name'],
        ], [
            'entity_id' => $this->entityEdit->id,
            'name' => $this->newContact['name'],
            'email' => $this->newContact['email'],

        ]);

        $this->newContact = null;

        $this->closeAll();
    }

    public function removeContact(EntityContact $contact)
    {
        $contact->delete();

        $this->closeAll();
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

        $this->closeAll();
    }

    public function addDoc()
    {
        if ($this->newDoc) {

            $arrayDocs = $this->formatTextLongToArray($this->newDoc);

            $docs = $this->entityEdit->docs ?? [];

            foreach ($arrayDocs as $value) {
                $docs[] = mb_strtoupper(trim($value));
            }


            // $docs[] = mb_strtoupper(trim($this->newDoc));


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

    public function entityEdit(Entity $entity)
    {
        $this->entityEdit = $entity;
    }

    public function saveEntity()
    {
        $this->validate();

        if ($this->entityEdit) {

            if (is_string($this->entityEdit->name)) {
                $this->entityEdit->name = mb_strtoupper($this->entityEdit->name);
            }
            if (is_string($this->entityEdit->nick)) {
                $this->entityEdit->nick = mb_strtoupper($this->entityEdit->nick);
            }




            $this->entityEdit->save();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'SUCESSO',
                'html'     => 'Entidade editada com sucesso.',
            ]);

            $this->entityEdit = null;
        }

        $this->closeAll();
    }

    public function deleteEntity(Entity $entity)
    {
        if ($entity) {
            $entity->delete();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'SUCESSO',
                'html'     => 'Entidade excluída com sucesso.',
            ]);

            $this->closeAll();
        }
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

    public function closeAll()
    {
        // $this->dispatchBrowserEvent('hideModal');
        $this->emitSelf('refreshComponent');
        $this->emitUp('refreshComponent');
    }

    public function render()
    {
        return view('livewire.components.entity.add-entity', [
            'lists' => $this->lists,
            'entityTypes' => $this->entityTypes,
        ]);
    }
}
