<?php

namespace App\Http\Livewire\Components\Count;

use App\Models\Manualnote;
use Livewire\Component;

class Countwaiting extends Component
{
    public $service;

    public $onlyuser;

    public $status;

    public function mount($service, $status = null, $onlyuser = true)
    {
        $this->service  = $service;
        $this->onlyuser = $onlyuser;
        $this->status   = $status;
    }

    public function getCountProperty()
    {
        return Manualnote::where('service_id', $this->service)
            ->where('user_id', Auth()->User()->id)
            ->where('confirmed', false)
            ->count();
    }

    public function render()
    {
        return view('livewire.components.count.countwaiting', [
            'count' => $this->count,
        ]);
    }
}
