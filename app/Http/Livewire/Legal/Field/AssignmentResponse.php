<?php

namespace App\Http\Livewire\Legal\Field;

use App\Models\Legal\{LegalDemand, LegalDemandAssignment};
use App\Services\Legal\{LegalDemandFileService, LegalDemandWorkflowService};
use Livewire\{Component, WithFileUploads};

class AssignmentResponse extends Component
{
    use WithFileUploads;

    public int                    $assignmentId;

    public LegalDemandAssignment  $assignment;

    public LegalDemand            $demand;

    // Formulário de resposta
    public string $responseSummary = '';

    public bool   $hasEvidence = false;

    public string $impossibilityReason = '';

    public bool   $isImpossibility = false;

    // Upload de evidências (múltiplos)
    public array  $uploadFiles = [];

    public string $fileVisibility = 'shared';

    // Comentário adicional
    public string $internalNote = '';

    // UI
    public bool $confirmingSend = false;

    public function mount(int $assignment_id): void
    {
        abort_unless(auth()->user()->can('legal.demands.answer'), 403);

        $this->assignmentId = $assignment_id;
        $this->assignment   = LegalDemandAssignment::with(['legalDemand.legalCase', 'legalDemand.files', 'sentBy'])
            ->findOrFail($assignment_id);

        abort_unless($this->assignment->to_user_id === auth()->id(), 403);

        $this->demand = $this->assignment->legalDemand;
    }

    public function markInProgress(): void
    {
        $status = $this->assignment->status instanceof \BackedEnum
            ? $this->assignment->status->value
            : $this->assignment->status;

        if ($status === 'received') {
            $this->assignment->update(['status' => 'in_progress']);
            $this->assignment->refresh();
        }
    }

    public function startConfirm(): void
    {
        if ($this->isImpossibility) {
            $this->validate(['impossibilityReason' => 'required|min:20']);
        } else {
            $this->validate([
                'responseSummary' => 'required|min:20',
            ]);
        }

        $this->confirmingSend = true;
    }

    public function cancelConfirm(): void
    {
        $this->confirmingSend = false;
    }

    public function submitResponse(): void
    {
        if ($this->isImpossibility) {
            $this->validate(['impossibilityReason' => 'required|min:20']);
        } else {
            $this->validate(['responseSummary' => 'required|min:20']);
        }

        $fileService = app(LegalDemandFileService::class);

        foreach ($this->uploadFiles as $file) {
            $fileService->store(
                demand:       $this->demand,
                file:         $file,
                uploadedBy:   auth()->user(),
                visibility:   'shared',
                assignmentId: $this->assignment->id,
            );
        }

        app(LegalDemandWorkflowService::class)->answerFromField(
            assignment:        $this->assignment,
            actor:             auth()->user(),
            responseSummary:   $this->isImpossibility ? null : $this->responseSummary,
            hasEvidence:       !empty($this->uploadFiles),
            impossibilityReason: $this->isImpossibility ? $this->impossibilityReason : null,
        );

        $this->confirmingSend = false;

        session()->flash('success', 'Resposta enviada com sucesso!');

        redirect()->route('legal.field.queue');
    }

    public function saveDraft(): void
    {
        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'info',
            'title' => 'Rascunho salvo',
            'timer' => 1500,
        ]);
    }

    public function render()
    {
        $sharedFiles = $this->demand->files
            ->where('removed_at', null)
            ->where('visibility', 'shared');

        $demandEvents = $this->demand->events()
            ->whereJsonContains('metadata->assignment_id', $this->assignment->id)
            ->orWhere('event_type', 'sent_to_field')
            ->get();

        return view('livewire.legal.field.assignment-response', [
            'sharedFiles' => $sharedFiles,
        ]);
    }
}
