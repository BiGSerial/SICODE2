<?php

namespace App\Http\Livewire\Construction\Hiring\Counts;

use App\Models\Viability;
use Livewire\Component;

class Countmycontrol extends Component
{
    public function getCountProperty()
    {
        return Viability::where('completed', false)
                ->where('hired', false)
                ->where('approved', true)
                ->where('rejected', false)
                ->count();

    }

    public function render()
    {
        return view('livewire.construction.hiring.counts.countmycontrol', [
            'count' => $this->count
        ]);
    }
}
