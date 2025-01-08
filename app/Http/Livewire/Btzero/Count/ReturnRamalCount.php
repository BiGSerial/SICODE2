<?php

namespace App\Http\Livewire\Btzero\Count;

use App\Models\RamalReport;
use Livewire\Component;

class ReturnRamalCount extends Component
{
    public function getCountProperty()
    {
        return RamalReport::where('rejected', true)
            ->count();
    }


    public function render()
    {
        return view('livewire.btzero.count.return-ramal-count', [
            'count' => $this->count,
        ]);
    }
}
