<?php

namespace App\Http\Livewire\Protests\Dispatch\Actions;

use App\Models\Note;
use App\Models\Protest;
use Livewire\Component;

class AddNotesRelation extends Component
{
    public $search = '';
    public $protest;
    public $note;


    protected $listeners = [
        'openAddNotesRelation',
        'refreshComponent' => '$refresh',
    ];

    public function openAddNotesRelation(Protest $protest)
    {
        $this->reset(['search', 'note']);
        $this->protest = $protest->load('Notes');

        if ($this->protest) {
            $this->dispatchBrowserEvent('showModal', [
               'id' => 'addNotesRelationModal',
            ]);
        }
    }


    public function addNoteToProtest($id)
    {
        if ($id) {
            $this->protest->Notes()->syncWithoutDetaching([$id]);
            $this->emit('refreshComponent');
        }
    }

    public function removeNoteFromProtest($id)
    {
        if ($id) {
            $this->protest->Notes()->detach($id);
            $this->emit('refreshComponent');
        }
    }

    public function getNotesProperty()
    {
        return Note::where('note', trim($this->search))->get();
    }


    public function closeAll()
    {
        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.protests.dispatch.actions.add-notes-relation', [
            'notes' => $this->notes,
        ]);
    }
}
