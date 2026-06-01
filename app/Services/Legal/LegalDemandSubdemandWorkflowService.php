<?php

namespace App\Services\Legal;

use App\Enum\LegalDemandSubdemandStatus;
use App\Jobs\Legal\ProcessSubdemandJob;
use App\Models\Legal\LegalDemand;
use App\Models\Legal\LegalDemandSubdemand;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LegalDemandSubdemandWorkflowService
{
    public const CONTRACT_VERSION = 'v1';

    public function create(
        LegalDemand $demand,
        User $actor,
        ?string $assignedToUserId,
        ?string $assignedAreaName,
        ?\DateTimeInterface $deadlineAt,
        ?string $description = null,
        array $metadata = []
    ): LegalDemandSubdemand {
        return DB::transaction(function () use ($demand, $actor, $assignedToUserId, $assignedAreaName, $deadlineAt, $description, $metadata) {
            $subdemand = LegalDemandSubdemand::create([
                'uuid' => (string) str()->uuid(),
                'legal_demand_id' => $demand->id,
                'assigned_to_user_id' => $assignedToUserId,
                'assigned_area_name' => $this->normalizeNullable($assignedAreaName),
                'status' => LegalDemandSubdemandStatus::ABERTA,
                'deadline_at' => $deadlineAt,
                'created_by_user_id' => $actor->id,
                'status_contract_version' => self::CONTRACT_VERSION,
                'metadata' => $metadata,
            ]);

            $this->event(
                $subdemand,
                eventType: 'created',
                actor: $actor,
                fromStatus: null,
                toStatus: LegalDemandSubdemandStatus::ABERTA->value,
                reason: null,
                description: $description ?: 'Subdemanda criada.',
                payload: [
                    'assigned_to_user_id' => $assignedToUserId,
                    'assigned_area_name' => $this->normalizeNullable($assignedAreaName),
                    'deadline_at' => $deadlineAt?->format('Y-m-d H:i:s'),
                ]
            );
            app(LegalDemandSubdemandMetricsService::class)->refreshForDemand($demand);

            $this->dispatchAsyncProcessing($subdemand, 'created');

            return $subdemand->refresh();
        });
    }

    public function transitionStatus(
        LegalDemandSubdemand $subdemand,
        User $actor,
        LegalDemandSubdemandStatus $toStatus,
        ?string $reason = null,
        ?string $description = null,
        array $payload = []
    ): LegalDemandSubdemand {
        $fromStatus = $subdemand->status instanceof LegalDemandSubdemandStatus
            ? $subdemand->status
            : LegalDemandSubdemandStatus::from((string) $subdemand->status);

        $this->assertTransitionAllowed($fromStatus, $toStatus, $reason);

        return DB::transaction(function () use ($subdemand, $actor, $fromStatus, $toStatus, $reason, $description, $payload) {
            $subdemand->status = $toStatus;

            if ($toStatus === LegalDemandSubdemandStatus::EM_ANDAMENTO && $subdemand->started_at === null) {
                $subdemand->started_at = now();
            }

            if (in_array($toStatus, [LegalDemandSubdemandStatus::CONCLUIDA, LegalDemandSubdemandStatus::ENCERRADA_CONTROLADOR], true)) {
                $subdemand->finished_at = now();
            }

            $subdemand->save();

            $this->event(
                $subdemand,
                eventType: 'status_changed',
                actor: $actor,
                fromStatus: $fromStatus->value,
                toStatus: $toStatus->value,
                reason: $this->normalizeNullable($reason),
                description: $description ?: 'Status da subdemanda atualizado.',
                payload: $payload,
            );
            app(LegalDemandSubdemandMetricsService::class)->refreshForDemand($subdemand->demand);

            $this->dispatchAsyncProcessing($subdemand, 'status_changed');

            return $subdemand->refresh();
        });
    }

    public function reassign(
        LegalDemandSubdemand $subdemand,
        User $actor,
        ?string $assignedToUserId,
        ?string $assignedAreaName,
        ?string $reason = null
    ): LegalDemandSubdemand {
        return DB::transaction(function () use ($subdemand, $actor, $assignedToUserId, $assignedAreaName, $reason) {
            $beforeUser = $subdemand->assigned_to_user_id;
            $beforeArea = $subdemand->assigned_area_name;

            $subdemand->assigned_to_user_id = $assignedToUserId;
            $subdemand->assigned_area_name = $this->normalizeNullable($assignedAreaName);
            $subdemand->save();

            $this->event(
                $subdemand,
                eventType: 'reassigned',
                actor: $actor,
                fromStatus: $subdemand->status?->value ?? (string) $subdemand->status,
                toStatus: $subdemand->status?->value ?? (string) $subdemand->status,
                reason: $this->normalizeNullable($reason),
                description: 'Subdemanda reatribuída.',
                payload: [
                    'from_assigned_to_user_id' => $beforeUser,
                    'to_assigned_to_user_id' => $assignedToUserId,
                    'from_assigned_area_name' => $beforeArea,
                    'to_assigned_area_name' => $this->normalizeNullable($assignedAreaName),
                ]
            );
            app(LegalDemandSubdemandMetricsService::class)->refreshForDemand($subdemand->demand);

            $this->dispatchAsyncProcessing($subdemand, 'reassigned');

            return $subdemand->refresh();
        });
    }

    public function updateDeadline(
        LegalDemandSubdemand $subdemand,
        User $actor,
        ?\DateTimeInterface $deadlineAt,
        ?string $reason = null
    ): LegalDemandSubdemand {
        return DB::transaction(function () use ($subdemand, $actor, $deadlineAt, $reason) {
            $before = $subdemand->deadline_at?->format('Y-m-d H:i:s');
            $subdemand->deadline_at = $deadlineAt;
            $subdemand->save();

            $this->event(
                $subdemand,
                eventType: 'deadline_changed',
                actor: $actor,
                fromStatus: $subdemand->status?->value ?? (string) $subdemand->status,
                toStatus: $subdemand->status?->value ?? (string) $subdemand->status,
                reason: $this->normalizeNullable($reason),
                description: 'Prazo da subdemanda atualizado.',
                payload: [
                    'from_deadline_at' => $before,
                    'to_deadline_at' => $deadlineAt?->format('Y-m-d H:i:s'),
                ]
            );
            app(LegalDemandSubdemandMetricsService::class)->refreshForDemand($subdemand->demand);

            $this->dispatchAsyncProcessing($subdemand, 'deadline_changed');

            return $subdemand->refresh();
        });
    }

    public function generateExternalAccess(
        LegalDemandSubdemand $subdemand,
        ?User $actor,
        ?\DateTimeInterface $expiresAt = null
    ): string {
        $token = bin2hex(random_bytes(24));
        $hash = hash('sha256', $token);

        $subdemand->external_access_token_hash = $hash;
        $subdemand->external_access_expires_at = $expiresAt ?? now()->addDays(2);
        $subdemand->external_access_revoked_at = null;
        $subdemand->external_access_generated_by = $actor?->id;
        $subdemand->save();

        $this->event(
            $subdemand,
            eventType: 'external_link_generated',
            actor: $actor,
            fromStatus: $subdemand->status?->value ?? (string) $subdemand->status,
            toStatus: $subdemand->status?->value ?? (string) $subdemand->status,
            reason: null,
            description: 'Link externo da subdemanda gerado.',
            payload: ['expires_at' => $subdemand->external_access_expires_at?->format('Y-m-d H:i:s')],
        );

        return $token;
    }

    public function revokeExternalAccess(LegalDemandSubdemand $subdemand, ?User $actor, ?string $reason = null): LegalDemandSubdemand
    {
        $subdemand->external_access_revoked_at = now();
        $subdemand->save();

        $this->event(
            $subdemand,
            eventType: 'external_link_revoked',
            actor: $actor,
            fromStatus: $subdemand->status?->value ?? (string) $subdemand->status,
            toStatus: $subdemand->status?->value ?? (string) $subdemand->status,
            reason: $this->normalizeNullable($reason),
            description: 'Link externo da subdemanda revogado.',
            payload: [],
        );

        return $subdemand->refresh();
    }

    private function dispatchAsyncProcessing(LegalDemandSubdemand $subdemand, string $trigger): void
    {
        ProcessSubdemandJob::dispatch($subdemand->id, $trigger)->afterCommit();
    }

    public function resolveExternalByToken(string $token): ?LegalDemandSubdemand
    {
        $hash = hash('sha256', trim($token));

        return LegalDemandSubdemand::query()
            ->with(['demand.legalCase', 'comments.user'])
            ->where('external_access_token_hash', $hash)
            ->whereNull('external_access_revoked_at')
            ->where(function ($q) {
                $q->whereNull('external_access_expires_at')
                    ->orWhere('external_access_expires_at', '>=', now());
            })
            ->first();
    }

    private function assertTransitionAllowed(LegalDemandSubdemandStatus $from, LegalDemandSubdemandStatus $to, ?string $reason = null): void
    {
        if ($from === $to) {
            throw new InvalidArgumentException('A subdemanda já está no status informado.');
        }

        $allowed = match ($from) {
            LegalDemandSubdemandStatus::ABERTA => [
                LegalDemandSubdemandStatus::EM_ANDAMENTO,
                LegalDemandSubdemandStatus::ENCERRADA_CONTROLADOR,
            ],
            LegalDemandSubdemandStatus::EM_ANDAMENTO => [
                LegalDemandSubdemandStatus::AGUARDANDO_RETORNO,
                LegalDemandSubdemandStatus::CONCLUIDA,
                LegalDemandSubdemandStatus::ENCERRADA_CONTROLADOR,
            ],
            LegalDemandSubdemandStatus::AGUARDANDO_RETORNO => [
                LegalDemandSubdemandStatus::EM_ANDAMENTO,
                LegalDemandSubdemandStatus::ENCERRADA_CONTROLADOR,
            ],
            LegalDemandSubdemandStatus::CONCLUIDA,
            LegalDemandSubdemandStatus::ENCERRADA_CONTROLADOR => [],
        };

        if (!in_array($to, $allowed, true)) {
            throw new InvalidArgumentException(sprintf('Transição inválida: %s -> %s', $from->value, $to->value));
        }

        if ($to === LegalDemandSubdemandStatus::ENCERRADA_CONTROLADOR && $this->normalizeNullable($reason) === null) {
            throw new InvalidArgumentException('Motivo obrigatório para encerramento pelo controlador.');
        }
    }

    private function event(
        LegalDemandSubdemand $subdemand,
        string $eventType,
        ?User $actor,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $reason,
        ?string $description,
        array $payload = []
    ): void {
        $subdemand->events()->create([
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_user_id' => $actor?->id,
            'actor_role' => $this->resolveActorRole($actor),
            'reason' => $reason,
            'description' => $description,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }

    private function resolveActorRole(?User $actor): ?string
    {
        if ($actor === null) {
            return null;
        }

        if ($actor->can('legal.demands.triage') || $actor->can('legal.demands.assign') || $actor->can('legal.demands.review')) {
            return 'controller';
        }

        if ($actor->can('legal.demands.answer')) {
            return 'operator';
        }

        return 'user';
    }

    private function normalizeNullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
