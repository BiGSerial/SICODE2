<?php

namespace App\Http\Livewire\Components\Maps;

use App\Models\SicodeSql\Production;
use Livewire\Component;

class SingleView extends Component
{
    public $production;

    public function openMap(Production $production)
    {
        $this->production = $production->load('Note', 'Service', 'User', 'Wpas');

        if ($this->production && $this->production->Wpas->count() > 0) {
            
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'map_modal',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.components.maps.single-view');
    }
}
