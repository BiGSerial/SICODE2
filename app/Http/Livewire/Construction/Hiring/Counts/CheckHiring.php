<?php

namespace App\Http\Livewire\Construction\Hiring\Counts;

use App\Models\Viability;
use Livewire\Component;

class CheckHiring extends Component
{
    public function getCountsProperty()
    {
        return Viability::where('hired', true)
            ->whereHas('Note.Orders', function ($query) {
                $query->whereRaw("LTRIM(statusSist) NOT LIKE 'ENT%'")
                    ->whereRaw("LTRIM(statusSist) NOT LIKE 'ENC%'")
                    ->whereRaw("LTRIM(statusSist) NOT LIKE 'CANCE%'")
                    ->whereHas('Operations', function ($q) {
                        $q->where('operacao', '0010')
                            ->where('status', 'NOT LIKE', 'CONF%');
                    });
            })
            ->count();
    }


    public function render()
    {
        return view('livewire.construction.hiring.counts.check-hiring', [
            'count' => $this->counts
        ]);
    }
}
