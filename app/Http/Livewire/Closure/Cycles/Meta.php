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

        $this->cycleId = $this->openCyclesQuery()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->value('id');
    }

    /**
     * Só lista competências que ainda têm alguma Ordem em aberto — uma vez que 100%
     * das Ordens de uma competência já encerraram no SAP, não sobra nada "para resolver"
     * ali, então ela some do seletor.
     */
    private function openCyclesQuery()
    {
        return ClosureCycle::query()
            ->whereHas('Targets.Order', function ($query) {
                $query->where('statusSist', 'not like', 'ENTE%')
                    ->where('statusSist', 'not like', 'ENCE%');
            });
    }

    public function render()
    {
        $cycles = $this->openCyclesQuery()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $targets       = collect();
        $flatTargets   = collect();
        $summaryClosed = 0;
        $selectedCycle = $this->cycleId ? ClosureCycle::find($this->cycleId) : null;

        if ($this->cycleId) {
            $flatTargets = ClosureTarget::query()
                ->where('closure_cycle_id', $this->cycleId)
                ->with(['Order', 'Note'])
                ->get();

            $summaryClosed = $flatTargets
                ->filter(function (ClosureTarget $target) {
                    $status = (string) ($target->Order->statusSist ?? '');

                    return str_starts_with($status, 'ENTE') || str_starts_with($status, 'ENCE');
                })
                ->count();

            $targets = $flatTargets->groupBy(fn (ClosureTarget $target) => $target->Note?->note ?? "Nota #{$target->note_id}");
        }

        $summaryTotal   = $flatTargets->count();
        $summaryPercent = $summaryTotal > 0 ? round(($summaryClosed / $summaryTotal) * 100, 1) : 0;
        $cycleAgeDays   = $selectedCycle ? $selectedCycle->startDate()->diffInDays(now()) : 0;

        return view('livewire.closure.cycles.meta', compact(
            'cycles',
            'targets',
            'summaryTotal',
            'summaryClosed',
            'summaryPercent',
            'selectedCycle',
            'cycleAgeDays'
        ));
    }
}
