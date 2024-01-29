<?php

namespace App\Http\Livewire\Components\Transprod;

use App\Models\Prodtransfer;
use Illuminate\Support\Facades\Auth;
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
        ->whereRelation('Production', 'completed', false)
        ->where(function ($q) {
            return $q->where('from', Auth()->User()->id)
                    ->where('read_from', false);
        })->orWhere(function ($q) {
            return $q->where('to', Auth()->User()->id)
                    ->where('read_to', false);
        })->count();
    }

    public function render()
    {
        return view('livewire.components.transprod.count', [
            'count' => $this->count
        ]);
    }
}
