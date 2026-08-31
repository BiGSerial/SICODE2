<?php

namespace App\Http\Livewire\Closure\Cycles;

use App\Models\{ClosureCycle, ClosureTarget};
use Livewire\Component;

class Passive extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can('closure.manager'), 403);
    }

    public function render()
    {
        $currentCycle = ClosureCycle::query()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        $targets = collect();

        if ($currentCycle) {
            $targets = ClosureTarget::query()
                ->whereHas('Cycle', fn ($q) => $q->whereRaw('(year * 100 + month) < ?', [$currentCycle->periodKey()]))
                ->whereDoesntHave('Order', function ($q) {
                    $q->where('statusSist', 'like', 'ENTE%')->orWhere('statusSist', 'like', 'ENCE%');
                })
                ->with(['Order', 'Note', 'Cycle'])
                ->get()
                ->sortByDesc(fn (ClosureTarget $target) => $target->frozen_at);
        }

        return view('livewire.closure.cycles.passive', compact('targets', 'currentCycle'));
    }
}
