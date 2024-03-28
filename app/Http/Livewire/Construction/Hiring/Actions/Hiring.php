<?php

namespace App\Http\Livewire\Construction\Hiring\Actions;

use App\Models\Service;
use Livewire\Component;

class Hiring extends Component
{
    public $list;
    public $services;
    public $service_s;
    public $comment;

    public function mount($list)
    {
        $this->list = $list;
        $this->services = Service::orderBy('service')->get();
    }

    public function render()
    {
        return view('livewire.construction.hiring.actions.hiring', [
            'services' => $this->services
        ]);
    }
}
