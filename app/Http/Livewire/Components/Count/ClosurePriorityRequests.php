<?php

namespace App\Http\Livewire\Components\Count;

use App\Models\Closure\ClosurePriorityRequest;
use Livewire\Component;

/** Contador do menu "Transferência de Encerramento": pedidos de prioridade pendentes. */
class ClosurePriorityRequests extends Component
{
    public function getCountProperty(): int
    {
        if (!auth()->user()?->can('closure.dispatch')) {
            return 0;
        }

        return ClosurePriorityRequest::query()->where('status', ClosurePriorityRequest::STATUS_PENDING)->count();
    }

    public function render()
    {
        return view('livewire.components.count.closure-priority-requests', ['count' => $this->count]);
    }
}
