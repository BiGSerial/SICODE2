<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Models\MedProtest;
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
        return MedProtest::query()
            ->where('statusSist', 'MEDA')
            ->whereDoesntHave('ProtestJobs')
            ->when($this->type === 'btzero', function ($typeQuery) {
                $typeQuery->identifiedAsBtzero();
            }, function ($typeQuery) {
                $typeQuery->notIdentifiedAsBtzero();
            })
            ->count();
    }
}
