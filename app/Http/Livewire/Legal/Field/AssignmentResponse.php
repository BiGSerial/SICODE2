<?php

namespace App\Http\Livewire\Legal\Field;

use App\Enum\LegalDemandSubdemandStatus;
use App\Models\Legal\{LegalDemand, LegalDemandAssignment, LegalDemandComment, LegalDemandFile, LegalDemandSubdemand};
use App\Notifications\SystemNotification;
use App\Services\Legal\LegalDemandWorkflowService;
use App\Support\Notifications\UserNotificationData;
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
    public $uploadFiles = [];
    public array $uploadNames = [];

    public string $fileVisibility = 'shared';

    // Comentário adicional
    public string $internalNote = '';

    // UI
    public bool $confirmingSend = false;
    public ?int $activeSubdemandId = null;
    public array $subdemandCommentInput = [];

    public function mount(string $uuid, bool $external = false): void
    {
        $this->externalAccess = $external;

        if (!$this->externalAccess) {
            abort_unless(auth()->user()->can('legal.demands.answer'), 403);
        }

        $assignment = LegalDemandAssignment::with([
            'legalDemand.legalCase.adverseParties',
            'legalDemand.files.legalDemand',
            'legalDemand.comments.user',
            'legalDemand.subdemands.assignedTo',
            'legalDemand.subdemands.events.actor',
            'sentBy',
            'toUser',
        ])
            ->where('uuid', $uuid);

        if (!$this->externalAccess) {
            $assignment->where('to_user_id', auth()->id());
        }

        $assignment = $assignment->first();

        if (!$assignment) {
            session()->flash('warning', 'A tarefa informada não está mais disponível.');
            redirect()->route($this->externalAccess ? 'legal.external.expired' : 'legal.field.queue');
            return;
        }

        $this->assignment   = $assignment;
        $this->assignmentId = $assignment->id;

        $this->demand = $this->assignment->legalDemand;
        $assignmentSubdemandId = (int) data_get($this->assignment->metadata ?? [], 'subdemand_id', 0);
        $active = $assignmentSubdemandId > 0
            ? $this->demand->subdemands->firstWhere('id', $assignmentSubdemandId)
            : $this->demand->subdemands->firstWhere('assigned_to_user_id', $this->externalAccess ? null : auth()->id());
        $this->activeSubdemandId = $active?->id;

        if ($this->externalAccess) {
            $this->externalExecutorName = (string) (
                data_get($this->assignment->metadata ?? [], 'external_contact_name')
                ?: data_get($this->assignment->metadata ?? [], 'external_executor_name')
                ?: ''
            );
        }
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

        $requiresEvidence = (bool) data_get($this->assignment->metadata ?? [], 'requires_evidence', false);
        if ($requiresEvidence && !$this->hasEvidenceAttached()) {
            $this->addError('evidence', 'O controlador exige ao menos um arquivo de evidência para esta tarefa.');
            $this->confirmingSend = false;
            return;
        }

        $this->confirmingSend = true;
    }

    public function cancelConfirm(): void
    {
        $this->confirmingSend = false;
    }

    public function updatedUploadFiles(): void
    {
        if (!is_array($this->uploadFiles)) {
            $this->uploadFiles = [$this->uploadFiles];
        }

        $this->validate([
            'uploadFiles.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,docx,xlsx',
        ]);

        foreach ($this->uploadFiles as $index => $file) {
            if (!isset($this->uploadNames[$index]) || trim((string) $this->uploadNames[$index]) === '') {
                $this->uploadNames[$index] = (string) $file->getClientOriginalName();
            }
        }

        $this->hasEvidence = !empty($this->uploadFiles);
    }

    public function removeUploadFile(int $index): void
    {
        if (!isset($this->uploadFiles[$index])) {
            return;
        }

        unset($this->uploadFiles[$index]);
        unset($this->uploadNames[$index]);
        $this->uploadFiles = array_values($this->uploadFiles);
        $this->uploadNames = array_values($this->uploadNames);
        $this->hasEvidence = !empty($this->uploadFiles);
    }

    public function saveFilesToTask(): void
    {
        if (!$this->activeSubdemandAcceptsInput()) {
            return;
        }

        $this->validate([
            'uploadFiles' => 'required|array|min:1',
            'uploadFiles.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,docx,xlsx',
            'uploadNames' => 'array',
            'uploadNames.*' => 'nullable|string|max:190',
        ]);

        $this->persistUploadedFiles();

        $this->uploadFiles = [];
        $this->uploadNames = [];
        $this->hasEvidence = false;
        $this->demand->refresh()->load('files');

        $this->dispatchBrowserEvent('swal', [
            'icon' => 'success',
            'title' => 'Arquivos salvos na tarefa',
            'timer' => 1800,
        ]);
    }

    public function submitResponse(): void
    {
        if (!$this->activeSubdemandAcceptsInput()) {
            return;
        }

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

        // Notifica o controlador responsável (apenas usuários internos)
        $controller = $this->demand->controller;
        if ($controller && !$this->externalAccess) {
            $executorName = auth()->user()->name;
            $caseNumber   = $this->demand->source_case_number ?? $this->demand->id;
            try {
                $controller->notify(new SystemNotification(new UserNotificationData(
                    title:   "Demanda #{$caseNumber} — Executante respondeu",
                    message: "{$executorName} enviou " . ($this->isImpossibility ? 'uma impossibilidade de atendimento' : 'o retorno da tarefa') . ". Verifique e decida se aprova ou devolve.",
                    status:  'info',
                )));
            } catch (\Throwable) {}
        }

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
        $this->demand->loadMissing([
            'subdemands.assignedTo',
            'subdemands.events.actor',
            'comments.user',
            'files.legalDemand',
        ]);

        $sharedFiles = $this->demand->files
            ->where('removed_at', null)
            ->where('visibility', 'shared');



        return view('livewire.legal.field.assignment-response', [
            'sharedFiles' => $sharedFiles,
        ]);
    }

    public function addSubdemandComment(int $subdemandId): void
    {
        if (!$this->activeSubdemandAcceptsInput($subdemandId)) {
            return;
        }

        $comment = trim((string) ($this->subdemandCommentInput[$subdemandId] ?? ''));
        if ($comment === '') {
            return;
        }

        if (!$this->externalAccess) {
            $allowed = $this->demand->subdemands
                ->where('id', $subdemandId)
                ->where('assigned_to_user_id', auth()->id())
                ->isNotEmpty();
            abort_unless($allowed, 403);
        }

        LegalDemandComment::create([
            'legal_demand_id' => $this->demand->id,
            'assignment_id' => $this->assignment->id,
            'legal_demand_subdemand_id' => $subdemandId,
            'user_id' => $this->externalAccess ? null : auth()->id(),
            'comment' => $comment,
            'visibility' => 'shared',
        ]);

        $this->subdemandCommentInput[$subdemandId] = '';
        $this->demand->refresh()->load(['comments.user', 'subdemands.assignedTo', 'subdemands.events.actor']);
    }

    private function hasEvidenceAttached(): bool
    {
        if (!empty($this->uploadFiles)) {
            return true;
        }

        return $this->demand->files
            ->where('removed_at', null)
            ->where('assignment_id', $this->assignment->id)
            ->isNotEmpty();
    }

    private function persistUploadedFiles(): int
    {
        if (empty($this->uploadFiles)) {
            return 0;
        }

        $count = 0;
        foreach ($this->uploadFiles as $index => $file) {
            $customName = trim((string) ($this->uploadNames[$index] ?? ''));
            $originalName = (string) $file->getClientOriginalName();
            if ($customName === '') {
                $customName = $originalName;
            }

            $customName = preg_replace('/[\\\\\\/]+/', '-', $customName) ?: $originalName;
            $extension = strtolower((string) $file->getClientOriginalExtension());
            if ($extension !== '' && !str_ends_with(strtolower($customName), '.' . $extension)) {
                $customName .= '.' . $extension;
            }

            $folder = $this->externalAccess
                ? "legal/demands/{$this->demand->id}/external"
                : "legal/demands/{$this->demand->id}";
            $path = $file->storeAs($folder, $customName, 'public');

            LegalDemandFile::create([
                'legal_demand_id' => $this->demand->id,
                'assignment_id' => $this->assignment->id,
                'legal_demand_subdemand_id' => $this->activeSubdemandId,
                'uploaded_by' => $this->externalAccess ? null : auth()->id(),
                'file_name' => basename($path),
                'original_name' => $customName,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'visibility' => 'shared',
            ]);

            $count++;
        }

        return $count;
    }

    private function activeSubdemandAcceptsInput(?int $subdemandId = null): bool
    {
        $subdemandId ??= $this->activeSubdemandId;
        if ($subdemandId === null) {
            return true;
        }

        $subdemand = LegalDemandSubdemand::query()->find($subdemandId);
        if (!$subdemand) {
            return true;
        }

        $status = $subdemand->status instanceof LegalDemandSubdemandStatus
            ? $subdemand->status
            : LegalDemandSubdemandStatus::from((string) $subdemand->status);

        if (!in_array($status, [LegalDemandSubdemandStatus::CONCLUIDA, LegalDemandSubdemandStatus::ENCERRADA_CONTROLADOR], true)) {
            return true;
        }

        $this->dispatchBrowserEvent('swal', [
            'icon' => 'warning',
            'title' => 'Subdemanda encerrada',
            'html' => 'Esta subdemanda já foi encerrada e não aceita novos comentários, arquivos ou respostas.',
        ]);

        return false;
    }
}
