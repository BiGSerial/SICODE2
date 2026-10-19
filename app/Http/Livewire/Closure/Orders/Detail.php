<?php

namespace App\Http\Livewire\Closure\Orders;

use App\Models\{ClosureCycle, Order};
use App\Services\Closure\ClosureMetaClassifier;
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
        $order = Order::with([
            'Note',
            'ClosureTarget.Cycle',
            'ClosureTarget.AuthorizedBy',
            'ClosureTarget.RequestedBy',
            'ClosureState.CurrentHolderUser',
            'ClosureState.CurrentHolderCompany',
            'ClosureState.Events.Actor',
            'ClosureState.Periods.HolderUser',
            'ClosureState.Periods.HolderCompany',
        ])->findOrFail($this->orderId);

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

        $state              = $order->ClosureState;
        $canViewOperational = $this->canViewOperational($state);
        $meta               = app(ClosureMetaClassifier::class)->classify(collect([$order]))->get($order->id);
        $canDispatch        = !$state && !$isClosed && !$order->canceled && auth()->user()->can('closure.dispatch');

        return view('livewire.closure.orders.detail', [
            'order'              => $order,
            'status'             => $status,
            'isClosed'           => $isClosed,
            'target'             => $target,
            'situation'          => $situation,
            'state'              => $state,
            'canViewOperational' => $canViewOperational,
            'metaType'           => $meta['type'] ?? null,
            'competence'         => $meta['competence'] ?? null,
            'canDispatch'        => $canDispatch,
        ]);
    }

    private function canViewOperational($state): bool
    {
        if (!$state) {
            return true;
        }

        $user = auth()->user();

        if ($user->can('closure.manage')) {
            return true;
        }

        return (string) $state->current_holder_user_id === (string) $user->id
            || ($state->current_holder_type === 'PARTNER_COMPANY'
                && (string) $state->current_holder_company_id === (string) $user->company_id);
    }
}
