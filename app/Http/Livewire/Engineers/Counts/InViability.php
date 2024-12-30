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

            if (Auth()->user()->Companies->isNotEmpty()) {
                $query->where(function ($q) {
                    $q->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                    ->orWhere('company_id', Auth()->user()->Company->id);
                });
            } else {
                $query->where('engineer_id', Auth()->user()->id);
            }

            // $query->where('engineer_id', Auth()->user()->id);
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
