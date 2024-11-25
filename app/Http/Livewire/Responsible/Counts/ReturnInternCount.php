<?php

namespace App\Http\Livewire\Responsible\Counts;

use App\Models\Viability;
use Livewire\Component;

class ReturnInternCount extends Component
{
    public function getCountProperty()
    {

        $query = Viability::query()->where('rejected', true)
        ->where('completed', false)
        ->whereRelation('Reclaims', 'completed', true);


        if (!auth()->user()->superadm) {


            // if (Auth()->user()->Companies->isNotEmpty()) {
            //     $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            // } else {
            //     $query->where('company_id', Auth()->user()->Company->id);
            // }

            $query->where('engineer_id', Auth()->user()->id);

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
