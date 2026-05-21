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
                ->whereIn('internal_status', ['sent_to_field', 'field_received', 'waiting_field_response'])
                ->count(),
            'returned_today' => LegalDemand::externallyActive()
                ->whereIn('internal_status', ['returned_by_field'])
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
