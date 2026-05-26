<?php

namespace App\Http\Livewire\Legal\Controller;

use App\Models\Note;
use App\Models\Legal\{LegalDemand};
use App\Models\User;
use App\Services\Legal\{LegalDemandFileService, LegalDemandWorkflowService};
use Livewire\{Component, WithFileUploads};

class DemandDetail extends Component
{
    use WithFileUploads;

    public string $uuid;

    public LegalDemand $demand;

    // Painel de Ação
    public string  $assignToUserId = '';

    public string  $assignToTeamId = '';

    public string  $assignMessage = '';

    public ?string $assignDueAt = null;

    public string  $returnReason = '';

    public string  $closureReason = '';

    public string  $closureType = 'internal';

    public string  $externalProtocol = '';

    public ?string $externalClosedAt = null;

    // Comentário
    public string $newComment = '';

    public string $commentVisibility = 'controller';

    // Upload
    public $uploadFile = null;

    public string $fileVisibility = 'controller';

    // UI state
    public bool $showAssignForm = false;

    public bool $showCloseForm = false;

    public bool $showReturnForm = false;

    public string $noteIdsInput = '';

    public string $noteLinkContext = '';

    public string $noteSearch = '';

    public function mount(string $uuid): void
    {
        abort_unless(
            auth()->user()->can('legal.demands.triage')
            || auth()->user()->can('legal.demands.assign')
            || auth()->user()->can('legal.demands.review'),
            403
        );

        $this->uuid   = $uuid;
        $this->demand = LegalDemand::where('uuid', $uuid)
            ->with(['legalCase.notes', 'controller', 'currentAssignee', 'events.actor', 'files', 'comments.user', 'assignments.sentBy'])
            ->firstOrFail();
    }

    public function linkNotesToCase(): void
    {
        $this->validate([
            'noteIdsInput' => 'required|string|min:1',
        ]);

        $ids = collect(preg_split('/[\\s,;]+/', $this->noteIdsInput))
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '' && ctype_digit($v))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Informe IDs válidos de notes.']);
            return;
        }

        $validIds = Note::query()->whereIn('id', $ids->all())->pluck('id')->all();
        if (empty($validIds)) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Nenhuma note encontrada para os IDs informados.']);
            return;
        }

        $payload = [];
        foreach ($validIds as $id) {
            $payload[$id] = [
                'linked_by' => auth()->id(),
                'linked_at' => now(),
                'context' => $this->noteLinkContext ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->demand->legalCase->notes()->syncWithoutDetaching($payload);

        $this->noteIdsInput = '';
        $this->noteLinkContext = '';
        $this->demand->refresh()->load(['legalCase.notes', 'events.actor', 'files', 'comments.user', 'assignments.sentBy']);

        $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Notes vinculadas ao processo.']);
    }

    public function unlinkNoteFromCase(int $noteId): void
    {
        $this->demand->legalCase->notes()->detach($noteId);
        $this->demand->refresh()->load(['legalCase.notes']);
        $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Note desvinculada do processo.']);
    }

    public function attachSingleNote(int $noteId): void
    {
        $note = Note::query()->find($noteId);
        if (!$note) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Note não encontrada.']);
            return;
        }

        $this->demand->legalCase->notes()->syncWithoutDetaching([
            $note->id => [
                'linked_by' => auth()->id(),
                'linked_at' => now(),
                'context' => $this->noteLinkContext ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->demand->refresh()->load(['legalCase.notes']);
        $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Note associada ao processo.']);
    }

    public function startTriage(): void
    {
        try {
            app(LegalDemandWorkflowService::class)->startTriage($this->demand, auth()->user());
            $this->demand->refresh()->load(['legalCase', 'controller', 'currentAssignee', 'events.actor', 'files', 'comments.user', 'assignments.sentBy']);
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Triagem iniciada', 'timer' => 2500]);
        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => 'Ação não permitida', 'html' => $e->getMessage()]);
        }
    }

    public function sendToField(): void
    {
        $this->validate([
            'assignToUserId' => 'required_without:assignToTeamId',
        ]);

        try {
            app(LegalDemandWorkflowService::class)->sendToField(
                $this->demand,
                auth()->user(),
                $this->assignToUserId ?: null,
                $this->assignToTeamId ?: null,
                $this->assignMessage ?: null,
                $this->assignDueAt ? new \DateTime($this->assignDueAt) : null,
            );
            $this->demand->refresh()->load(['legalCase', 'controller', 'currentAssignee', 'events.actor', 'files', 'comments.user', 'assignments.sentBy']);
            $this->showAssignForm = false;
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Enviado para o campo', 'timer' => 2500]);
        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => 'Erro', 'html' => $e->getMessage()]);
        }
    }

    public function approveReturn(): void
    {
        try {
            app(LegalDemandWorkflowService::class)->approveFieldReturn($this->demand, auth()->user());
            $this->demand->refresh()->load(['events.actor', 'files', 'comments.user']);
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Retorno aprovado', 'timer' => 2500]);
        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => 'Erro', 'html' => $e->getMessage()]);
        }
    }

    public function returnForCorrection(): void
    {
        $this->validate(['returnReason' => 'required|min:10']);

        try {
            $assignment = $this->demand->assignments()
                ->whereNotIn('status', ['cancelled', 'closed'])
                ->latest()
                ->firstOrFail();

            app(LegalDemandWorkflowService::class)->requestCorrection($assignment, auth()->user(), $this->returnReason);
            $this->demand->refresh()->load(['events.actor', 'files', 'comments.user', 'assignments.sentBy']);
            $this->showReturnForm = false;
            $this->returnReason   = '';
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Devolvido para correção', 'timer' => 2500]);
        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => 'Erro', 'html' => $e->getMessage()]);
        }
    }

    public function closeDemand(): void
    {
        $this->validate(['closureReason' => 'required|min:10']);

        try {
            if ($this->closureType === 'external') {
                $this->validate(['externalProtocol' => 'required']);
                app(LegalDemandWorkflowService::class)->closeExternal(
                    $this->demand,
                    auth()->user(),
                    $this->externalProtocol,
                    $this->closureReason
                );
            } else {
                app(LegalDemandWorkflowService::class)->closeInternal($this->demand, auth()->user(), $this->closureReason);
            }
            $this->demand->refresh()->load(['events.actor', 'files', 'comments.user']);
            $this->showCloseForm = false;
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Demanda fechada', 'timer' => 2500]);
        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => 'Erro', 'html' => $e->getMessage()]);
        }
    }

    public function reopen(): void
    {
        try {
            app(LegalDemandWorkflowService::class)->reopen($this->demand, auth()->user(), 'Reaberta pelo controlador.');
            $this->demand->refresh()->load(['events.actor']);
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Demanda reaberta', 'timer' => 2500]);
        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => 'Erro', 'html' => $e->getMessage()]);
        }
    }

    public function addComment(): void
    {
        $this->validate(['newComment' => 'required|min:3']);

        app(LegalDemandWorkflowService::class)->addComment(
            $this->demand,
            auth()->user(),
            $this->newComment,
            null,
            $this->commentVisibility,
        );

        $this->newComment = '';
        $this->demand->refresh()->load(['comments.user']);
    }

    public function uploadFile(): void
    {
        $this->validate(['uploadFile' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,docx,xlsx']);

        app(LegalDemandFileService::class)->store(
            demand:       $this->demand,
            file:         $this->uploadFile,
            uploadedBy:   auth()->user(),
            visibility:   $this->fileVisibility,
            assignmentId: $this->demand->assignments()->whereNotIn('status', ['cancelled', 'closed'])->first()?->id,
        );

        $this->uploadFile = null;
        $this->demand->refresh()->load(['files']);
    }

    public function removeFile(int $fileId): void
    {
        $file = $this->demand->files()->findOrFail($fileId);
        abort_unless($file->uploaded_by_id === auth()->id(), 403);

        $file->update(['removed_at' => now()]);
        $this->demand->refresh()->load(['files']);
    }

    public function render()
    {
        $fieldUsers        = User::orderBy('name')->get();
        $currentAssignment = $this->demand->assignments()
            ->whereNotIn('status', ['cancelled', 'closed'])
            ->with(['sentBy', 'toUser'])
            ->latest()
            ->first();

        $statusValue = $this->demand->internal_status instanceof \BackedEnum
            ? $this->demand->internal_status->value
            : $this->demand->internal_status;

        return view('livewire.legal.controller.demand-detail', [
            'fieldUsers'         => $fieldUsers,
            'currentAssignment'  => $currentAssignment,
            'statusValue'        => $statusValue,
            'isExternallyClosed' => $this->demand->isExternallyClosed(),
            'searchedNotes'      => $this->searchNotes(),
        ]);
    }

    private function searchNotes()
    {
        $term = trim($this->noteSearch);
        if ($term === '') {
            return collect();
        }

        $q = Note::query()->select(['id', 'note', 'client', 'status', 'dt_created']);

        if (ctype_digit($term)) {
            $q->where('id', (int) $term)
                ->orWhere('note', 'like', "%{$term}%");
        } else {
            $q->where('note', 'like', "%{$term}%")
                ->orWhere('client', 'like', "%{$term}%");
        }

        return $q->orderByDesc('id')->limit(10)->get();
    }
}
