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


            if (Auth()->user()->Companies->isNotEmpty()) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }



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
