<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Models\Service;
use Livewire\Component;

class Menu extends Component
{
    public $service;

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();
    }

    public function render()
    {
        return view('livewire.construction.hiring.menu');
    }
}
