<?php

namespace App\Http\Livewire\Legal\Controller;

use App\Enum\LegalDemandSubdemandStatus;
use App\Models\Company;
use App\Models\Note;
use App\Models\Legal\{LegalDemand, LegalDemandAssignment, LegalDemandFile, LegalDemandSubdemand, LegalExternalContact};
use App\Models\User;
use App\Services\Legal\{LegalDemandFileService, LegalDemandSubdemandWorkflowService, LegalDemandWorkflowService};
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
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

    public bool $assignAsExternal = false;
    public ?int $externalContactId = null;
    public string $externalContactName = '';
    public string $externalContactEmail = '';

    public string  $returnReason = '';

    public string  $closureReason = '';

    public string  $closureType = 'internal';

    public string  $externalProtocol = '';

    public ?string $externalClosedAt = null;

    // Comentário
    public string $newComment = '';

    public string $commentVisibility = 'controller';

    // Upload
    public $uploadFiles = [];
    public array $uploadNames = [];

    public string $fileVisibility = 'controller';

    // UI state
    public bool $showAssignForm = false;

    public bool $showCloseForm = false;

    public bool $showReturnForm = false;

    public string $noteInput = '';

    public string $noteLinkContext = '';
    public string $internalAction = '';

    // Subdemandas
    public bool $showSubdemandForm = false;
    public string $subdemandAssignedToUserId = '';
    public ?string $subdemandDeadlineAt = null;
    public string $subdemandDescription = '';
    public string $subdemandUserSearch = '';
    public string $subdemandCompanyFilter = '';
    public bool $subdemandAssignAsExternal = false;
    public ?int $subdemandExternalContactId = null;
    public string $subdemandExternalContactName = '';
    public string $subdemandExternalContactEmail = '';

    public bool $showSubdemandActionForm = false;
    public ?int $subdemandActionId = null;
    public string $subdemandActionToStatus = '';
    public string $subdemandActionReason = '';
    public string $subdemandActionDescription = '';
    public string $subdemandActionAssignedToUserId = '';
    public ?string $subdemandActionDeadlineAt = null;
    public array $subdemandInlineStatus = [];
    public array $subdemandControllerCommentInput = [];
    public array $subdemandExternalLinks = [];

    public function mount(string $uuid): void
    {
        abort_unless(
            auth()->user()->can('legal.demands.triage')
            || auth()->user()->can('legal.demands.assign')
            || auth()->user()->can('legal.demands.review'),
            403
        );

        $this->uuid   = $uuid;
        $this->demand = LegalDemand::where('uuid', $uuid)->with($this->demandRelations())->firstOrFail();
    }

    public function linkNotesToCase(): void
    {
        $this->validate([
            'noteInput' => 'required|string|min:1',
        ]);

        $tokens = collect(preg_split('/[\\s,;]+/', $this->noteInput))
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Informe ao menos um ID ou número de note válido.']);
            return;
        }

        $numericTokens = $tokens->filter(fn ($v) => ctype_digit($v))->map(fn ($v) => (int) $v)->values()->all();
        $stringTokens = $tokens->map(fn ($v) => (string) $v)->values()->all();

        $validIds = Note::query()
            ->where(function ($q) use ($numericTokens, $stringTokens) {
                if (!empty($numericTokens)) {
                    $q->orWhereIn('id', $numericTokens);
                }
                if (!empty($stringTokens)) {
                    $q->orWhereIn('note', $stringTokens);
                }
            })
            ->pluck('id')
            ->all();

        if (empty($validIds)) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Nenhuma note encontrada para os valores informados.']);
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

        $this->noteInput = '';
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
        $rules = [
            'assignDueAt' => 'nullable|date',
        ];

        if ($this->assignAsExternal) {
            $rules['assignDueAt'] = 'required|date';
            if ($this->externalContactId) {
                $rules['externalContactId'] = 'exists:legal_external_contacts,id';
            } else {
                $rules['externalContactName'] = 'required|string|min:3|max:120';
                $rules['externalContactEmail'] = 'required|email|max:190';
            }
        } else {
            $rules['assignToUserId'] = 'required_without:assignToTeamId';
        }

        $this->validate($rules);

        try {
            $externalMetadata = [];
            $toUserId = $this->assignToUserId ?: null;
            $toTeamId = $this->assignToTeamId ?: null;

            if ($this->assignAsExternal) {
                $contact = $this->resolveExternalContact();
                $externalMetadata = [
                    'external_dispatch' => true,
                    'external_contact_id' => $contact->id,
                    'external_contact_name' => $contact->name,
                    'external_contact_email' => $contact->email,
                ];
                $toUserId = null;
                $toTeamId = null;
            }

            $assignment = app(LegalDemandWorkflowService::class)->sendToField(
                $this->demand,
                auth()->user(),
                $toUserId,
                $toTeamId,
                $this->assignMessage ?: null,
                $this->assignDueAt ? new \DateTime($this->assignDueAt) : null,
                $this->assignAsExternal,
                $externalMetadata,
            );

            if ($this->assignAsExternal) {
                $expiresAt = Carbon::parse((string) $this->assignDueAt);
                $metadata = (array) ($assignment->metadata ?? []);
                $metadata['external_link_expires_at'] = $expiresAt->toDateTimeString();
                $metadata['external_link_generated_at'] = now()->toDateTimeString();
                $metadata['external_link_generated_by'] = auth()->id();
                $assignment->metadata = $metadata;
                $assignment->save();
            }

            $this->demand->refresh()->load(['legalCase', 'controller', 'currentAssignee', 'events.actor', 'files', 'comments.user', 'assignments.sentBy']);
            $this->showAssignForm = false;
            $this->assignAsExternal = false;
            $this->externalContactId = null;
            $this->externalContactName = '';
            $this->externalContactEmail = '';
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Responsável atribuído', 'timer' => 2500]);
        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => 'Erro', 'html' => $e->getMessage()]);
        }
    }

    public function setExternalContactName(string $value): void
    {
        $this->externalContactName = trim($value);
    }

    public function setExternalContactEmail(string $value): void
    {
        $this->externalContactEmail = mb_strtolower(trim($value));
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

    public function createSubdemand(): void
    {
        abort_unless(config('features.legal_subdemands', true), 404);
        if (!$this->canManageSubdemands()) {
            $this->dispatchBrowserEvent('swal', [
                'icon' => 'warning',
                'title' => 'Assuma a demanda primeiro',
                'html' => 'A criação de subdemanda exige que o controlador responsável seja você.',
            ]);
            return;
        }

        $this->validate([
            'subdemandDeadlineAt' => 'nullable|date',
            'subdemandDescription' => 'nullable|string|max:1000',
        ]);

        if (!$this->subdemandAssignAsExternal && $this->subdemandAssignedToUserId === '') {
            $this->addError('subdemandAssignedToUserId', 'Informe um executante interno ou marque despacho externo.');
            return;
        }

        try {
            $metadata = [];
            $assignedToUserId = $this->subdemandAssignedToUserId !== '' ? $this->subdemandAssignedToUserId : null;

            if ($this->subdemandAssignAsExternal) {
                if ($this->subdemandExternalContactId) {
                    $this->validate(['subdemandExternalContactId' => 'exists:legal_external_contacts,id']);
                    $contact = LegalExternalContact::query()->findOrFail($this->subdemandExternalContactId);
                } else {
                    $this->validate([
                        'subdemandExternalContactName' => 'required|string|min:3|max:120',
                        'subdemandExternalContactEmail' => 'required|email|max:190',
                    ]);

                    $contact = LegalExternalContact::query()->firstOrNew(['email' => mb_strtolower(trim($this->subdemandExternalContactEmail))]);
                    $contact->name = trim($this->subdemandExternalContactName);
                    $contact->is_active = true;
                    $contact->last_used_at = now();
                    if (!$contact->exists) {
                        $contact->created_by = auth()->id();
                    }
                    $contact->save();
                }

                $assignedToUserId = null;
                $metadata = [
                    'external_dispatch' => true,
                    'external_contact_id' => $contact->id,
                    'external_contact_name' => $contact->name,
                    'external_contact_email' => $contact->email,
                ];
            }

            $subdemand = app(LegalDemandSubdemandWorkflowService::class)->create(
                demand: $this->demand,
                actor: auth()->user(),
                assignedToUserId: $assignedToUserId,
                assignedAreaName: null,
                deadlineAt: $this->subdemandDeadlineAt ? new \DateTime($this->subdemandDeadlineAt) : null,
                description: $this->subdemandDescription ?: null,
                metadata: $metadata,
            );

            $externalLink = null;
            if ($this->subdemandAssignAsExternal) {
                $token = app(LegalDemandSubdemandWorkflowService::class)->generateExternalAccess(
                    $subdemand,
                    auth()->user(),
                    $this->subdemandDeadlineAt ? new \DateTime($this->subdemandDeadlineAt) : null
                );
                $externalLink = route('legal.external.subdemand.response', ['token' => $token]);
                $this->subdemandExternalLinks[$subdemand->id] = $externalLink;
            } else {
                LegalDemandAssignment::create([
                    'legal_demand_id' => $this->demand->id,
                    'from_user_id' => auth()->id(),
                    'to_user_id' => $assignedToUserId,
                    'to_team_id' => null,
                    'status' => 'sent',
                    'sent_at' => now(),
                    'message' => $this->subdemandDescription ?: 'Subdemanda enviada para execução.',
                    'metadata' => [
                        'subdemand_id' => $subdemand->id,
                        'due_at' => $this->subdemandDeadlineAt ? (new \DateTime($this->subdemandDeadlineAt))->format('Y-m-d H:i:s') : null,
                        'source' => 'subdemand',
                    ],
                ]);
            }

            $this->showSubdemandForm = false;
            $this->subdemandAssignedToUserId = '';
            $this->subdemandDeadlineAt = null;
            $this->subdemandDescription = '';
            $this->subdemandAssignAsExternal = false;
            $this->subdemandExternalContactId = null;
            $this->subdemandExternalContactName = '';
            $this->subdemandExternalContactEmail = '';
            $this->reloadDemand();
            $this->dispatchBrowserEvent('swal', [
                'icon' => 'success',
                'title' => 'Subdemanda criada',
                'html' => $externalLink ? ('Link externo: <a target=\"_blank\" href=\"' . e($externalLink) . '\">abrir</a>') : null,
                'timer' => 3200,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => 'Erro', 'html' => $e->getMessage()]);
        }
    }

    public function updatedSubdemandAssignAsExternal(bool $value): void
    {
        if ($value) {
            // Externo e interno são mutuamente exclusivos.
            $this->subdemandAssignedToUserId = '';
        } else {
            $this->subdemandExternalContactId = null;
            $this->subdemandExternalContactName = '';
            $this->subdemandExternalContactEmail = '';
        }
    }

    public function revokeSubdemandExternalAccess(int $subdemandId): void
    {
        abort_unless($this->canManageSubdemands(), 403);
        $sub = $this->demand->subdemands()->findOrFail($subdemandId);
        app(LegalDemandSubdemandWorkflowService::class)->revokeExternalAccess($sub, auth()->user(), 'Revogado pelo controlador.');
        $this->reloadDemand();
        $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Link externo revogado', 'timer' => 2000]);
    }

    public function regenerateSubdemandExternalAccess(int $subdemandId): void
    {
        abort_unless($this->canManageSubdemands(), 403);
        $sub = $this->demand->subdemands()->findOrFail($subdemandId);
        $token = app(LegalDemandSubdemandWorkflowService::class)->generateExternalAccess(
            $sub,
            auth()->user(),
            $sub->deadline_at
        );
        $link = route('legal.external.subdemand.response', ['token' => $token]);
        $this->subdemandExternalLinks[$sub->id] = $link;
        $this->reloadDemand();
        $this->dispatchBrowserEvent('swal', [
            'icon' => 'success',
            'title' => 'Link externo atualizado',
            'html' => 'Novo link: <a target=\"_blank\" href=\"' . e($link) . '\">abrir</a>',
        ]);
    }

    public function openSubdemandAction(int $subdemandId, string $toStatus): void
    {
        abort_unless($this->canManageSubdemands(), 403);
        $subdemand = $this->demand->subdemands()->findOrFail($subdemandId);

        $this->subdemandActionId = $subdemandId;
        $this->subdemandActionToStatus = $toStatus;
        $this->subdemandActionReason = '';
        $this->subdemandActionDescription = '';
        $this->subdemandActionAssignedToUserId = (string) ($subdemand->assigned_to_user_id ?? '');
        $this->subdemandActionDeadlineAt = $subdemand->deadline_at?->format('Y-m-d\TH:i');
        $this->showSubdemandActionForm = true;
    }

    public function removeSubdemand(int $subdemandId): void
    {
        abort_unless($this->canManageSubdemands(), 403);

        $subdemand = $this->demand->subdemands()->findOrFail($subdemandId);
        $status = $subdemand->status instanceof \BackedEnum ? $subdemand->status->value : (string) $subdemand->status;
        if (in_array($status, ['concluida', 'encerrada_controlador'], true)) {
            return;
        }

        app(LegalDemandSubdemandWorkflowService::class)->transitionStatus(
            subdemand: $subdemand,
            actor: auth()->user(),
            toStatus: LegalDemandSubdemandStatus::ENCERRADA_CONTROLADOR,
            reason: 'Subdemanda removida pelo controlador.',
            description: 'Subdemanda removida do fluxo ativo pelo controlador.'
        );

        $metadata = (array) ($subdemand->metadata ?? []);
        $metadata['removed_by_controller'] = true;
        $metadata['removed_at'] = now()->toDateTimeString();
        $metadata['removed_by'] = auth()->id();
        $subdemand->metadata = $metadata;
        $subdemand->save();

        // Encerrar também atribuições internas abertas vinculadas a esta subdemanda.
        $this->demand->assignments()
            ->whereJsonContains('metadata->subdemand_id', $subdemand->id)
            ->whereNotIn('status', ['cancelled', 'closed'])
            ->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);

        $this->reloadDemand();
        $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Subdemanda removida', 'timer' => 1800]);
    }

    public function applySubdemandAction(): void
    {
        abort_unless($this->canManageSubdemands(), 403);
        $this->validate([
            'subdemandActionId' => 'required|integer',
            'subdemandActionToStatus' => 'required|string',
            'subdemandActionReason' => 'nullable|string|max:1000',
            'subdemandActionDescription' => 'nullable|string|max:1000',
        ]);

        $subdemand = $this->demand->subdemands()->findOrFail((int) $this->subdemandActionId);

        try {
            app(LegalDemandSubdemandWorkflowService::class)->transitionStatus(
                subdemand: $subdemand,
                actor: auth()->user(),
                toStatus: LegalDemandSubdemandStatus::from($this->subdemandActionToStatus),
                reason: $this->subdemandActionReason ?: null,
                description: $this->subdemandActionDescription ?: null,
            );

            $this->showSubdemandActionForm = false;
            $this->subdemandActionId = null;
            $this->subdemandActionToStatus = '';
            $this->subdemandActionReason = '';
            $this->subdemandActionDescription = '';
            $this->subdemandActionAssignedToUserId = '';
            $this->subdemandActionDeadlineAt = null;
            $this->reloadDemand();
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Subdemanda atualizada', 'timer' => 2200]);
        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => 'Erro', 'html' => $e->getMessage()]);
        }
    }

    public function applySubdemandReassignment(): void
    {
        abort_unless($this->canManageSubdemands(), 403);
        $this->validate([
            'subdemandActionId' => 'required|integer',
            'subdemandActionAssignedToUserId' => 'nullable|string',
            'subdemandActionReason' => 'nullable|string|max:1000',
        ]);

        $subdemand = $this->demand->subdemands()->findOrFail((int) $this->subdemandActionId);

        app(LegalDemandSubdemandWorkflowService::class)->reassign(
            subdemand: $subdemand,
            actor: auth()->user(),
            assignedToUserId: $this->subdemandActionAssignedToUserId !== '' ? $this->subdemandActionAssignedToUserId : null,
            assignedAreaName: null,
            reason: $this->subdemandActionReason ?: null
        );

        $this->reloadDemand();
        $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Subdemanda reatribuída', 'timer' => 2200]);
    }

    public function applySubdemandDeadline(): void
    {
        abort_unless($this->canManageSubdemands(), 403);
        $this->validate([
            'subdemandActionId' => 'required|integer',
            'subdemandActionDeadlineAt' => 'nullable|date',
            'subdemandActionReason' => 'nullable|string|max:1000',
        ]);

        $subdemand = $this->demand->subdemands()->findOrFail((int) $this->subdemandActionId);

        app(LegalDemandSubdemandWorkflowService::class)->updateDeadline(
            subdemand: $subdemand,
            actor: auth()->user(),
            deadlineAt: $this->subdemandActionDeadlineAt ? new \DateTime($this->subdemandActionDeadlineAt) : null,
            reason: $this->subdemandActionReason ?: null
        );

        $this->reloadDemand();
        $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Prazo da subdemanda atualizado', 'timer' => 2200]);
    }

    public function applyInternalAction(): void
    {
        $action = trim($this->internalAction);
        if ($action === '') {
            return;
        }

        try {
            match ($action) {
                'start_triage' => app(LegalDemandWorkflowService::class)->startTriage($this->demand, auth()->user()),
                'approve_return' => app(LegalDemandWorkflowService::class)->approveFieldReturn($this->demand, auth()->user()),
                default => throw new \InvalidArgumentException('Ação de status não permitida neste contexto.'),
            };

            $this->internalAction = '';
            $this->reloadDemand();
            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Status interno atualizado', 'timer' => 2200]);
        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => 'Erro', 'html' => $e->getMessage()]);
        }
    }

    public function applyInlineSubdemandStatus(int $subdemandId): void
    {
        abort_unless($this->canManageSubdemands(), 403);
        $to = (string) ($this->subdemandInlineStatus[$subdemandId] ?? '');
        if ($to === '') {
            return;
        }

        $allowed = ['em_andamento', 'aguardando_retorno', 'encerrada_controlador'];
        if (!in_array($to, $allowed, true)) {
            return;
        }

        $subdemand = $this->demand->subdemands()->findOrFail($subdemandId);
        app(LegalDemandSubdemandWorkflowService::class)->transitionStatus(
            subdemand: $subdemand,
            actor: auth()->user(),
            toStatus: LegalDemandSubdemandStatus::from($to),
            reason: $to === 'encerrada_controlador' ? 'Encerrada via painel de subdemanda.' : null,
            description: 'Status alterado via seletor rápido.',
        );

        $this->reloadDemand();
        $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Status atualizado', 'timer' => 1800]);
    }

    public function addControllerSubdemandComment(int $subdemandId): void
    {
        abort_unless($this->canManageSubdemands(), 403);

        $comment = trim((string) ($this->subdemandControllerCommentInput[$subdemandId] ?? ''));
        if ($comment === '') {
            return;
        }

        $subdemand = $this->demand->subdemands()->findOrFail($subdemandId);
        $assignmentId = $this->demand->assignments()
            ->whereNotIn('status', ['cancelled', 'closed'])
            ->latest()
            ->first()?->id;

        \App\Models\Legal\LegalDemandComment::create([
            'legal_demand_id' => $this->demand->id,
            'assignment_id' => $assignmentId,
            'legal_demand_subdemand_id' => $subdemand->id,
            'user_id' => auth()->id(),
            'comment' => $comment,
            'visibility' => 'shared',
        ]);

        $this->subdemandControllerCommentInput[$subdemandId] = '';
        $this->reloadDemand();
        $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Comentário enviado', 'timer' => 1600]);
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
    }

    public function removeQueuedFile(int $index): void
    {
        if (!array_key_exists($index, $this->uploadFiles)) {
            return;
        }

        unset($this->uploadFiles[$index], $this->uploadNames[$index]);
        $this->uploadFiles = array_values($this->uploadFiles);
        $this->uploadNames = array_values($this->uploadNames);
    }

    public function saveQueuedFiles(): void
    {
        $this->validate([
            'uploadFiles' => 'required|array|min:1',
            'uploadFiles.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,docx,xlsx',
            'uploadNames' => 'array',
            'uploadNames.*' => 'nullable|string|max:190',
        ]);

        $assignmentId = $this->demand->assignments()->whereNotIn('status', ['cancelled', 'closed'])->first()?->id;

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

            $path = $file->storeAs("legal/demands/{$this->demand->id}", $customName, 'public');

            LegalDemandFile::create([
                'legal_demand_id' => $this->demand->id,
                'assignment_id' => $assignmentId,
                'uploaded_by' => auth()->id(),
                'file_name' => basename($path),
                'original_name' => $customName,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'visibility' => $this->fileVisibility,
            ]);
        }

        $this->uploadFiles = [];
        $this->uploadNames = [];
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
        $fieldUsersQuery = User::query()->with('Company')->orderBy('name');
        if (trim($this->subdemandUserSearch) !== '') {
            $s = '%' . trim($this->subdemandUserSearch) . '%';
            $fieldUsersQuery->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                    ->orWhere('email', 'like', $s);
            });
        }
        if (trim($this->subdemandCompanyFilter) !== '') {
            $fieldUsersQuery->where('company_id', $this->subdemandCompanyFilter);
        }
        $fieldUsers = $fieldUsersQuery->limit(120)->get();
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
            'externalContacts'   => LegalExternalContact::query()
                ->where('is_active', true)
                ->orderByDesc('last_used_at')
                ->orderBy('name')
                ->limit(100)
                ->get(),
            'currentAssignment'  => $currentAssignment,
            'currentAssignmentExternalLink' => $this->currentAssignmentExternalLink($currentAssignment),
            'currentAssignmentExternalExpiresAt' => $this->currentAssignmentExternalExpiresAt($currentAssignment),
            'statusValue'        => $statusValue,
            'isExternallyClosed' => $this->demand->isExternallyClosed(),
            'searchedNotes'      => $this->searchNotes(),
            'linkedNotes'        => $this->linkedNotes(),
            'subdemandStatuses'  => LegalDemandSubdemandStatus::cases(),
            'companies'          => Company::query()->orderBy('name')->get(['id', 'name']),
            'canManageSubdemands' => $this->canManageSubdemands(),
            'subdemandsFeatureEnabled' => (bool) config('features.legal_subdemands', true),
            'availableInternalActions' => $this->availableInternalActions(),
        ]);
    }

    private function reloadDemand(): void
    {
        $this->demand->refresh()->load($this->demandRelations());
    }

    private function demandRelations(): array
    {
        return [
            'legalCase.notes',
            'controller',
            'currentAssignee',
            'events.actor',
            'files.uploadedBy',
            'comments.user',
            'assignments.sentBy',
            'subdemands.assignedTo',
            'subdemands.createdBy',
            'subdemands.events.actor',
        ];
    }

    private function canManageSubdemands(): bool
    {
        return (string) ($this->demand->controller_user_id ?? '') !== ''
            && (string) $this->demand->controller_user_id === (string) auth()->id();
    }

    private function availableInternalActions(): array
    {
        $statusValue = $this->demand->internal_status instanceof \BackedEnum
            ? $this->demand->internal_status->value
            : (string) $this->demand->internal_status;

        return match ($statusValue) {
            'new_imported' => [
                ['value' => 'start_triage', 'label' => 'Assumir demanda (Iniciar triagem)'],
            ],
            'returned_by_field', 'under_controller_review' => [
                ['value' => 'approve_return', 'label' => 'Aprovar retorno (Pronta para fechamento externo)'],
            ],
            default => [],
        };
    }

    private function currentAssignmentExternalLink(?LegalDemandAssignment $assignment): ?string
    {
        if (!$assignment) {
            return null;
        }

        if (!data_get($assignment->metadata ?? [], 'external_dispatch')) {
            return null;
        }

        $expiresAt = $this->currentAssignmentExternalExpiresAt($assignment);
        if (!$expiresAt || $expiresAt->isPast()) {
            return null;
        }

        return URL::temporarySignedRoute(
            'legal.external.response',
            $expiresAt,
            ['assignment_id' => $assignment->id]
        );
    }

    private function currentAssignmentExternalExpiresAt(?LegalDemandAssignment $assignment): ?Carbon
    {
        if (!$assignment) {
            return null;
        }

        $raw = data_get($assignment->metadata ?? [], 'external_link_expires_at');
        if (!$raw) {
            return null;
        }

        try {
            return Carbon::parse((string) $raw);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveExternalContact(): LegalExternalContact
    {
        if ($this->externalContactId) {
            $contact = LegalExternalContact::query()->findOrFail($this->externalContactId);
            $contact->last_used_at = now();
            $contact->save();
            return $contact;
        }

        $email = mb_strtolower(trim($this->externalContactEmail));
        $name = trim($this->externalContactName);

        $contact = LegalExternalContact::query()->firstOrNew(['email' => $email]);
        $contact->name = $name;
        $contact->is_active = true;
        $contact->last_used_at = now();
        if (!$contact->exists) {
            $contact->created_by = auth()->id();
        }
        $contact->save();

        return $contact;
    }

    private function searchNotes()
    {
        $term = $this->currentNoteSearchTerm();
        if ($term === '') {
            return collect();
        }

        $q = Note::query()->select(['id', 'note', 'client', 'status', 'nstats', 'dt_status', 'dt_created']);

        if (ctype_digit($term)) {
            $q->where('id', (int) $term)
                ->orWhere('note', 'like', "%{$term}%");
        } else {
            $q->where('note', 'like', "%{$term}%")
                ->orWhere('client', 'like', "%{$term}%");
        }

        return $q->orderByDesc('id')->limit(10)->get();
    }

    private function currentNoteSearchTerm(): string
    {
        $raw = trim($this->noteInput);
        if ($raw === '') {
            return '';
        }

        $parts = preg_split('/[\\s,;]+/', $raw);
        if (empty($parts)) {
            return '';
        }

        return trim((string) end($parts));
    }

    private function linkedNotes()
    {
        $caseId = $this->demand->legal_case_id;
        if (!$caseId) {
            return collect();
        }

        return Note::query()
            ->join('legal_case_note as lcn', 'lcn.note_id', '=', 'notes.id')
            ->where('lcn.legal_case_id', $caseId)
            ->select([
                'notes.id',
                'notes.note',
                'notes.client',
                'notes.status',
                'notes.nstats',
                'notes.dt_status',
                'lcn.linked_at as pivot_linked_at',
                'lcn.context as pivot_context',
            ])
            ->orderByDesc(DB::raw('COALESCE(lcn.linked_at, lcn.created_at)'))
            ->get();
    }
}
