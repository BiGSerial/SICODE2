<?php

namespace App\Http\Livewire\Components\Count;

use App\Enum\CancellationRequestStatus;
use App\Models\CancellationRequest;
use Livewire\Component;

class CancellationRequests extends Component
{
    public string $mode = 'unassigned';
    public ?string $userId = null;

    public function mount(string $mode = 'unassigned', ?string $userId = null): void
    {
        $this->mode = $mode;
        $this->userId = $userId;
    }

    public function getCountProperty(): int
    {
        $query = CancellationRequest::query();

        if ($this->mode === 'unassigned') {
            return $query
                ->whereNull('assigned_to')
                ->where('status', CancellationRequestStatus::SUBMITTED->value)
                ->count();
        }

        if ($this->mode === 'in_progress') {
            $query->whereIn('status', [
                CancellationRequestStatus::ASSIGNED->value,
                CancellationRequestStatus::PAUSED->value,
            ]);

            if ($this->userId) {
                $query->where('assigned_to', $this->userId);
            }

            return $query->count();
        }

        return 0;
    }

    public function render()
    {
        return view('livewire.components.count.cancellation-requests', [
            'count' => $this->count,
            'mode' => $this->mode,
        ]);
    }
}
