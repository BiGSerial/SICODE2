<?php

namespace App\Http\Livewire\Components\Count;

use App\Models\Reclaim;
use App\Models\Service;
use Carbon\Carbon;
use Livewire\Component;

class CountReturn extends Component
{
    public $service;

    public function mount($service)
    {
        $this->service = $service;
    }

    public function getCountProperty()
    {
        return Reclaim::Where('service_id', $this->service)->where('completed', false)->count();
    }

    public function getNotattProperty()
    {
        return Reclaim::where('service_id', $this->service)
            ->where('completed', false)
            ->whereDoesntHave('production')
            ->count();
    }

    public function getDaysProperty()
    {
        $twentyFourHoursAgo = Carbon::now()->subHours(24)->toDateTimeString();

        return Reclaim::Where('service_id', $this->service)
                        ->where('completed', false)
                        ->where('updated_at', '<', $twentyFourHoursAgo)
                        ->count();
    }



    public function render()
    {
        return view('livewire.components.count.count-return', [
            'count' => $this->count,
            'notAtt' => $this->notatt,
            'days' => $this->days,
        ]);
    }
}
