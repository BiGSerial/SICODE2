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

        $groups = collect();

        if ($currentCycle) {
            $targets = ClosureTarget::query()
                ->whereHas('Cycle', fn ($q) => $q->whereRaw('(year * 100 + month) < ?', [$currentCycle->periodKey()]))
                ->whereDoesntHave('Order', function ($q) {
                    $q->where('statusSist', 'like', 'ENTE%')->orWhere('statusSist', 'like', 'ENCE%');
                })
                ->with(['Order', 'Note', 'Cycle'])
                ->get();

            $groups = $targets
                ->groupBy('closure_cycle_id')
                ->map(fn ($group) => [
                    'cycle'   => $group->first()->Cycle,
                    'targets' => $group->sortBy(fn (ClosureTarget $target) => $target->Order->ordem ?? '')->values(),
                ])
                ->sortBy(fn ($row) => $row['cycle']->periodKey())
                ->values();
        }

        $totalCount = $groups->sum(fn ($row) => $row['targets']->count());
        $oldestDays = $groups->isNotEmpty()
            ? (int) $groups->first()['cycle']->startDate()->diffInDays(now())
            : 0;

        return view('livewire.closure.cycles.passive', compact('groups', 'currentCycle', 'totalCount', 'oldestDays'));
    }
}
