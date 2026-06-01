<?php

namespace App\Http\Livewire\Legal\Controller;

use App\Models\Legal\{LegalDemandSubdemand, LegalDemandAssignment};
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\Legal\LegalDemandWorkflowService;
use App\Support\Notifications\UserNotificationData;
use Livewire\Component;

class SubdemandDetail extends Component
{
    public string $uuid;
    public LegalDemandSubdemand $subdemand;

    public bool $showReturnForm = false;
    public string $returnReason = '';
    public bool $showApproveConfirm = false;

    public function mount(string $uuid): void
    {
        abort_unless(config('features.legal_subdemands', true), 404);

        $this->uuid = $uuid;
        $subdemand = LegalDemandSubdemand::query()
            ->with([
                'demand.legalCase',
                'assignedTo',
                'createdBy',
                'events.actor',
                'comments.user',
                'files.uploadedBy',
            ])
            ->where('uuid', $uuid)
            ->first();

        abort_if(is_null($subdemand), 404, 'Subdemanda não encontrada.');

        $this->subdemand = $subdemand;

        $isController = auth()->user()->can('legal.demands.triage')
            || auth()->user()->can('legal.demands.assign')
            || auth()->user()->can('legal.demands.review');
        $isManager    = auth()->user()->can('legal.manager');
        $isAssignedField = auth()->user()->can('legal.demands.answer')
            && (string) $this->subdemand->assigned_to_user_id === (string) auth()->id();

        abort_unless($isController || $isManager || $isAssignedField, 403);
    }

    public function approveReturn(): void
    {
        $assignment = $this->latestAnsweredAssignment();
        abort_if(is_null($assignment), 422, 'Nenhuma resposta para aprovar.');

        app(LegalDemandWorkflowService::class)->approveFieldReturn(
            $this->subdemand->demand,
            auth()->user()
        );

        // Notifica executante interno
        if ($assignment->to_user_id) {
            $this->notifyUser(
                User::find($assignment->to_user_id),
                'Retorno aprovado pelo controlador',
                'Sua resposta na subdemanda #' . $this->subdemand->id . ' foi aprovada por ' . auth()->user()->name . '.',
                'success'
            );
        }

        $this->showApproveConfirm = false;
        $this->reload();
        $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Retorno aprovado', 'timer' => 2200]);
    }

    public function returnForCorrection(): void
    {
        $this->validate(['returnReason' => 'required|min:10']);

        $assignment = $this->latestAnsweredAssignment();
        abort_if(is_null($assignment), 422, 'Nenhuma resposta para devolver.');

        app(LegalDemandWorkflowService::class)->requestCorrection(
            $assignment,
            auth()->user(),
            $this->returnReason
        );

        // Notifica executante interno
        if ($assignment->to_user_id) {
            $this->notifyUser(
                User::find($assignment->to_user_id),
                'Subdemanda devolvida para correção',
                'O controlador ' . auth()->user()->name . ' devolveu a subdemanda #' . $this->subdemand->id . '. Motivo: ' . $this->returnReason,
                'warning'
            );
        }

        $this->showReturnForm = false;
        $this->returnReason   = '';
        $this->reload();
        $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Devolvido para correção', 'timer' => 2200]);
    }

    public function render()
    {
        return view('livewire.legal.controller.subdemand-detail', [
            'answeredAssignment' => $this->latestAnsweredAssignment(),
            'isController' => auth()->user()->can('legal.demands.triage')
                || auth()->user()->can('legal.demands.assign')
                || auth()->user()->can('legal.demands.review'),
        ]);
    }

    private function latestAnsweredAssignment(): ?LegalDemandAssignment
    {
        return $this->subdemand->demand
            ->assignments()
            ->whereJsonContains('metadata->subdemand_id', $this->subdemand->id)
            ->where('status', 'answered')
            ->latest()
            ->first();
    }

    private function reload(): void
    {
        $this->subdemand->refresh()->load([
            'demand.legalCase', 'assignedTo', 'createdBy',
            'events.actor', 'comments.user', 'files.uploadedBy',
        ]);
    }

    private function notifyUser(?User $user, string $title, string $message, string $status = 'info'): void
    {
        if (!$user) {
            return;
        }
        try {
            $user->notify(new SystemNotification(new UserNotificationData(
                title:   $title,
                message: $message,
                status:  $status,
            )));
        } catch (\Throwable) {}
    }
}
