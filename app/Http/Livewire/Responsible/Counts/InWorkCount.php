<?php

namespace App\Http\Livewire\Responsible\Counts;

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


            // if (Auth()->user()->Companies->isNotEmpty()) {
            //     $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            // } else {
            //     $query->where('company_id', Auth()->user()->Company->id);
            // }

            $query->whereIn('engineer_id', auth()->user()->visibleUserIdsForWork());

        }

        return $query->count();
    }

    public function render()
    {
        return view('livewire.responsible.counts.in-work-count', [
            'count' => $this->count
        ]);
    }
}
