<?php

namespace App\Http\Livewire\Responsible\Counts;

use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReturnInternCount extends Component
{
    public function getCountProperty()
    {

        $query = Viability::query()
        ->where('viabilities.rejected', true)
        ->where('viabilities.status', 13);
        if (!auth()->user()->superadm) {
            $query->whereIn('engineer_id', auth()->user()->visibleUserIdsForWork());
        }

        return $query->count();

    }
    public function render()
    {
        return view('livewire.responsible.counts.return-intern-count', [
            'count' => $this->count
        ]);
    }
}
