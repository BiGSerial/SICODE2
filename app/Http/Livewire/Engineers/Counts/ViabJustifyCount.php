<?php

namespace App\Http\Livewire\Engineers\Counts;

use App\Models\Viability;
use Livewire\Component;

class ViabJustifyCount extends Component
{
    public function getCountProperty()
    {
        $query = Viability::query();

        $query->whereRelation('Justification', function ($query) {
            $query->where('granted', false)
            ->where('dismissed', false)
            ->orderBy('justified_at', 'desc');
        })->where('tacit', true);

        if (!auth()->user()->superadm) {

            $query->whereIn('engineer_id', auth()->user()->visibleUserIdsForWork());
        }

        return $query->count();
    }

    public function render()
    {
        return view('livewire.engineers.counts.viab-justify-count', [
            'count' => $this->count
        ]);
    }
}
