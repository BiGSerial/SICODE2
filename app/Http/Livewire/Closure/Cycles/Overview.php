<?php

namespace App\Http\Livewire\Closure\Cycles;

use App\Models\{ClosureCycle, ClosureTarget};
use Livewire\Component;

class Overview extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can('closure.manager'), 403);
    }

    private function closedCondition($query)
    {
        return $query->where('statusSist', 'like', 'ENTE%')->orWhere('statusSist', 'like', 'ENCE%');
    }

    public function render()
    {
        $currentCycle = ClosureCycle::query()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        $metaTotal    = 0;
        $metaClosed   = 0;
        $passiveTotal = 0;

        if ($currentCycle) {
            $metaTotal = $currentCycle->Targets()->count();

            $metaClosed = $currentCycle->Targets()
                ->whereHas('Order', fn ($q) => $this->closedCondition($q))
                ->count();

            $passiveTotal = ClosureTarget::query()
                ->whereHas('Cycle', fn ($q) => $q->whereRaw('(year * 100 + month) < ?', [$currentCycle->periodKey()]))
                ->whereDoesntHave('Order', fn ($q) => $this->closedCondition($q))
                ->count();
        }

        return view('livewire.closure.cycles.overview', [
            'currentCycle' => $currentCycle,
            'metaTotal'    => $metaTotal,
            'metaClosed'   => $metaClosed,
            'metaOpen'     => $metaTotal - $metaClosed,
            'passiveTotal' => $passiveTotal,
        ]);
    }
}
