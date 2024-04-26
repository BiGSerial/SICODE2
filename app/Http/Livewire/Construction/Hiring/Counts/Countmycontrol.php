<?php

namespace App\Http\Livewire\Construction\Hiring\Counts;

use App\Models\Note;
use Livewire\Component;

class Countmycontrol extends Component
{
    public function getCountProperty()
    {
        return Note::whereRelation('Viabilities', function ($q) {
            return $q->where('completed', false)
                    ->where('hired', false)
                    ->where('user_id', Auth()->User()->id);
        })->count();

    }

    public function render()
    {
        return view('livewire.construction.hiring.counts.countmycontrol', [
            'count' => $this->count
        ]);
    }
}
