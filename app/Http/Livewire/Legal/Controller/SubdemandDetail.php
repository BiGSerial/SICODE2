<?php

namespace App\Http\Livewire\Legal\Controller;

use App\Models\Legal\LegalDemandSubdemand;
use Livewire\Component;

class SubdemandDetail extends Component
{
    public int $id;
    public LegalDemandSubdemand $subdemand;

    public function mount(int $id): void
    {
        abort_unless(config('features.legal_subdemands', true), 404);

        $this->id = $id;
        $this->subdemand = LegalDemandSubdemand::query()
            ->with([
                'demand.legalCase',
                'assignedTo',
                'createdBy',
                'events.actor',
                'comments.user',
                'files.uploadedBy',
            ])
            ->findOrFail($id);

        $isController = auth()->user()->can('legal.demands.triage')
            || auth()->user()->can('legal.demands.assign')
            || auth()->user()->can('legal.demands.review');
        $isManager = auth()->user()->can('legal.manager');
        $isAssignedField = auth()->user()->can('legal.demands.answer')
            && (string) $this->subdemand->assigned_to_user_id === (string) auth()->id();

        abort_unless($isController || $isManager || $isAssignedField, 403);
    }

    public function render()
    {
        return view('livewire.legal.controller.subdemand-detail');
    }
}
