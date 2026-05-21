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
        return match ($this->metric) {
            'new' => LegalDemand::externallyActive()->where('internal_status', 'new_imported')->count(),
            'triage' => LegalDemand::externallyActive()->where('internal_status', 'triage')->count(),
            'pending' => LegalDemand::externallyActive()->whereIn('internal_status', ['new_imported', 'triage'])->count(),
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
