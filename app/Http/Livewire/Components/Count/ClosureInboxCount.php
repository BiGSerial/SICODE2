<?php

namespace App\Http\Livewire\Components\Count;

use App\Services\Closure\ClosureArea;
use App\Services\Closure\Queries\ClosureInboxQuery;
use Livewire\Component;

/**
 * Selo do menu da caixa do Encerramento (Medição/Fiscalização):
 *  - `accompanying`: Ordens em acompanhamento (azul, só visão);
 *  - `dispatched`: pendências despachadas para a área esperando resolver (vermelho, ação).
 */
class ClosureInboxCount extends Component
{
    public string $area = ClosureArea::MEASUREMENT;

    public string $kind = 'accompanying';

    public function mount(string $area, string $kind = 'accompanying'): void
    {
        $this->area = $area;
        $this->kind = $kind === 'dispatched' ? 'dispatched' : 'accompanying';
    }

    public function getCountProperty(): int
    {
        if (!ClosureArea::hasInbox($this->area) || !auth()->user()?->can('closure.inbox.' . strtolower($this->area))) {
            return 0;
        }

        $query = app(ClosureInboxQuery::class);

        return $this->kind === 'dispatched'
            ? $query->dispatched($this->area, 'open')->count()
            : array_sum($query->accompanyingCounts($this->area));
    }

    public function render()
    {
        return view('livewire.components.count.closure-inbox-count', ['count' => $this->count, 'kind' => $this->kind]);
    }
}
