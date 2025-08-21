<?php

namespace App\Http\Livewire\Components\D5;

use App\Models\Note;
use Livewire\Component;

class D5details extends Component
{
    public $five;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'openD5Details',
    ];

    public function openD5Details(Note $note)
    {

        $this->five = $note->load('FiveNote.Productions')?->fiveNote;

        if ($this->five) {
            $this->dispatchBrowserEvent('showModal', [
            'id' => 'fiveNoteModal',
        ]);
        }
    }

    public function render()
    {
        return view('livewire.components.d5.d5details');
    }
}
