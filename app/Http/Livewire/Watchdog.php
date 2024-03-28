<?php

namespace App\Http\Livewire;

use App\Models\Activeuser;
use Carbon\Carbon;
use Livewire\Component;

class Watchdog extends Component
{
    public function watchdog()
    {
        Activeuser::updateOrCreate(
            ['user_id' => Auth()->User()->id],
            [
                'user_id' => Auth()->User()->id,
                'watchdog' => true
            ]
        );

        Activeuser::where('updated_at', '<', Carbon::now()->subMinutes(6))
            ->where('watchdog', true)
            ->update(
                [
                    'watchdog' => false
                ]
            );
    }


    public function render()
    {
        return view('livewire.watchdog');
    }
}
