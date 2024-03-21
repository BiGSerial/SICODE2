<?php

namespace App\Http\Livewire\Components\Modal;

use App\Models\Wpa;
use Livewire\Component;

class Mapsingleposition extends Component
{
    public $wpa;

    protected $listeners = [
        'show_map_production' => 'show_map_production',
    ];

    public function show_map_production($production_id)
    {
        $this->wpa = '';

        if ($production_id) {
            if ($this->wpa = Wpa::where('prduction_id')->first()) {
                $this->dispatchBrowserEvent('showModal', [
                    'id' => 'singleMapPosition',
                ]);
            }
        }
    }

    public function render()
    {
        return view('livewire.components.modal.mapsingleposition');
    }
}
