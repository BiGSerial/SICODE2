<?php

namespace App\Http\Livewire\Partner\Count;

use App\Models\WorkReport;
use Livewire\Component;

class Returnworkforms extends Component
{
    public function getSumProperty()
    {
        return WorkReport::when(!Auth()->User()->superadm, function ($q) {
            $q->where(function ($query) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                    ->orWhere('company_id', Auth()->user()->Company->id);
            });
        })
        ->where('rejected', true)
        ->count();
    }

    public function render()
    {
        return view('livewire.partner.count.returnworkforms', [
            'sum' => $this->sum
        ]);
    }
}
