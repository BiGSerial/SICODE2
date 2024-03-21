<?php

namespace App\Http\Livewire\Audits;

use App\Models\Audit;
use Livewire\Component;

class Prodbutton extends Component
{
    public $audit = false;

    public $prod;

    public $service;

    public function mount($prod, $service)
    {
        $this->prod    = $prod;
        $this->service = $service;

        if (Audit::where('after->id', $prod)->where('after->service_id', $service)->count()) {
            $this->audit = true;
        } else {
            $this->audit = false;
        }
    }

    public function audit()
    {

        $this->emit('audit_prod', ['production_id' => $this->prod, 'service_id' => $this->service]);
    }

    public function render()
    {
        return view('livewire.audits.prodbutton');
    }
}
