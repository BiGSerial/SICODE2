<?php

namespace App\Http\Livewire\Partner\Count;

use App\Models\WorkReport;
use Livewire\Component;

class Returnworkforms extends Component
{
    public function getSumProperty()
    {
        return WorkReport::when(!Auth()->User()->superadm, function ($q) {
            $q->where('company_id', Auth()->User()->Employee->Contract->company->id);
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
