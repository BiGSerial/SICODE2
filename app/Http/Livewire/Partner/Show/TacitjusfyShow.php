<?php

namespace App\Http\Livewire\Partner\Show;

use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TacitjusfyShow extends Component
{
    public $viability;
    public $description;
    public $hasFile = false;


    protected $listeners = [
        'getTacitInfo',
    ];

    public function hasFile($value)
    {
        $this->hasFile = $value;
    }

    public function getTacitInfo(Viability $viability)
    {
        $this->viability = $viability;

        if ($this->viability) {

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'tacitresponse-show-modal',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.partner.show.tacitjusfy-show');
    }
}
