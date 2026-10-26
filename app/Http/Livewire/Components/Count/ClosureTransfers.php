<?php

namespace App\Http\Livewire\Components\Count;

use App\Services\Closure\ClosureTransferService;
use Livewire\Component;

/** Contador do menu "Transferências": pedidos de transferência esperando a minha resposta. */
class ClosureTransfers extends Component
{
    public function render()
    {
        $user = auth()->user();

        return view('livewire.components.count.closure-priority-requests', [
            'count' => $user?->can('closure.work') ? app(ClosureTransferService::class)->pendingCountFor($user) : 0,
        ]);
    }
}
