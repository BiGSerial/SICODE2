<?php

namespace App\Services\Legal;

use App\Enum\LegalDemandAssignmentStatus;
use App\Enum\LegalDemandInternalStatus;
use App\Models\Legal\LegalDemand;
use App\Models\Legal\LegalDemandAssignment;
use App\Models\Legal\LegalDemandComment;
use App\Models\Legal\LegalDemandEvent;
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

            $this->event($demand->id, 'triage_started', $from, LegalDemandInternalStatus::TRIAGE->value, $actor->id, null, null, 'Triagem iniciada.');
            return $demand->refresh();
        });
    }

    public function sendToField(LegalDemand $demand, User $actor, ?string $toUserId, ?int $toTeamId, ?string $message, ?\DateTimeInterface $dueAt): LegalDemandAssignment
    {
        $this->assertNotClosed($demand);
        $this->ensureAllowed($actor, 'legal.demands.assign');
        $this->assertTransition($demand, [
            LegalDemandInternalStatus::NEW_IMPORTED,
            LegalDemandInternalStatus::TRIAGE,
            LegalDemandInternalStatus::SENT_TO_FIELD,
            LegalDemandInternalStatus::FIELD_RECEIVED,
            LegalDemandInternalStatus::WAITING_FIELD_RESPONSE,
            LegalDemandInternalStatus::RETURNED_BY_FIELD,
            LegalDemandInternalStatus::UNDER_CONTROLLER_REVIEW,
            LegalDemandInternalStatus::RETURNED_FOR_CORRECTION,
        ], 'sent_to_field');

        if (!$toUserId && !$toTeamId) {
            throw new InvalidArgumentException('Envio exige usuário destino ou equipe destino.');
        }

        return DB::transaction(function () use ($demand, $actor, $toUserId, $toTeamId, $message, $dueAt) {
            $assignment = LegalDemandAssignment::create([
                'uuid' => (string) str()->uuid(),
                'legal_demand_id' => $demand->id,
                'assigned_by_user_id' => $actor->id,
                'assigned_to_user_id' => $toUserId,
                'assigned_to_team_id' => $toTeamId,
                'status' => LegalDemandAssignmentStatus::SENT,
                'message' => $message,
                'due_at' => $dueAt,
                'sent_at' => now(),
            ]);

            $from = $demand->internal_status?->value;
            $demand->current_assigned_user_id = $toUserId;
            $demand->current_assigned_team_id = $toTeamId;
            $demand->internal_status = LegalDemandInternalStatus::SENT_TO_FIELD;
            $demand->save();

            $this->event(
                $demand->id,
                'sent_to_field',
                $from,
                LegalDemandInternalStatus::SENT_TO_FIELD->value,
                $actor->id,
                $toUserId,
                $toTeamId,
                'Demanda enviada para ponta.',
                $assignment->id
            );

            return $assignment->refresh();
        });
    }

    public function receiveInField(LegalDemandAssignment $assignment, User $actor): LegalDemandAssignment
    {
        $demand = $assignment->LegalDemand;
        $this->assertNotClosed($demand);
        $this->ensureAllowed($actor, 'legal.demands.answer');

        return DB::transaction(function () use ($assignment, $demand, $actor) {
            $assignment->status = LegalDemandAssignmentStatus::RECEIVED;
            $assignment->received_at = $assignment->received_at ?? now();
            $assignment->save();

            $from = $demand->internal_status?->value;
            $demand->internal_status = LegalDemandInternalStatus::FIELD_RECEIVED;
            $demand->save();

            $this->event($demand->id, 'field_received', $from, LegalDemandInternalStatus::FIELD_RECEIVED->value, $actor->id, null, null, 'Ponta recebeu a demanda.', $assignment->id);
            return $assignment->refresh();
        });
    }

    public function answerFromField(LegalDemandAssignment $assignment, User $actor, ?string $responseSummary, bool $hasEvidence, ?string $impossibilityReason): LegalDemandAssignment
    {
        $demand = $assignment->LegalDemand;
        $this->assertNotClosed($demand);
        $this->ensureAllowed($actor, 'legal.demands.answer');

        $summary = trim((string) $responseSummary);
        if ($summary === '' && !$hasEvidence && trim((string) $impossibilityReason) === '') {
            throw new InvalidArgumentException('Resposta exige texto, evidência ou justificativa de impossibilidade.');
        }

        return DB::transaction(function () use ($assignment, $demand, $actor, $summary, $impossibilityReason) {
            $assignment->status = LegalDemandAssignmentStatus::ANSWERED;
            $assignment->answered_at = now();
            $assignment->response_summary = $summary !== '' ? $summary : trim((string) $impossibilityReason);
            $assignment->save();

            $from = $demand->internal_status?->value;
            $demand->internal_status = LegalDemandInternalStatus::RETURNED_BY_FIELD;
            $demand->save();

            $this->event($demand->id, 'field_answered', $from, LegalDemandInternalStatus::RETURNED_BY_FIELD->value, $actor->id, null, null, 'Resposta registrada pela ponta.', $assignment->id);
            return $assignment->refresh();
        });
    }

    public function requestCorrection(LegalDemandAssignment $assignment, User $actor, string $note): LegalDemand
    {
        $demand = $assignment->LegalDemand;
        $this->assertNotClosed($demand);
        $this->ensureAllowed($actor, 'legal.demands.review');

        return DB::transaction(function () use ($assignment, $demand, $actor, $note) {
            $assignment->status = LegalDemandAssignmentStatus::RETURNED_FOR_CORRECTION;
            $assignment->controller_review_note = $note;
            $assignment->returned_at = now();
            $assignment->save();

            $from = $demand->internal_status?->value;
            $demand->internal_status = LegalDemandInternalStatus::RETURNED_FOR_CORRECTION;
            $demand->save();

            $this->event($demand->id, 'returned_for_correction', $from, LegalDemandInternalStatus::RETURNED_FOR_CORRECTION->value, $actor->id, $assignment->assigned_to_user_id, $assignment->assigned_to_team_id, 'Controlador solicitou correção.', $assignment->id);
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
        $this->ensureAllowed($actor, 'legal.demands.close_internal');

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
        $this->ensureAllowed($actor, 'legal.demands.close_external');
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

    private function event(
        int $demandId,
        string $type,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $actorUserId,
        ?string $targetUserId,
        ?int $targetTeamId,
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
            'metadata' => ['source' => 'legal_workflow'],
            'occurred_at' => now(),
        ]);
    }
}
