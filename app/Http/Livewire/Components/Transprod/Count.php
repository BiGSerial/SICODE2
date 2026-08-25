<?php

namespace App\Http\Livewire\Components\Transprod;

use App\Models\Prodtransfer;
use App\Support\SicodeRules;
use Livewire\Component;

class Count extends Component
{
    public $service_id;

    public function mount($service_id)
    {
        $this->service_id = $service_id;
    }

    public function getCountProperty()
    {
        return Prodtransfer::where('service_id', $this->service_id)
            ->where('to', Auth()->user()->id)
            ->where('read_to', false)
            ->when(auth()->user()?->contract, function ($q) {
                $companyIds = SicodeRules::visibleCompanyIdsFor(auth()->user());

                return count($companyIds)
                    ? $q->whereHas('Production', fn ($production) => $production->whereIn('company_id', $companyIds))
                    : $q->whereRaw('0 = 1');
            })
            ->count();

    }

    public function render()
    {
        return view('livewire.components.transprod.count', [
            'count' => $this->count,
        ]);
    }
}
