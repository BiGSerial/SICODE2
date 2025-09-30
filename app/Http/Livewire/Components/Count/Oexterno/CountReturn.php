<?php

namespace App\Http\Livewire\Components\Count\Oexterno;

use App\Models\Reclaim;
use Livewire\Component;

class CountReturn extends Component
{
    public function getCountProperty()
    {
        return  Reclaim::where('completed', true)
                ->whereHas('externals', function ($q) {
                    $q->where('external_reclaim.completed', 0);
                });

    }

    public function render()
    {
        return view('livewire.components.count.oexterno.count-return', [
            'count' => $this->count->count(),
        ]);
    }
}
