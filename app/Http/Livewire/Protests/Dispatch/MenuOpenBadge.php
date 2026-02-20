<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Models\Protest;
use Livewire\Component;

class MenuOpenBadge extends Component
{
    public string $type = 'normal';

    public function mount(string $type = 'normal'): void
    {
        $this->type = $type === 'btzero' ? 'btzero' : 'normal';
    }

    public function render()
    {
        return view('livewire.protests.dispatch.menu-open-badge', [
            'count' => $this->count,
        ]);
    }

    public function getCountProperty(): int
    {
        return Protest::query()
            ->whereHas('medProtests', function ($q) {
                $q->where('statusSist', 'MEDA')
                    ->whereDoesntHave('ProtestJobs')
                    ->when($this->type === 'btzero', function ($typeQuery) {
                        $typeQuery->identifiedAsBtzero();
                    }, function ($typeQuery) {
                        $typeQuery->notIdentifiedAsBtzero();
                    });
            })
            ->count();
    }
}
