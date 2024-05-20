<?php

namespace App\Http\Livewire\Partner\Actions;

use App\Models\Note;
use Livewire\Component;

class Responserviab extends Component
{
    public ?Note $note = null;

    protected $listeners= [
        'toResponser'
    ];


    public function toResponser(Note $note)
    {
        $this->note = $note;
    }

    public function render()
    {
        return view('livewire.partner.actions.responserviab');
    }
}
