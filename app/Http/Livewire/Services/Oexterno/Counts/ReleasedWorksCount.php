<?php

namespace App\Http\Livewire\Services\Oexterno\Counts;

use App\Models\ExternalOrganRelease;
use Livewire\Component;

class ReleasedWorksCount extends Component
{
    public function getCountProperty(): int
    {
        return ExternalOrganRelease::query()->newForBadge()->count();
    }

    public function render()
    {
        return view('livewire.services.oexterno.counts.released-works-count', [
            'count' => $this->count,
        ]);
    }
}
