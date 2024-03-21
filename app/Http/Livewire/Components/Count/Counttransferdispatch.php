<?php

namespace App\Http\Livewire\Components\Count;

use App\Models\Production;
use Livewire\Component;

class Counttransferdispatch extends Component
{
    public $service;

    public $onlyuser;

    public $status;

    public $geral;

    public function mount($service = null, $status = null, $onlyuser = true, $geral = false)
    {
        $this->service  = $service;
        $this->onlyuser = $onlyuser;
        $this->status   = $status;
        $this->geral    = $geral;
    }

    public function getCountProperty()
    {
        return Production::when($this->service, function ($q) {
            return $q->where('service_id', $this->service);
        })
            ->where('block_wpa', true)
            ->where('block', false)
            ->count();
    }

    public function render()
    {
        return view('livewire.components.count.counttransferdispatch', [
            'count' => $this->count,
        ]);
    }
}
