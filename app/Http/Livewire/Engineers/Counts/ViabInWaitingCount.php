<?php

namespace App\Http\Livewire\Engineers\Counts;

use App\Models\Viability;
use Livewire\Component;

class ViabInWaitingCount extends Component
{
    public function getCountProperty()
    {
        $query = Viability::query()->where('completed', false)
        ->where('visible_partner', true);

        if (!auth()->user()->superadm) {
            $query->whereIn('engineer_id', auth()->user()->visibleUserIdsForWork());
        }

        return $query->count();
    }

    public function render()
    {
        return view('livewire.engineers.counts.viab-in-waiting-count', [
            'count' => $this->count
        ]);
    }
}
