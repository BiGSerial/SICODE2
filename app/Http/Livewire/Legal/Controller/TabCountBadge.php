<?php

namespace App\Http\Livewire\Legal\Controller;

use App\Models\Legal\LegalDemand;
use Livewire\Component;

class TabCountBadge extends Component
{
    public string $tab = '';

    public function mount(string $tab): void
    {
        $this->tab = $tab;
    }

    private function countForTab(): int
    {
        return match ($this->tab) {
            'triage' => LegalDemand::externallyActive()
                ->whereIn('internal_status', ['new_imported', 'triage', 'waiting_controller_action'])
                ->count(),
            'in_field' => LegalDemand::externallyActive()
                ->whereIn('internal_status', ['sent_to_field', 'field_received', 'waiting_field_response'])
                ->count(),
            'returned' => LegalDemand::externallyActive()
                ->whereIn('internal_status', ['returned_by_field', 'under_controller_review', 'returned_for_correction'])
                ->count(),
            'overdue' => LegalDemand::externallyActive()->overdue()->count(),
            'closed' => LegalDemand::where(function ($q) {
                $q->whereIn('internal_status', ['closed_internal', 'closed_external', 'cancelled', 'ignored'])
                    ->orWhere(fn ($sub) => $sub->externallyClosed());
            })->count(),
            default => 0,
        };
    }

    public function render()
    {
        return view('livewire.legal.controller.tab-count-badge', [
            'count' => $this->countForTab(),
            'class' => $this->tab === 'overdue' ? 'bg-danger' : 'bg-secondary',
        ]);
    }
}
