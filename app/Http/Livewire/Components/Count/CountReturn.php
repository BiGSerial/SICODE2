<?php

namespace App\Http\Livewire\Components\Count;

use App\Models\Reclaim;
use App\Models\Service;
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
        return Reclaim::Where('service_id', $this->service)->where('completed', false)->with('Note.Files', 'Production', 'Comments')->count();
    }

    public function render()
    {
        return view('livewire.components.count.count-return', [
            'count' => $this->count
        ]);
    }
}
