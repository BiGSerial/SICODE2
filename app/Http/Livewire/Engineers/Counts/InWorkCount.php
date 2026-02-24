<?php

namespace App\Http\Livewire\Engineers\Counts;

use App\Models\Viability;
use Livewire\Component;

class InWorkCount extends Component
{
    public function getCountProperty()
    {
        $query = Viability::query()->where('rejected', true)
        ->where('completed', false)
        ->where('status', 4);

        if (!auth()->user()->superadm) {
            $query->whereIn('engineer_id', auth()->user()->visibleUserIdsForWork());
        }

        return $query->count();
    }

    public function render()
    {
        return view('livewire.engineers.counts.in-work-count', [
            'count' => $this->count
        ]);
    }
}
