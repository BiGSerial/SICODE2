<?php

namespace App\Http\Livewire\Legal\Field;

use App\Models\Legal\{LegalDemand, LegalDemandAssignment, LegalDemandFile};
use App\Services\Legal\{LegalDemandFileService, LegalDemandWorkflowService};
use Livewire\{Component, WithFileUploads};

class AssignmentResponse extends Component
{
    use WithFileUploads;

    public int                    $assignmentId;

    public LegalDemandAssignment  $assignment;

    public LegalDemand            $demand;

    public bool $externalAccess = false;

    public string $externalExecutorName = '';

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
    public bool $confirmingFileSave = false;

    public function mount(int $assignment_id, bool $external = false): void
    {
        $this->externalAccess = $external;

        if (!$this->externalAccess) {
            abort_unless(auth()->user()->can('legal.demands.answer'), 403);
        }

        $this->assignmentId = $assignment_id;
        $assignment = LegalDemandAssignment::with(['legalDemand.legalCase', 'legalDemand.files', 'sentBy', 'toUser'])
            ->whereKey($assignment_id);

        if (!$this->externalAccess) {
            $assignment->where('to_user_id', auth()->id());
        }

        $assignment = $assignment
            ->first();

        if (!$assignment) {
            session()->flash('warning', 'A tarefa informada não está mais disponível.');
            redirect()->route($this->externalAccess ? 'legal.external.expired' : 'legal.field.queue');
            return;
        }

        $this->assignment = $assignment;

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
        if ($this->externalAccess) {
            $this->validate([
                'externalExecutorName' => 'required|string|min:3|max:120',
            ]);
        }

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

    public function updatedUploadFiles(): void
    {
        $this->hasEvidence = !empty($this->uploadFiles);
    }

    public function removeUploadFile(int $index): void
    {
        if (!isset($this->uploadFiles[$index])) {
            return;
        }

        unset($this->uploadFiles[$index]);
        $this->uploadFiles = array_values($this->uploadFiles);
        $this->hasEvidence = !empty($this->uploadFiles);
    }

    public function startSaveFilesConfirm(): void
    {
        $this->validate([
            'uploadFiles' => 'required|array|min:1',
            'uploadFiles.*' => 'file|max:10240',
        ]);

        $this->confirmingFileSave = true;
    }

    public function cancelSaveFilesConfirm(): void
    {
        $this->confirmingFileSave = false;
    }

    public function saveFilesToTask(): void
    {
        $this->validate([
            'uploadFiles' => 'required|array|min:1',
            'uploadFiles.*' => 'file|max:10240',
        ]);

        $this->persistUploadedFiles();

        $this->uploadFiles = [];
        $this->hasEvidence = false;
        $this->confirmingFileSave = false;
        $this->demand->refresh()->load('files');

        $this->dispatchBrowserEvent('swal', [
            'icon' => 'success',
            'title' => 'Arquivos salvos na tarefa',
            'timer' => 1800,
        ]);
    }

    public function submitResponse(): void
    {
        if ($this->externalAccess) {
            $this->validate([
                'externalExecutorName' => 'required|string|min:3|max:120',
            ]);
        }

        if ($this->isImpossibility) {
            $this->validate(['impossibilityReason' => 'required|min:20']);
        } else {
            $this->validate(['responseSummary' => 'required|min:20']);
        }

        $uploadedCount = $this->persistUploadedFiles();

        if ($this->externalAccess) {
            app(LegalDemandWorkflowService::class)->answerFromExternal(
                assignment:          $this->assignment,
                externalExecutorName: $this->externalExecutorName,
                responseSummary:     $this->isImpossibility ? null : $this->responseSummary,
                hasEvidence:         $uploadedCount > 0,
                impossibilityReason: $this->isImpossibility ? $this->impossibilityReason : null,
            );
        } else {
            app(LegalDemandWorkflowService::class)->answerFromField(
                assignment:        $this->assignment,
                actor:             auth()->user(),
                responseSummary:   $this->isImpossibility ? null : $this->responseSummary,
                hasEvidence:       $uploadedCount > 0,
                impossibilityReason: $this->isImpossibility ? $this->impossibilityReason : null,
            );
        }

        $this->confirmingSend = false;

        session()->flash('success', 'Resposta enviada com sucesso!');

        redirect()->route($this->externalAccess ? 'legal.external.expired' : 'legal.field.queue');
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

    private function persistUploadedFiles(): int
    {
        if (empty($this->uploadFiles)) {
            return 0;
        }

        $count = 0;
        foreach ($this->uploadFiles as $file) {
            if ($this->externalAccess) {
                $path = $file->store("legal/demands/{$this->demand->id}/external", 'public');

                LegalDemandFile::create([
                    'legal_demand_id' => $this->demand->id,
                    'assignment_id' => $this->assignment->id,
                    'uploaded_by' => null,
                    'file_name' => $file->hashName(),
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'visibility' => 'shared',
                ]);
            } else {
                app(LegalDemandFileService::class)->store(
                    demand:       $this->demand,
                    file:         $file,
                    uploadedBy:   auth()->user(),
                    visibility:   'shared',
                    assignmentId: $this->assignment->id,
                );
            }

            $count++;
        }

        return $count;
    }
}
