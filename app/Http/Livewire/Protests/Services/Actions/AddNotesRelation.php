<?php

namespace App\Http\Livewire\Protests\Services\Actions;

use App\Models\MedProtest;
use App\Models\Note;
use Livewire\Component;

class AddNotesRelation extends Component
{
    public $search = '';
    public $medProtest;
    public $note;


    protected $listeners = [
        'openAddNotesRelation',
        'refreshComponent' => '$refresh',
    ];

    public function openAddNotesRelation(MedProtest $medProtest)
    {
        $this->medProtest = $medProtest->load('Notes');

        if ($this->medProtest) {
            $this->dispatchBrowserEvent('showModal', [
               'id' => 'addNotesRelationModal',
            ]);
        }
    }


    public function addNoteToProtest($id)
    {
        if ($id) {
            $this->medProtest->Notes()->syncWithoutDetaching([$id]);
            $this->emit('refreshComponent');
        }
    }

    public function removeNoteFromProtest($id)
    {
        if ($id) {
            $this->medProtest->Notes()->detach($id);
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
        return view('livewire.protests.services.actions.add-notes-relation', [
            'notes' => $this->notes,
        ]);
    }
}
