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

        $metaTotal       = 0;
        $metaClosed      = 0;
        $passiveTotal    = 0;
        $passiveByCycle  = collect();
        $criticalPassive = collect();

        if ($currentCycle) {
            $metaTotal = $currentCycle->Targets()->count();

            $metaClosed = $currentCycle->Targets()
                ->whereHas('Order', fn ($q) => $this->closedCondition($q))
                ->count();

            $passiveTargets = ClosureTarget::query()
                ->whereHas('Cycle', fn ($q) => $q->whereRaw('(year * 100 + month) < ?', [$currentCycle->periodKey()]))
                ->whereDoesntHave('Order', fn ($q) => $this->closedCondition($q))
                ->with(['Cycle', 'Order', 'Note'])
                ->get();

            $passiveTotal = $passiveTargets->count();

            $passiveByCycle = $passiveTargets
                ->groupBy('closure_cycle_id')
                ->map(fn ($group) => [
                    'cycle' => $group->first()->Cycle,
                    'count' => $group->count(),
                ])
                ->sortBy(fn ($row) => $row['cycle']->periodKey())
                ->values();

            $criticalPassive = $passiveTargets
                ->sortBy(fn (ClosureTarget $target) => $target->Cycle->periodKey())
                ->take(8)
                ->values();
        }

        $metaOpen    = $metaTotal - $metaClosed;
        $metaPercent = $metaTotal > 0 ? round(($metaClosed / $metaTotal) * 100, 1) : 0;

        $statusDonutChart = [
            'type' => 'doughnut',
            'data' => [
                'labels'   => ['Encerradas', 'Em aberto'],
                'datasets' => [[
                    'data'            => [$metaClosed, $metaOpen],
                    'backgroundColor' => ['#0f8a77', '#f7d200'],
                ]],
            ],
            'options' => [
                'cutout'  => '72%',
                'plugins' => [
                    'legend'     => ['position' => 'bottom'],
                    'centerText' => [
                        'display' => true,
                        'text'    => $metaPercent . '%',
                        'subtext' => 'concluído',
                    ],
                ],
            ],
        ];

        $passiveBarChart = [
            'type' => 'bar',
            'data' => [
                'labels'   => $passiveByCycle->pluck('cycle.label')->all(),
                'datasets' => [[
                    'label'           => 'Ordens em aberto',
                    'data'            => $passiveByCycle->pluck('count')->all(),
                    'backgroundColor' => '#e32c2c',
                    'borderRadius'    => 4,
                ]],
            ],
            'options' => [
                'plugins' => ['legend' => ['display' => false]],
                'scales'  => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
            ],
        ];

        return view('livewire.closure.cycles.overview', [
            'currentCycle'     => $currentCycle,
            'metaTotal'        => $metaTotal,
            'metaClosed'       => $metaClosed,
            'metaOpen'         => $metaOpen,
            'metaPercent'      => $metaPercent,
            'passiveTotal'     => $passiveTotal,
            'passiveByCycle'   => $passiveByCycle,
            'criticalPassive'  => $criticalPassive,
            'statusDonutChart' => $statusDonutChart,
            'passiveBarChart'  => $passiveBarChart,
        ]);
    }
}
