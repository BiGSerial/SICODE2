<?php

namespace App\Http\Livewire\Engineers\Counts;

use App\Services\Partner\PepBudgetWatchService;
use Livewire\Component;

class PepAlertsCount extends Component
{
    public $menu;

    public function mount(bool $menu = false)
    {
        $this->menu = $menu;
    }

    public function getCountProperty(): int
    {
        $watch = app(PepBudgetWatchService::class);

        return $watch->scopeVisibleTo($watch->openQuery(), auth()->user())->count();
    }

    public function render()
    {
        return view('livewire.engineers.counts.pep-alerts-count', ['count' => $this->count]);
    }
}
