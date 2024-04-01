<?php

namespace App\Http\Livewire\Dispatchs;

use App\Models\Reclaim;
use App\Models\Service;
use Livewire\Component;

class ReturnD5 extends Component
{
    public $service;

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
    }

    public function getListsProperty()
    {
        return Reclaim::Where('service_id', $this->service->uuid)->where('completed', false)->with('Note.Files', 'Note.Productions', 'Comments')->paginate(50);
    }

    public function render()
    {
        return view('livewire.dispatchs.return-d5', [
            'lists' => $this->lists
        ]);
    }
}
