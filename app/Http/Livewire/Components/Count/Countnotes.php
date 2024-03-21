<?php

namespace App\Http\Livewire\Components\Count;

use App\Models\Production;
use Livewire\Component;

class Countnotes extends Component
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
        return Production::Where('completed', false)
            ->when($this->service, function ($q) {
                return $q->where('service_id', $this->service);
            })
            ->when($this->onlyuser, function ($q) {
                return $q->where('user_id', Auth()->User()->id);
            })
            ->when($this->status, function ($q, $s) {
                return $q->where('status', $s);
            })
            ->count();
    }

    public function render()
    {
        return view('livewire.components.count.countnotes', [
            'count' => $this->count,
        ]);
    }
}
