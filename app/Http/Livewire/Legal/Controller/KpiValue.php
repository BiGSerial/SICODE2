<?php

namespace App\Http\Livewire\Legal\Controller;

use App\Models\Legal\LegalDemand;
use Livewire\Component;

class KpiValue extends Component
{
    public string $metric = '';

    public function mount(string $metric): void
    {
        $this->metric = $metric;
    }

    private function value(): int
    {
        return match ($this->metric) {
            'total_active' => LegalDemand::externallyActive()
                ->whereNotIn('internal_status', ['cancelled', 'ignored'])
                ->count(),
            'overdue' => LegalDemand::externallyActive()->overdue()->count(),
            'awaiting_field' => LegalDemand::externallyActive()
                ->whereIn('internal_status', ['triage', 'waiting_controller_action', 'under_controller_review', 'ready_to_close_external', 'reopened'])
                ->count(),
            'returned_today' => LegalDemand::externallyActive()
                ->whereIn('internal_status', ['triage', 'waiting_controller_action'])
                ->whereDate('updated_at', today())
                ->count(),
            default => 0,
        };
    }

    public function render()
    {
        return view('livewire.legal.controller.kpi-value', [
            'value' => $this->value(),
        ]);
    }
}
