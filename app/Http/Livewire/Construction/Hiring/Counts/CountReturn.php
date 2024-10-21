<?php

namespace App\Http\Livewire\Construction\Hiring\Counts;

use App\Models\HiringWaiting;
use Livewire\Component;

class CountReturn extends Component
{
    public function getCountProperty()
    {
        return HiringWaiting::where('complete', false)->count();
    }

    public function render()
    {
        return view('livewire.construction.hiring.counts.count-return', [
            'count' => $this->count
        ]);
    }
}
