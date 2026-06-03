<?php

namespace App\Services\Legal;

use App\Enum\LegalDemandAssignmentStatus;
use App\Enum\LegalDemandInternalStatus;
use App\Models\Legal\LegalDemand;
use App\Models\Legal\LegalDemandAssignment;
use App\Models\Legal\LegalDemandComment;
use App\Models\Legal\LegalDemandEvent;
use App\Models\Legal\LegalDemandSubdemand;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LegalDemandWorkflowService
{
    public function startTriage(LegalDemand $demand, User $actor): LegalDemand
    {
        $this->assertNotClosed($demand);
        $this->ensureAllowed($actor, 'legal.demands.triage');
        $this->assertTransition($demand, [LegalDemandInternalStatus::NEW_IMPORTED, LegalDemandInternalStatus::TRIAGE], 'triage');

        return DB::transaction(function () use ($demand, $actor) {
            $from = $demand->internal_status?->value;
            $demand->internal_status = LegalDemandInternalStatus::TRIAGE;
            $demand->controller_user_id = $actor->id;
            $demand->save();

            $this->event($demand->id, 'triage_started', $from, LegalDemandInternalStatus::TRIAGE->value, $actor->id, null, null, 'Demanda assumida.');
            return $demand->refresh();
        });
    }

    public function sendToField(
        LegalDemand $demand,
        User $actor,
        ?string $toUserId,
        ?string $toTeamId,
        ?string $message,
        ?\DateTimeInterface $dueAt,
        bool $externalDispatch = false,
        array $extraMetadata = []
    ): LegalDemandAssignment
    {
        $this->assertNotClosed($demand);
        $this->ensureAllowed($actor, 'legal.demands.assign');

        if (!$externalDispatch && !$toUserId && !$toTeamId) {
            throw new InvalidArgumentException('Envio exige usuário destino ou equipe destino.');
        }

        return DB::transaction(function () use ($demand, $actor, $toUserId, $toTeamId, $message, $dueAt, $externalDispatch, $extraMetadata) {
            $metadata = array_merge([
                'due_at' => $dueAt?->format('Y-m-d H:i:s'),
            ], $extraMetadata);

            $assignment = LegalDemandAssignment::create([
                'legal_demand_id' => $demand->id,
                'from_user_id' => $actor->id,
                'to_user_id' => $toUserId,
                'to_team_id' => $toTeamId,
                'status' => LegalDemandAssignmentStatus::SENT,
                'message' => $message,
                'sent_at' => now(),
                'metadata' => $metadata,
            ]);

            if (!$this->isSubdemandAssignment($assignment)) {
                $from = $demand->internal_status?->value;
                $demand->current_assigned_user_id = $toUserId;
                $demand->current_assigned_team_id = $toTeamId;
                $demand->internal_status = LegalDemandInternalStatus::SENT_TO_FIELD;
                $demand->save();

                $this->event($demand->id, 'sent_to_field', $from, LegalDemandInternalStatus::SENT_TO_FIELD->value, $actor->id, $toUserId, $toTeamId, 'Demanda enviada para ponta.', $assignment->id);
            } else {
                $this->event($demand->id, 'subdemand_sent_to_field', $demand->internal_status?->value, $demand->internal_status?->value, $actor->id, $toUserId, $toTeamId, 'Subdemanda enviada para executante.', $assignment->id);
            }

            return $assignment->refresh();
        });
    }

    public function receiveInField(LegalDemandAssignment $assignment, User $actor): LegalDemandAssignment
    {
        $demand = $assignment->legalDemand;
        $this->assertNotClosed($demand);
        $this->ensureAllowed($actor, 'legal.demands.answer');

        return DB::transaction(function () use ($assignment, $demand, $actor) {
            $assignment->status = LegalDemandAssignmentStatus::RECEIVED;
            $assignment->received_at = $assignment->received_at ?? now();
            $assignment->save();

            if (!$this->isSubdemandAssignment($assignment)) {
                $from = $demand->internal_status?->value;
                $demand->internal_status = LegalDemandInternalStatus::FIELD_RECEIVED;
                $demand->save();

                $this->event($demand->id, 'field_received', $from, LegalDemandInternalStatus::FIELD_RECEIVED->value, $actor->id, null, null, 'Ponta recebeu a demanda.', $assignment->id);
            } else {
                $this->event($demand->id, 'subdemand_field_received', $demand->internal_status?->value, $demand->internal_status?->value, $actor->id, null, null, 'Executante recebeu subdemanda.', $assignment->id);
            }
            return $assignment->refresh();
        });
    }

    public function answerFromField(LegalDemandAssignment $assignment, User $actor, ?string $responseSummary, bool $hasEvidence, ?string $impossibilityReason): LegalDemandAssignment
    {
        $demand = $assignment->legalDemand;
        $this->assertNotClosed($demand);
        $this->ensureAllowed($actor, 'legal.demands.answer');

        $summary = trim((string) $responseSummary);
        if ($summary === '' && !$hasEvidence && trim((string) $impossibilityReason) === '') {
            throw new InvalidArgumentException('Resposta exige texto, evidência ou justificativa de impossibilidade.');
        }

        return DB::transaction(function () use ($assignment, $demand, $actor, $summary, $impossibilityReason) {
            $assignment->status = LegalDemandAssignmentStatus::ANSWERED;
            $assignment->answered_at = now();
            $metadata = (array) ($assignment->metadata ?? []);
            $metadata['response_summary'] = $summary !== '' ? $summary : trim((string) $impossibilityReason);
            $assignment->metadata = $metadata;
            $assignment->save();

            if (!$this->isSubdemandAssignment($assignment)) {
                $from = $demand->internal_status?->value;
                $demand->internal_status = LegalDemandInternalStatus::RETURNED_BY_FIELD;
                $demand->save();

                $this->event($demand->id, 'field_answered', $from, LegalDemandInternalStatus::RETURNED_BY_FIELD->value, $actor->id, null, null, 'Resposta registrada pela ponta.', $assignment->id);
            } else {
                $this->event($demand->id, 'subdemand_answered', $demand->internal_status?->value, $demand->internal_status?->value, $actor->id, null, null, 'Resposta registrada na subdemanda.', $assignment->id);
                $this->advanceSubdemandFromAssignment($assignment, $actor->id);
            }
            return $assignment->refresh();
        });
    }

    public function answerFromExternal(
        LegalDemandAssignment $assignment,
        string $externalExecutorName,
        ?string $responseSummary,
        bool $hasEvidence,
        ?string $impossibilityReason
    ): LegalDemandAssignment {
        $demand = $assignment->legalDemand;
        $this->assertNotClosed($demand);

        $externalExecutorName = trim($externalExecutorName);
        if ($externalExecutorName === '') {
            throw new InvalidArgumentException('Nome do executante externo é obrigatório.');
        }

        $summary = trim((string) $responseSummary);
        if ($summary === '' && !$hasEvidence && trim((string) $impossibilityReason) === '') {
            throw new InvalidArgumentException('Resposta exige texto, evidência ou justificativa de impossibilidade.');
        }

        return DB::transaction(function () use ($assignment, $demand, $externalExecutorName, $summary, $impossibilityReason) {
            $assignment->status = LegalDemandAssignmentStatus::ANSWERED;
            $assignment->answered_at = now();
            $metadata = (array) ($assignment->metadata ?? []);
            $metadata['response_summary'] = $summary !== '' ? $summary : trim((string) $impossibilityReason);
            $metadata['external_executor_name'] = $externalExecutorName;
            $metadata['answered_via'] = 'external_link';
            $assignment->metadata = $metadata;
            $assignment->save();

            if (!$this->isSubdemandAssignment($assignment)) {
                $from = $demand->internal_status?->value;
                $demand->internal_status = LegalDemandInternalStatus::RETURNED_BY_FIELD;
                $demand->save();

                $this->event(
                    $demand->id,
                    'field_answered_external',
                    $from,
                    LegalDemandInternalStatus::RETURNED_BY_FIELD->value,
                    null,
                    $assignment->to_user_id,
                    $assignment->to_team_id,
                    'Resposta registrada por executante externo.',
                    $assignment->id
                );
            } else {
                $this->event(
                    $demand->id,
                    'subdemand_answered_external',
                    $demand->internal_status?->value,
                    $demand->internal_status?->value,
                    null,
                    $assignment->to_user_id,
                    $assignment->to_team_id,
                    'Resposta externa registrada na subdemanda.',
                    $assignment->id
                );
                $this->advanceSubdemandFromAssignment($assignment, null);
            }

            return $assignment->refresh();
        });
    }

    public function requestCorrection(LegalDemandAssignment $assignment, User $actor, string $note): LegalDemand
    {
        $demand = $assignment->legalDemand;
        $this->assertNotClosed($demand);
        $this->ensureAllowed($actor, 'legal.demands.review');

        return DB::transaction(function () use ($assignment, $demand, $actor, $note) {
            $assignment->status = LegalDemandAssignmentStatus::RETURNED_FOR_CORRECTION;
            $assignment->returned_at = now();
            $metadata = (array) ($assignment->metadata ?? []);
            $metadata['controller_review_note'] = $note;
            $assignment->metadata = $metadata;
            $assignment->save();

            if (!$this->isSubdemandAssignment($assignment)) {
                $from = $demand->internal_status?->value;
                $demand->internal_status = LegalDemandInternalStatus::RETURNED_FOR_CORRECTION;
                $demand->save();

                $this->event($demand->id, 'returned_for_correction', $from, LegalDemandInternalStatus::RETURNED_FOR_CORRECTION->value, $actor->id, $assignment->to_user_id, $assignment->to_team_id, 'Controlador solicitou correção.', $assignment->id);
            } else {
                $this->event($demand->id, 'subdemand_returned_for_correction', $demand->internal_status?->value, $demand->internal_status?->value, $actor->id, $assignment->to_user_id, $assignment->to_team_id, 'Controlador solicitou correção na subdemanda.', $assignment->id);
            }

            return $demand->refresh();
        });
    }

    public function approveFieldReturn(LegalDemand $demand, User $actor): LegalDemand
    {
        $this->assertNotClosed($demand);
        $this->ensureAllowed($actor, 'legal.demands.review');

        return DB::transaction(function () use ($demand, $actor) {
            $from = $demand->internal_status?->value;
            $demand->internal_status = LegalDemandInternalStatus::READY_TO_CLOSE_EXTERNAL;
            $demand->save();

            $this->event($demand->id, 'controller_approved', $from, LegalDemandInternalStatus::READY_TO_CLOSE_EXTERNAL->value, $actor->id, null, null, 'Retorno aprovado pelo controlador.');
            return $demand->refresh();
        });
    }

    public function closeInternal(LegalDemand $demand, User $actor, string $reason): LegalDemand
    {
        $this->assertNotClosed($demand);
        $this->ensureAnyAllowed($actor, ['legal.demands.close_internal', 'legal.demands.review']);
        $this->assertCanCloseMainDemand($demand);

        return DB::transaction(function () use ($demand, $actor, $reason) {
            $from = $demand->internal_status?->value;
            $demand->internal_status = LegalDemandInternalStatus::CLOSED_INTERNAL;
            $demand->closed_by = $actor->id;
            $demand->closed_at = now();
            $demand->closure_reason = $reason;
            $demand->save();

            $this->event($demand->id, 'internal_closed', $from, LegalDemandInternalStatus::CLOSED_INTERNAL->value, $actor->id, null, null, 'Encerramento interno registrado.');
            return $demand->refresh();
        });
    }

    public function closeExternal(LegalDemand $demand, User $actor, string $protocol, ?string $note): LegalDemand
    {
        $this->assertNotClosed($demand);
        $this->ensureAnyAllowed($actor, ['legal.demands.close_external', 'legal.demands.review']);
        $this->assertCanCloseMainDemand($demand);

        if (trim($protocol) === '') {
            throw new InvalidArgumentException('Protocolo externo é obrigatório.');
        }

        return DB::transaction(function () use ($demand, $actor, $protocol, $note) {
            $from = $demand->internal_status?->value;
            $demand->internal_status = LegalDemandInternalStatus::CLOSED_EXTERNAL;
            $demand->external_closed_at = now();
            $demand->external_protocol = $protocol;
            $demand->external_closure_note = $note;
            if (!$demand->closed_at) {
                $demand->closed_by = $actor->id;
                $demand->closed_at = now();
            }
            $demand->save();

            $this->event($demand->id, 'external_closed', $from, LegalDemandInternalStatus::CLOSED_EXTERNAL->value, $actor->id, null, null, 'Encerramento externo registrado.');
            return $demand->refresh();
        });
    }

    public function reopen(LegalDemand $demand, User $actor, string $reason): LegalDemand
    {
        $this->ensureAllowed($actor, 'legal.demands.reopen');

        return DB::transaction(function () use ($demand, $actor, $reason) {
            $from = $demand->internal_status?->value;
            $demand->internal_status = LegalDemandInternalStatus::REOPENED;
            $demand->closure_reason = $reason;
            $demand->save();

            $this->event($demand->id, 'reopened', $from, LegalDemandInternalStatus::REOPENED->value, $actor->id, null, null, 'Demanda reaberta.');
            return $demand->refresh();
        });
    }

    public function addComment(LegalDemand $demand, User $actor, string $comment, ?int $assignmentId = null, ?string $visibility = null): LegalDemandComment
    {
        $this->ensureAllowed($actor, 'legal.demands.view');
        if (trim($comment) === '') {
            throw new InvalidArgumentException('Comentário não pode ser vazio.');
        }

        return LegalDemandComment::create([
            'legal_demand_id' => $demand->id,
            'assignment_id' => $assignmentId,
            'user_id' => $actor->id,
            'comment' => $comment,
            'visibility' => $visibility,
        ]);
    }

    private function assertNotClosed(LegalDemand $demand): void
    {
        if (in_array((string) $demand->internal_status?->value, [
            LegalDemandInternalStatus::CLOSED_EXTERNAL->value,
            LegalDemandInternalStatus::CANCELLED->value,
            LegalDemandInternalStatus::IGNORED->value,
        ], true)) {
            throw new InvalidArgumentException('Demanda encerrada/cancelada não permite esta ação.');
        }
    }

    private function assertTransition(LegalDemand $demand, array $allowed, string $action): void
    {
        $current = $demand->internal_status?->value;
        $allowedValues = array_map(fn($item) => $item instanceof LegalDemandInternalStatus ? $item->value : (string) $item, $allowed);

        if ($current !== null && !in_array($current, $allowedValues, true)) {
            throw new InvalidArgumentException("Transição bloqueada para {$action} no status {$current}.");
        }
    }

    private function ensureAllowed(User $actor, string $permission): void
    {
        if (method_exists($actor, 'can') && !$actor->can($permission)) {
            throw new InvalidArgumentException("Sem permissão: {$permission}");
        }
    }

    private function ensureAnyAllowed(User $actor, array $permissions): void
    {
        foreach ($permissions as $permission) {
            if (method_exists($actor, 'can') && $actor->can($permission)) {
                return;
            }
        }

        throw new InvalidArgumentException('Sem permissão: ' . implode(' ou ', $permissions));
    }

    private function event(
        int $demandId,
        string $type,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $actorUserId,
        ?string $targetUserId,
        ?string $targetTeamId,
        ?string $description,
        ?int $assignmentId = null
    ): void {
        LegalDemandEvent::create([
            'legal_demand_id' => $demandId,
            'assignment_id' => $assignmentId,
            'event_type' => $type,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_user_id' => $actorUserId,
            'target_user_id' => $targetUserId,
            'target_team_id' => $targetTeamId,
            'description' => $description,
            'occurred_at' => now(),
            'metadata' => ['source' => 'workflow'],
        ]);
    }

    private function isSubdemandAssignment(LegalDemandAssignment $assignment): bool
    {
        $metadata = (array) ($assignment->metadata ?? []);
        return (string) ($metadata['source'] ?? '') === 'subdemand'
            || !empty($metadata['subdemand_id']);
    }

    private function advanceSubdemandFromAssignment(LegalDemandAssignment $assignment, ?string $actorUserId): void
    {
        $subdemandId = (int) data_get($assignment->metadata ?? [], 'subdemand_id', 0);
        if ($subdemandId <= 0) {
            return;
        }

        $sub = LegalDemandSubdemand::query()->find($subdemandId);
        if (!$sub) {
            return;
        }

        $status = $sub->status instanceof \BackedEnum ? $sub->status->value : (string) $sub->status;
        if ($status === 'concluida' || $status === 'encerrada_controlador') {
            return;
        }

        $sub->status = 'aguardando_retorno';
        if ($sub->started_at === null) {
            $sub->started_at = now();
        }
        $sub->save();

        $sub->events()->create([
            'event_type' => 'status_changed',
            'from_status' => $status,
            'to_status' => 'aguardando_retorno',
            'actor_user_id' => $actorUserId,
            'actor_role' => $actorUserId ? 'operator' : 'external',
            'reason' => null,
            'description' => 'Subdemanda atualizada após resposta do executante.',
            'payload' => ['assignment_id' => $assignment->id],
            'occurred_at' => now(),
        ]);
    }

    private function assertCanCloseMainDemand(LegalDemand $demand): void
    {
        $openSubdemands = $demand->subdemands()
            ->get()
            ->filter(function (LegalDemandSubdemand $sub) {
                $metadata = (array) ($sub->metadata ?? []);
                $removedByController = (bool) ($metadata['removed_by_controller'] ?? false);
                $status = $sub->status instanceof \BackedEnum ? $sub->status->value : (string) $sub->status;
                $isOpen = !in_array($status, ['concluida', 'encerrada_controlador'], true);

                return !$removedByController && $isOpen;
            });

        if ($openSubdemands->isNotEmpty()) {
            throw new InvalidArgumentException('Não é possível fechar a demanda principal: existem subdemandas em aberto.');
        }
    }
}
