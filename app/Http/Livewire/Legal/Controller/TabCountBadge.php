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
                ->whereRaw("LOWER(COALESCE(process_status_at_import, '')) NOT LIKE ?", ['%encerrad%'])
                ->whereNull('current_assigned_user_id')
                ->whereNull('current_assigned_team_id')
                ->whereDoesntHave('assignments', function ($aq) {
                    $aq->whereIn('status', ['sent', 'received', 'returned_for_correction']);
                })
                ->count(),
            'in_progress' => LegalDemand::externallyActive()
                
                ->whereIn('internal_status', [
                    'sent_to_field',
                    'field_received',
                    'waiting_field_response',
                    'returned_by_field',
                    'under_controller_review',
                    'ready_to_close_external',
                    'reopened',
                ])
                ->count(),
            'overdue' => LegalDemand::externallyActive()
                
                ->overdue()
                ->whereRaw("LOWER(COALESCE(process_status_at_import, '')) NOT LIKE ?", ['%encerrad%'])
                ->count(),
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
