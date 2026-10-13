<?php

namespace App\Services\Closure;

use App\Models\Closure\{ClosureOrderEvent, ClosureOrderState, ClosureOrderStatePeriod};
use App\Models\{Company, Order, Production, User};
use Illuminate\Support\Facades\DB;

class ClosureOrderWorkflowService
{
    public const STATUS_AVAILABLE = 'AVAILABLE';

    public const STATUS_ASSIGNED = 'ASSIGNED';

    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';

    public const STATUS_WAITING_RETURN = 'WAITING_RETURN';

    public const STATUS_CLOSED = 'CLOSED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const HOLDER_INTERNAL_USER = 'INTERNAL_USER';

    public const HOLDER_PARTNER_COMPANY = 'PARTNER_COMPANY';

    public const HOLDER_SYSTEM = 'SYSTEM';

    public const ORIGIN_UI = 'UI';

    public const EVENT_CLAIMED = 'CLAIMED';

    public const EVENT_ASSIGNED = 'ASSIGNED';

    public const EVENT_TRANSFERRED = 'TRANSFERRED';

    public const EVENT_RETURNED_TO_POOL = 'RETURNED_TO_POOL';

    public const EVENT_STARTED = 'STARTED';

    public const EVENT_WAITING_RETURN = 'WAITING_RETURN';

    public const EVENT_PRIORITY_CHANGED = 'PRIORITY_CHANGED';

    public const EVENT_ISSUE_BLOCKED = 'ISSUE_BLOCKED';

    public const EVENT_ISSUE_RELEASED = 'ISSUE_RELEASED';

    public function claim(ClosureOrderState $state, User $actor, ?string $batchId = null): ClosureOrderState
    {
        return $this->transition($state, $actor, self::EVENT_CLAIMED, $batchId, function (ClosureOrderState $state) use ($actor): array {
            $this->requireStatus($state, [self::STATUS_AVAILABLE]);

            return [
                'operational_status'        => self::STATUS_ASSIGNED,
                'assigned_user_id'          => $actor->id,
                'current_holder_type'       => self::HOLDER_INTERNAL_USER,
                'current_holder_user_id'    => $actor->id,
                'current_holder_company_id' => $actor->company_id,
            ];
        });
    }

    public function assign(ClosureOrderState $state, Company $company, ?User $user, User $actor, ?string $reason = null, ?string $batchId = null): ClosureOrderState
    {
        return $this->moveToDestination($state, $company, $user, $actor, self::EVENT_ASSIGNED, $reason, $batchId, false);
    }

    public function transfer(ClosureOrderState $state, Company $company, ?User $user, User $actor, string $reason, ?string $batchId = null): ClosureOrderState
    {
        return $this->moveToDestination($state, $company, $user, $actor, self::EVENT_TRANSFERRED, $reason, $batchId, true);
    }

    public function returnToPool(ClosureOrderState $state, User $actor, string $reason, ?string $batchId = null): ClosureOrderState
    {
        $this->requireReason($reason);

        return $this->transition($state, $actor, self::EVENT_RETURNED_TO_POOL, $batchId, function (ClosureOrderState $state) use ($reason): array {
            $this->requireStatus($state, [self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS, self::STATUS_WAITING_RETURN]);
            $production = Production::query()->whereKey($state->production_id)->lockForUpdate()->firstOrFail();

            return [
                'operational_status'        => self::STATUS_AVAILABLE,
                'assigned_user_id'          => null,
                'current_holder_type'       => self::HOLDER_PARTNER_COMPANY,
                'current_holder_user_id'    => null,
                'current_holder_company_id' => $production->company_id,
                'reason'                    => trim($reason),
            ];
        });
    }

    public function start(ClosureOrderState $state, User $actor, ?string $batchId = null): ClosureOrderState
    {
        return $this->transition($state, $actor, self::EVENT_STARTED, $batchId, function (ClosureOrderState $state) use ($actor): array {
            $this->requireStatus($state, [self::STATUS_ASSIGNED]);

            if ($state->assigned_user_id && (string) $state->assigned_user_id !== (string) $actor->id) {
                throw new ClosureOrderWorkflowException('Somente o responsável atribuído pode iniciar a tratativa.');
            }

            return ['operational_status' => self::STATUS_IN_PROGRESS];
        });
    }

    public function waitForReturn(ClosureOrderState $state, User $actor, string $reason, ?string $batchId = null): ClosureOrderState
    {
        $this->requireReason($reason);

        return $this->transition($state, $actor, self::EVENT_WAITING_RETURN, $batchId, function (ClosureOrderState $state) use ($reason): array {
            $this->requireStatus($state, [self::STATUS_IN_PROGRESS]);

            return [
                'operational_status'        => self::STATUS_WAITING_RETURN,
                'current_holder_type'       => self::HOLDER_SYSTEM,
                'current_holder_user_id'    => null,
                'current_holder_company_id' => null,
                'reason'                    => trim($reason),
            ];
        });
    }

    public function setPriority(ClosureOrderState $state, bool $priority, User $actor, ?string $batchId = null): ClosureOrderState
    {
        return $this->transition($state, $actor, self::EVENT_PRIORITY_CHANGED, $batchId, function (ClosureOrderState $state) use ($priority): array {
            if ($state->priority === $priority) {
                throw new ClosureOrderWorkflowException('A prioridade informada já é a prioridade atual da Ordem.');
            }

            return ['priority' => $priority];
        }, false);
    }

    public function blockForIssue(ClosureOrderState $state, User $actor, int $issueId, string $reason): ClosureOrderState
    {
        $this->requireReason($reason);

        return $this->transition($state, $actor, self::EVENT_ISSUE_BLOCKED, null, function (ClosureOrderState $state) use ($issueId, $reason): array {
            $this->requireStatus($state, [self::STATUS_AVAILABLE, self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS]);

            return [
                'operational_status'        => self::STATUS_WAITING_RETURN,
                'current_holder_type'       => self::HOLDER_SYSTEM,
                'current_holder_user_id'    => null,
                'current_holder_company_id' => null,
                'reason'                    => "Pendência #{$issueId}: " . trim($reason),
            ];
        });
    }

    /** @param array<string, mixed> $snapshot */
    public function restoreAfterIssue(ClosureOrderState $state, User $actor, int $issueId, array $snapshot): ClosureOrderState
    {
        foreach (['operational_status', 'current_holder_type'] as $key) {
            if (!array_key_exists($key, $snapshot)) {
                throw new ClosureOrderWorkflowException('O ponto de retorno da pendência está incompleto.');
            }
        }

        return $this->transition($state, $actor, self::EVENT_ISSUE_RELEASED, null, function (ClosureOrderState $state) use ($issueId, $snapshot): array {
            $this->requireStatus($state, [self::STATUS_WAITING_RETURN]);

            return [
                'operational_status'        => $snapshot['operational_status'],
                'assigned_user_id'          => $snapshot['assigned_user_id'] ?? null,
                'current_holder_type'       => $snapshot['current_holder_type'],
                'current_holder_user_id'    => $snapshot['current_holder_user_id'] ?? null,
                'current_holder_company_id' => $snapshot['current_holder_company_id'] ?? null,
                'reason'                    => "Pendência #{$issueId} resolvida; atividade devolvida ao ponto anterior.",
            ];
        });
    }

    private function moveToDestination(
        ClosureOrderState $state,
        Company $company,
        ?User $user,
        User $actor,
        string $eventType,
        ?string $reason,
        ?string $batchId,
        bool $reasonRequired,
    ): ClosureOrderState {
        if ($reasonRequired) {
            $this->requireReason((string) $reason);
        }

        return $this->transition($state, $actor, $eventType, $batchId, function (ClosureOrderState $state) use ($company, $user, $reason, $reasonRequired): array {
            $this->requireStatus($state, [self::STATUS_AVAILABLE, self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS, self::STATUS_WAITING_RETURN]);

            if ($reasonRequired || ($state->assigned_user_id && (string) $state->assigned_user_id !== (string) ($user?->id))) {
                $this->requireReason((string) $reason);
            }

            if ($user && (string) $user->company_id !== (string) $company->id) {
                throw new ClosureOrderWorkflowException('O usuário de destino não pertence à empresa selecionada.');
            }

            return [
                'operational_status'        => $user ? self::STATUS_ASSIGNED : self::STATUS_AVAILABLE,
                'assigned_user_id'          => $user?->id,
                'current_holder_type'       => $user ? self::HOLDER_INTERNAL_USER : self::HOLDER_PARTNER_COMPANY,
                'current_holder_user_id'    => $user?->id,
                'current_holder_company_id' => $company->id,
                'reason'                    => $reason ? trim($reason) : null,
            ];
        });
    }

    /**
     * @param callable(ClosureOrderState): array<string, mixed> $changes
     */
    private function transition(ClosureOrderState $state, User $actor, string $eventType, ?string $batchId, callable $changes, bool $rotatePeriod = true): ClosureOrderState
    {
        return DB::transaction(function () use ($state, $actor, $eventType, $batchId, $changes, $rotatePeriod) {
            $state = ClosureOrderState::query()->whereKey($state->id)->lockForUpdate()->firstOrFail();
            $order = Order::query()->whereKey($state->order_id)->lockForUpdate()->firstOrFail();

            $this->assertOrderCanChange($order, $state);

            $before = $this->snapshot($state);
            $data   = $changes($state);
            $reason = $data['reason'] ?? null;
            unset($data['reason']);

            if ($rotatePeriod) {
                $data['entered_current_status_at'] = now();
            }

            $state->update($data);
            $state->refresh();

            $event = ClosureOrderEvent::query()->create([
                'closure_order_state_id' => $state->id,
                'order_id'               => $order->id,
                'actor_id'               => $actor->id,
                'type'                   => $eventType,
                'origin'                 => self::ORIGIN_UI,
                'before_data'            => $before,
                'after_data'             => $this->snapshot($state),
                'meta'                   => $reason ? ['reason' => $reason] : null,
                'batch_id'               => $batchId,
            ]);

            if ($rotatePeriod) {
                $this->rotatePeriod($state, $event);
            }

            $state->update(['last_event_id' => $event->id]);

            return $state->fresh();
        }, 3);
    }

    private function rotatePeriod(ClosureOrderState $state, ClosureOrderEvent $event): void
    {
        $currentPeriod = ClosureOrderStatePeriod::query()
            ->where('closure_order_state_id', $state->id)
            ->whereNull('ended_at')
            ->lockForUpdate()
            ->first();

        if ($currentPeriod) {
            $currentPeriod->update([
                'ended_at'       => now(),
                'ended_event_id' => $event->id,
            ]);
        }

        ClosureOrderStatePeriod::query()->create([
            'closure_order_state_id' => $state->id,
            'operational_status'     => $state->operational_status,
            'holder_type'            => $state->current_holder_type,
            'holder_user_id'         => $state->current_holder_user_id,
            'holder_company_id'      => $state->current_holder_company_id,
            'started_at'             => $state->entered_current_status_at,
            'started_event_id'       => $event->id,
        ]);
    }

    private function assertOrderCanChange(Order $order, ClosureOrderState $state): void
    {
        if ($order->canceled || $state->operational_status === self::STATUS_CANCELLED) {
            throw new ClosureOrderWorkflowException('A Ordem está cancelada e não pode ser alterada.');
        }

        if ($state->operational_status === self::STATUS_CLOSED || $this->isSapClosed((string) $order->statusSist)) {
            throw new ClosureOrderWorkflowException('A Ordem já está encerrada no SAP e não pode ser alterada.');
        }
    }

    /** @param array<int, string> $allowed */
    private function requireStatus(ClosureOrderState $state, array $allowed): void
    {
        if (!in_array($state->operational_status, $allowed, true)) {
            throw new ClosureOrderWorkflowException('A transição não é permitida no estado operacional atual.');
        }
    }

    private function requireReason(string $reason): void
    {
        if (trim($reason) === '') {
            throw new ClosureOrderWorkflowException('Informe o motivo da transição.');
        }
    }

    private function isSapClosed(string $status): bool
    {
        return str_starts_with($status, 'ENTE') || str_starts_with($status, 'ENCE');
    }

    /** @return array<string, mixed> */
    private function snapshot(ClosureOrderState $state): array
    {
        return [
            'operational_status'        => $state->operational_status,
            'priority'                  => (bool) $state->priority,
            'assigned_user_id'          => $state->assigned_user_id,
            'current_holder_type'       => $state->current_holder_type,
            'current_holder_user_id'    => $state->current_holder_user_id,
            'current_holder_company_id' => $state->current_holder_company_id,
        ];
    }
}
