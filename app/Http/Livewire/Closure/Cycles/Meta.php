<?php

namespace App\Http\Livewire\Closure\Cycles;

use App\Models\{ClosureCycle, ClosureTarget};
use Livewire\Component;

class Meta extends Component
{
    public ?int $cycleId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('closure.manager'), 403);

        $this->cycleId = ClosureCycle::query()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->value('id');
    }

    public function render()
    {
        $cycles = ClosureCycle::query()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $targets = collect();

        if ($this->cycleId) {
            $targets = ClosureTarget::query()
                ->where('closure_cycle_id', $this->cycleId)
                ->with(['Order', 'Note'])
                ->get()
                ->groupBy(fn (ClosureTarget $target) => $target->Note?->note ?? "Nota #{$target->note_id}");
        }

        return view('livewire.closure.cycles.meta', compact('cycles', 'targets'));
    }
}
