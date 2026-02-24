<?php

namespace App\Http\Livewire\Engineers\Counts;

use App\Models\Viability;
use Livewire\Component;

class InViability extends Component
{
    public function getCountProperty()
    {
        $query = Viability::query();

        $query->where('canceled', false)
            ->where('completed', false)
            ->where('tacit', false)
            ->where('rejected', false)
            ->where('visible_partner', false);

        if (!auth()->user()->superadm) {
            $query->whereIn('engineer_id', auth()->user()->visibleUserIdsForWork());
        }

        return $query->count();
    }

    public function render()
    {
        return view('livewire.engineers.counts.in-viability', [
            'count' => $this->count
        ]);
    }
}
