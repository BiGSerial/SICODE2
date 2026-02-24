<?php

namespace App\Http\Livewire\Responsible\Counts;

use App\Models\Viability;
use Livewire\Component;

class ViabJustifyCount extends Component
{
    public function getCountProperty()
    {
        $query = Viability::query();

        $query->whereHas('Justification', function ($query) {
            $query->where('granted', false)
            ->where('dismissed', false)
            ->orderBy('justified_at', 'desc');
        })->where('tacit', true);


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


        return view('livewire.responsible.counts.viab-justify-count', [
            'count' => $this->count
        ]);
    }
}
