<?php

namespace App\Http\Livewire\Partner\Count;

use App\Models\Viability;
use Livewire\Component;

class Tacitcount extends Component
{
    public function getTacitCountProperty()
    {
        $query = Viability::query()
        ->doesntHave('Justification')
        ->whereBetween('tacit_at', [now()->subDays(7)->startOfDay(), now()->endOfDay()])
        ->where('approved', true)
        ->where('completed', true)
        ->where('tacit', true);

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
        return view('livewire.partner.count.tacitcount', [
            'sum' => $this->tacitcount
        ]);
    }
}
