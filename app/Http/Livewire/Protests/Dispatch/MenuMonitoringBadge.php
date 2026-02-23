<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Enum\ProtestJobStatus;
use App\Models\ProtestJob;
use Livewire\Component;

class MenuMonitoringBadge extends Component
{
    public string $type = 'normal';

    public function mount(string $type = 'normal'): void
    {
        $this->type = $type === 'btzero' ? 'btzero' : 'normal';
    }

    public function render()
    {
        return view('livewire.protests.dispatch.menu-monitoring-badge', [
            'openCount'      => $this->openCount,
            'donePending'    => $this->donePendingCount,
        ]);
    }

    protected function baseQuery()
    {
        return ProtestJob::query()
            ->when($this->type === 'btzero', function ($q) {
                $q->whereHas('medProtest', function ($sub) {
                    $sub->identifiedAsBtzero();
                });
            }, function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('med_protest_id')
                        ->orWhereHas('medProtest', function ($inner) {
                            $inner->notIdentifiedAsBtzero();
                        });
                });
            });
    }

    public function getOpenCountProperty(): int
    {
        return (clone $this->baseQuery())
            ->where(function ($q) {
                $q->whereNull('confirmed')
                    ->orWhere('confirmed', false);
            })
            ->count();
    }

    public function getDonePendingCountProperty(): int
    {
        return (clone $this->baseQuery())
            ->where('status', ProtestJobStatus::DONE->value)
            ->where(function ($q) {
                $q->whereNull('confirmed')
                    ->orWhere('confirmed', false);
            })
            ->count();
    }
}
