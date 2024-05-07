<?php

namespace App\Http\Livewire\Construction\Hiring\Actions;

use App\Models\Note;
use Livewire\Component;

class Edit extends Component
{
    public ?Note $note = null;

    protected $listeners = [
        'edit_hiring' => 'editHiring',
    ];

    public function editHiring(Note $note)
    {
        $this->note = $note->load(['Viabilities' => function ($q) {
            $q->where('completed', false)
                ->where('hired', false)
                ->where('user_id', Auth()->User()->id);
        }]);



        if ($this->note) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_edit_hiring',
            ]);
        }
    }



    public function render()
    {
        return view('livewire.construction.hiring.actions.edit');
    }
}
