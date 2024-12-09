<?php

namespace App\Http\Livewire\Construction\Hiring\Counts;

use App\Models\Viability;
use Livewire\Component;

class CheckHiring extends Component
{
    public function getCountsProperty()
    {
        return Viability::where('hired', true)->whereRelation('Orders', function ($query) {

            $query->where('statusSist', '!=', 'ENCE%')
                    ->where('statusSist', '!=', 'ENT%')
                    ->whereRelation('Operations', function ($query) {
                        $query->where('operacao', '0010')
                            ->where('status', '!=', 'CONF%');
                    });
        })->count();
    }


    public function render()
    {
        return view('livewire.construction.hiring.counts.check-hiring', [
            'count' => $this->counts
        ]);
    }
}
