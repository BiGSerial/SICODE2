<?php

namespace App\Http\Livewire\Legal\Controller;

use App\Models\Legal\LegalDemand;
use Livewire\Component;

class TriageCounter extends Component
{
    public string $metric = '';

    public function mount(string $metric): void
    {
        $this->metric = $metric;
    }

    private function value(): int
    {
        $base = LegalDemand::externallyActive()
            ->whereRaw("LOWER(COALESCE(process_status_at_import, '')) NOT LIKE ?", ['%encerrad%'])
            ->where('controller_user_id', auth()->id());

        return match ($this->metric) {
            'new' => (clone $base)->where('internal_status', 'new_imported')->count(),
            'triage' => (clone $base)->whereIn('internal_status', ['triage', 'waiting_controller_action'])->count(),
            'pending' => (clone $base)->count(),
            default => 0,
        };
    }

    public function render()
    {
        return view('livewire.legal.controller.triage-counter', [
            'value' => $this->value(),
        ]);
    }
}
