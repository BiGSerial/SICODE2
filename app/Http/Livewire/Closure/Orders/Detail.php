<?php

namespace App\Http\Livewire\Closure\Orders;

use App\Models\{ClosureCycle, Order};
use Livewire\Component;

class Detail extends Component
{
    public int $orderId;

    public function mount(int $orderId): void
    {
        abort_unless(auth()->user()->can('closure.view'), 403);

        $this->orderId = $orderId;
    }

    public function render()
    {
        $order = Order::with(['Note', 'ClosureTarget.Cycle', 'ClosureTarget.AuthorizedBy', 'ClosureTarget.RequestedBy'])->findOrFail($this->orderId);

        $status   = (string) ($order->statusSist ?? '');
        $isClosed = str_starts_with($status, 'ENTE') || str_starts_with($status, 'ENCE');

        $target    = $order->ClosureTarget;
        $isPassive = false;

        if ($target && !$isClosed && $target->Cycle) {
            $isPassive = $target->Cycle->periodKey() < ClosureCycle::currentPeriodKey();
        }

        $situation = match (true) {
            $isClosed        => 'ENCERRADA',
            $isPassive       => 'PASSIVO',
            $target !== null => 'NA META ATUAL',
            default          => 'FORA DA META',
        };

        return view('livewire.closure.orders.detail', [
            'order'     => $order,
            'status'    => $status,
            'isClosed'  => $isClosed,
            'target'    => $target,
            'situation' => $situation,
        ]);
    }
}
