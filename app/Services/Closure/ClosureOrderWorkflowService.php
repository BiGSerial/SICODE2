<?php

namespace App\Services\Closure;

use App\Models\Closure\{ClosureInternalReturn, ClosureIssue, ClosureOrderEvent, ClosureOrderState, ClosureOrderStatePeriod};
use App\Models\{Company, Order, Production, User};
use Illuminate\Support\Facades\DB;

class ClosureOrderWorkflowService
{
    public const STATUS_AVAILABLE = 'AVAILABLE';

    public const STATUS_ASSIGNED = 'ASSIGNED';

    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';

    public const STATUS_WAITING_PARTNER = 'WAITING_PARTNER';

    /** @deprecated Use STATUS_WAITING_PARTNER (valor migrado de WAITING_RETURN). */
    public const STATUS_WAITING_RETURN = self::STATUS_WAITING_PARTNER;

    public const STATUS_DEPOSIT_BLOCKED = 'DEPOSIT_BLOCKED';

    public const STATUS_DEPOSIT_RELEASED = 'DEPOSIT_RELEASED';

    public const STATUS_PAUSED = 'PAUSED';

    public const STATUS_FINISHED = 'FINISHED';

    public const STATUS_WAITING = 'WAITING';

    public const STATUS_CLOSED = 'CLOSED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const HOLDER_INTERNAL_USER = 'INTERNAL_USER';

    public const HOLDER_PARTNER_COMPANY = 'PARTNER_COMPANY';

    public const HOLDER_SYSTEM = 'SYSTEM';

    public const ORIGIN_UI = 'UI';

    public const ORIGIN_SAP_SYNC = 'SAP_SYNC';

    public const ORIGIN_SCHEDULER = 'SCHEDULER';

    /**
     * Matriz de transições manuais (changeStatus). Chaves: status atual; valores: destinos permitidos.
     * AVAILABLE não aparece: a Ordem precisa ser assumida/atribuída antes. FINISHED/WAITING só reabrem para IN_PROGRESS.
     */
    public const MANUAL_TRANSITIONS = [
        self::STATUS_ASSIGNED         => [self::STATUS_IN_PROGRESS, self::STATUS_PAUSED, self::STATUS_WAITING_PARTNER, self::STATUS_DEPOSIT_BLOCKED, self::STATUS_DEPOSIT_RELEASED],
        self::STATUS_IN_PROGRESS      => [self::STATUS_PAUSED, self::STATUS_WAITING_PARTNER, self::STATUS_DEPOSIT_BLOCKED, self::STATUS_DEPOSIT_RELEASED],
        self::STATUS_PAUSED           => [self::STATUS_IN_PROGRESS, self::STATUS_WAITING_PARTNER, self::STATUS_DEPOSIT_BLOCKED, self::STATUS_DEPOSIT_RELEASED],
        self::STATUS_WAITING_PARTNER  => [self::STATUS_IN_PROGRESS, self::STATUS_PAUSED],
        self::STATUS_DEPOSIT_BLOCKED  => [self::STATUS_DEPOSIT_RELEASED, self::STATUS_IN_PROGRESS, self::STATUS_PAUSED],
        self::STATUS_DEPOSIT_RELEASED => [self::STATUS_IN_PROGRESS, self::STATUS_PAUSED, self::STATUS_DEPOSIT_BLOCKED, self::STATUS_WAITING_PARTNER],
        self::STATUS_FINISHED         => [self::STATUS_IN_PROGRESS],
        self::STATUS_WAITING          => [self::STATUS_IN_PROGRESS],
    ];

    /** Destinos que exigem motivo. */
    public const REASON_REQUIRED_TARGETS = [
        self::STATUS_PAUSED,
        self::STATUS_WAITING_PARTNER,
        self::STATUS_DEPOSIT_BLOCKED,
    ];

    /** Status em que o usuário ainda pode concluir (finish). */
    public const FINISHABLE_STATUSES = [
        self::STATUS_ASSIGNED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_PAUSED,
        self::STATUS_DEPOSIT_RELEASED,
    ];

    /** Status em que a Ordem ainda está sob tratativa/posse (podem ser movidos entre responsáveis). */
    public const WORKING_STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_ASSIGNED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_WAITING_PARTNER,
        self::STATUS_DEPOSIT_BLOCKED,
        self::STATUS_DEPOSIT_RELEASED,
        self::STATUS_PAUSED,
    ];

    public const EVENT_CLAIMED = 'CLAIMED';

    public const EVENT_ASSIGNED = 'ASSIGNED';

    public const EVENT_TRANSFERRED = 'TRANSFERRED';

    public const EVENT_RETURNED_TO_POOL = 'RETURNED_TO_POOL';

    public const EVENT_STARTED = 'STARTED';

    public const EVENT_WAITING_RETURN = 'WAITING_RETURN';

    public const EVENT_PRIORITY_CHANGED = 'PRIORITY_CHANGED';

    public const EVENT_PARTNER_DEPOSIT_BLOCKED = 'PARTNER_DEPOSIT_BLOCKED';

    public const EVENT_PARTNER_DEPOSIT_RELEASED = 'PARTNER_DEPOSIT_RELEASED';

    /** Status que um bloqueio de depósito da parceira alcança (Ordens em tratativa ativa, sem pendência nem conclusão). */
    public const DEPOSIT_BLOCKABLE_STATUSES = [self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS, self::STATUS_PAUSED, self::STATUS_DEPOSIT_RELEASED];

    public const EVENT_ISSUE_BLOCKED = 'ISSUE_BLOCKED';

    public const EVENT_ISSUE_RELEASED = 'ISSUE_RELEASED';

    public const EVENT_STATUS_CHANGED = 'STATUS_CHANGED';

    public const EVENT_FINISHED = 'FINISHED';

    public const EVENT_WAITING_FUTURE = 'WAITING_FUTURE';

    public const EVENT_WAITING_PROMOTED = 'WAITING_PROMOTED';

    public const EVENT_SAP_CLOSED = 'SAP_CLOSED';

    public const EVENT_SAP_CANCELLED = 'SAP_CANCELLED';

    /** RI (Retorno Interno) com o Desenho: trava SOBREPOSTA às Ordens da Nota (não é um status operacional). Contrato: docs/encerramento/CONTRATO_RI_ENCERRAMENTO.md. */
    public const EVENT_INTERNAL_RETURN_SENT = 'RI_SENT';

    public const EVENT_INTERNAL_RETURN_RETURNED = 'RI_RETURNED';

    public const EVENT_INTERNAL_RETURN_CANCELLED = 'RI_CANCELLED';

    /**
     * Eventos que PASSAM pela trava do RI, para ela nunca prender a Ordem: liberar uma pendência ou um depósito já em curso.
     * O sync do SAP (encerrada/cancelada) e o registro dos próprios eventos do RI não usam o guard (guard = false).
     */
    public const INTERNAL_RETURN_LOCK_EXEMPT_EVENTS = [self::EVENT_ISSUE_RELEASED, self::EVENT_PARTNER_DEPOSIT_RELEASED];

    public function __construct(
        private ?ClosureMetaClassifier $classifier = null,
        private ?ClosureSettings $settings = null,
    ) {
        $this->classifier ??= new ClosureMetaClassifier();
        $this->settings ??= new ClosureSettings();
    }

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
        return $this->notifyNewAssignee(
            $this->moveToDestination($state, $company, $user, $actor, self::EVENT_ASSIGNED, $reason, $batchId, false),
            $actor,
        );
    }

    public function transfer(ClosureOrderState $state, Company $company, ?User $user, User $actor, string $reason, ?string $batchId = null): ClosureOrderState
    {
        return $this->notifyNewAssignee(
            $this->moveToDestination($state, $company, $user, $actor, self::EVENT_TRANSFERRED, $reason, $batchId, true),
            $actor,
        );
    }

    /**
     * Avisa (sino + toast) o novo responsável uma única vez por transição; o notifier
     * ignora o autor, adia para após o commit e deduplica por estado/usuário/entrada no status.
     */
    private function notifyNewAssignee(ClosureOrderState $state, User $actor): ClosureOrderState
    {
        if ($state->assigned_user_id && (string) $state->assigned_user_id !== (string) $actor->id) {
            app(ClosureNotifier::class)->orderAssigned($state, $actor);
        }

        return $state;
    }

    public function returnToPool(ClosureOrderState $state, User $actor, string $reason, ?string $batchId = null): ClosureOrderState
    {
        $this->requireReason($reason);

        return $this->transition($state, $actor, self::EVENT_RETURNED_TO_POOL, $batchId, function (ClosureOrderState $state) use ($reason): array {
            $this->requireStatus($state, array_diff(self::WORKING_STATUSES, [self::STATUS_AVAILABLE]));
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
                'operational_status'        => self::STATUS_WAITING_PARTNER,
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
            $this->requireStatus($state, array_diff(self::WORKING_STATUSES, [self::STATUS_WAITING_PARTNER]));

            return [
                'operational_status'        => self::STATUS_WAITING_PARTNER,
                'current_holder_type'       => self::HOLDER_SYSTEM,
                'current_holder_user_id'    => null,
                'current_holder_company_id' => null,
                'reason'                    => "Pendência #{$issueId}: " . trim($reason),
            ];
        });
    }

    /** Bloqueio de depósito (ação central por depósito: sinalizada pela parceira ou confirmada do calendário); a posse da Ordem não muda. */
    public function blockDepositByPartner(ClosureOrderState $state, User $actor, int $signalId, string $reason): ClosureOrderState
    {
        $this->requireReason($reason);

        return $this->transition($state, $actor, self::EVENT_PARTNER_DEPOSIT_BLOCKED, null, function (ClosureOrderState $state) use ($signalId, $reason): array {
            $this->requireStatus($state, self::DEPOSIT_BLOCKABLE_STATUSES);

            return [
                'operational_status' => self::STATUS_DEPOSIT_BLOCKED,
                'reason'             => "Depósito bloqueado (sinalização #{$signalId}): " . trim($reason),
            ];
        });
    }

    public function releaseDepositByPartner(ClosureOrderState $state, User $actor, int $signalId, string $previousStatus): ClosureOrderState
    {
        $target = in_array($previousStatus, self::DEPOSIT_BLOCKABLE_STATUSES, true) ? $previousStatus : self::STATUS_IN_PROGRESS;

        return $this->transition($state, $actor, self::EVENT_PARTNER_DEPOSIT_RELEASED, null, function (ClosureOrderState $state) use ($signalId, $target): array {
            $this->requireStatus($state, [self::STATUS_DEPOSIT_BLOCKED]);

            return [
                'operational_status' => $target,
                'reason'             => "Depósito liberado (sinalização #{$signalId}); atividade devolvida ao ponto anterior.",
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
            $this->requireStatus($state, [self::STATUS_WAITING_PARTNER]);

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

    /**
     * Mudança manual de status pelo dono da atividade (ou gestor), conforme MANUAL_TRANSITIONS.
     */
    public function changeStatus(ClosureOrderState $state, string $newStatus, User $actor, ?string $reason = null, ?string $batchId = null): ClosureOrderState
    {
        if (in_array($newStatus, self::REASON_REQUIRED_TARGETS, true)) {
            $this->requireReason((string) $reason);
        }

        return $this->transition($state, $actor, self::EVENT_STATUS_CHANGED, $batchId, function (ClosureOrderState $state) use ($newStatus, $actor, $reason): array {
            $this->assertOwnerOrManager($state, $actor);

            $allowed = self::MANUAL_TRANSITIONS[$state->operational_status] ?? [];

            if (!in_array($newStatus, $allowed, true)) {
                throw new ClosureOrderWorkflowException('A transição não é permitida no estado operacional atual.');
            }

            if (in_array($state->operational_status, [self::STATUS_FINISHED, self::STATUS_WAITING], true)) {
                $this->requireReason((string) $reason);
            }

            if ($state->operational_status === self::STATUS_WAITING_PARTNER && $this->hasOpenBlockingIssue($state)) {
                throw new ClosureOrderWorkflowException('Há pendência bloqueadora aberta; resolva-a para sair de Aguardando Parceira.');
            }

            $changes = ['operational_status' => $newStatus, 'reason' => $reason !== null && trim($reason) !== '' ? trim($reason) : null];

            if ($newStatus === self::STATUS_WAITING_PARTNER) {
                return $changes + [
                    'current_holder_type'       => self::HOLDER_SYSTEM,
                    'current_holder_user_id'    => null,
                    'current_holder_company_id' => null,
                ];
            }

            return $changes + $this->workingHolder($state);
        });
    }

    /**
     * Conclusão técnica pelo usuário. Meta futura (F): recusa, ou vai para WAITING quando permitido nas configurações.
     */
    public function finish(ClosureOrderState $state, User $actor, ?string $batchId = null): ClosureOrderState
    {
        return $this->transition($state, $actor, self::EVENT_FINISHED, $batchId, function (ClosureOrderState $state) use ($actor): array {
            $this->assertOwnerOrManager($state, $actor);
            $this->requireStatus($state, self::FINISHABLE_STATUSES);

            $order = Order::query()->findOrFail($state->order_id);
            $type  = $this->classifier->typeFor($order);

            if ($type === ClosureMetaClassifier::TYPE_FUTURE) {
                if (!$this->settings->allowFutureCompletion()) {
                    throw new ClosureOrderWorkflowException('Ordem de meta futura não pode ser finalizada antes do mês da meta.');
                }

                return [
                    'operational_status' => self::STATUS_WAITING,
                    'reason'             => 'Finalizada antes da meta (meta futura); aguardando o mês da meta.',
                    'event_type'         => self::EVENT_WAITING_FUTURE,
                    'meta'               => ['meta_type' => $type],
                ];
            }

            return [
                'operational_status' => self::STATUS_FINISHED,
                'meta'               => ['meta_type' => $type],
            ];
        });
    }

    /**
     * WAITING -> FINISHED quando a Ordem deixa de ser meta futura. Rotina diária (sistema).
     *
     * @return int quantidade promovida
     */
    public function promoteWaiting(): int
    {
        $promoted = 0;

        ClosureOrderState::query()
            ->where('operational_status', self::STATUS_WAITING)
            ->with('Order')
            ->chunkById(200, function ($states) use (&$promoted) {
                $types = $this->classifier->typesFor($states->pluck('Order')->filter());

                foreach ($states as $state) {
                    $type = $types->get($state->order_id);

                    if (!in_array($type, [ClosureMetaClassifier::TYPE_PASSIVE, ClosureMetaClassifier::TYPE_META], true)) {
                        continue;
                    }

                    try {
                        $this->transition($state, null, self::EVENT_WAITING_PROMOTED, null, fn () => [
                            'operational_status' => self::STATUS_FINISHED,
                            'reason'             => 'Meta futura chegou; atividade finalizada promovida automaticamente.',
                            'meta'               => ['meta_type' => $type],
                        ], true, self::ORIGIN_SCHEDULER);
                        $promoted++;
                    } catch (ClosureOrderWorkflowException) {
                        // Ordem cancelada/encerrada no SAP: tratada por syncFromSap().
                    }
                }
            });

        return $promoted;
    }

    /**
     * Detecta encerramento/cancelamento no SAP. Idempotente; nunca altera tipo já congelado.
     *
     * @return string|null novo status (CLOSED/CANCELLED) ou null quando nada mudou
     */
    public function syncFromSap(ClosureOrderState $state): ?string
    {
        $result = null;

        DB::transaction(function () use ($state, &$result) {
            $locked = ClosureOrderState::query()->whereKey($state->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->operational_status, [self::STATUS_CLOSED, self::STATUS_CANCELLED], true)) {
                return;
            }

            $order = Order::query()->whereKey($locked->order_id)->firstOrFail();

            if ($order->canceled) {
                $result = self::STATUS_CANCELLED;
                $this->transition($locked, null, self::EVENT_SAP_CANCELLED, null, fn () => [
                    'operational_status'          => self::STATUS_CANCELLED,
                    'cancelled_at'                => now(),
                    'cancelled_reason'            => 'Ordem cancelada.',
                    'last_status_sist_seen'       => $order->statusSist,
                    'last_status_sist_checked_at' => now(),
                    'reason'                      => 'Ordem cancelada.',
                ], true, self::ORIGIN_SAP_SYNC, false);
            } elseif ($this->isSapClosed((string) $order->statusSist)) {
                $result = self::STATUS_CLOSED;
                // Referência = última vez que a Ordem foi vista aberta no SAP (anterior à detecção); senão, agora.
                $asOf   = $locked->last_status_sist_checked_at ?? now();
                $type   = $locked->closed_meta_type ?: $this->classifier->typeFor($order, $asOf);
                $period = $locked->closed_meta_period ?: ClosureMetaClassifier::periodFor($asOf);

                $this->transition($locked, null, self::EVENT_SAP_CLOSED, null, fn () => [
                    'operational_status'          => self::STATUS_CLOSED,
                    'closed_at'                   => now(),
                    'closed_meta_type'            => $type,
                    'closed_meta_period'          => $period,
                    'last_status_sist_seen'       => $order->statusSist,
                    'last_status_sist_checked_at' => now(),
                    'reason'                      => "SAP: {$order->statusSist}",
                    'meta'                        => ['meta_type' => $type],
                ], true, self::ORIGIN_SAP_SYNC, false);
            } else {
                if ($locked->last_status_sist_seen !== $order->statusSist) {
                    $locked->update(['last_status_sist_seen' => $order->statusSist]);
                }

                $locked->update(['last_status_sist_checked_at' => now()]);

                return;
            }

            // Estado terminal não detém posse: encerra o período recém-aberto.
            $final = $locked->fresh();
            ClosureOrderStatePeriod::query()
                ->where('closure_order_state_id', $final->id)
                ->whereNull('ended_at')
                ->update(['ended_at' => now(), 'ended_event_id' => $final->last_event_id]);
        }, 3);

        return $result;
    }

    private function assertOwnerOrManager(ClosureOrderState $state, User $actor): void
    {
        $isOwner = $state->assigned_user_id && (string) $state->assigned_user_id === (string) $actor->id;

        if (!$isOwner && !$actor->can('closure.manage')) {
            throw new ClosureOrderWorkflowException('Somente o responsável pela atividade ou um gestor pode realizar esta ação.');
        }
    }

    private function hasOpenBlockingIssue(ClosureOrderState $state): bool
    {
        return ClosureIssue::query()
            ->where('closure_order_state_id', $state->id)
            ->where('blocking', true)
            ->where('status', '!=', ClosureIssue::STATUS_RESOLVED)
            ->exists();
    }

    /** @return array<string, mixed> */
    private function workingHolder(ClosureOrderState $state): array
    {
        $companyId = $state->current_holder_company_id
            ?? Production::query()->whereKey($state->production_id)->value('company_id');

        return [
            'current_holder_type'       => $state->assigned_user_id ? self::HOLDER_INTERNAL_USER : self::HOLDER_PARTNER_COMPANY,
            'current_holder_user_id'    => $state->assigned_user_id,
            'current_holder_company_id' => $companyId,
        ];
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
            $this->requireStatus($state, self::WORKING_STATUSES);

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
     * @param callable(ClosureOrderState): array<string, mixed> $changes pode devolver `reason`, `meta` (extra) e `event_type` (substitui o tipo do evento)
     */
    private function transition(
        ClosureOrderState $state,
        ?User $actor,
        string $eventType,
        ?string $batchId,
        callable $changes,
        bool $rotatePeriod = true,
        string $origin = self::ORIGIN_UI,
        bool $guard = true,
    ): ClosureOrderState {
        return DB::transaction(function () use ($state, $actor, $eventType, $batchId, $changes, $rotatePeriod, $origin, $guard) {
            $state = ClosureOrderState::query()->whereKey($state->id)->lockForUpdate()->firstOrFail();
            $order = Order::query()->whereKey($state->order_id)->lockForUpdate()->firstOrFail();

            if ($guard) {
                $this->assertOrderCanChange($order, $state);
                $this->assertNotLockedByInternalReturn($order, $eventType);
            }

            $before    = $this->snapshot($state);
            $data      = $changes($state);
            $reason    = $data['reason'] ?? null;
            $extra     = $data['meta'] ?? [];
            $eventType = $data['event_type'] ?? $eventType;
            unset($data['reason'], $data['meta'], $data['event_type']);

            if ($rotatePeriod) {
                $data['entered_current_status_at'] = now();
            }

            $state->update($data);
            $state->refresh();

            $meta = array_filter(['reason' => $reason] + $extra, fn ($value) => $value !== null);

            $event = ClosureOrderEvent::query()->create([
                'closure_order_state_id' => $state->id,
                'order_id'               => $order->id,
                'actor_id'               => $actor?->id,
                'type'                   => $eventType,
                'origin'                 => $origin,
                'before_data'            => $before,
                'after_data'             => $this->snapshot($state),
                'meta'                   => $meta ?: null,
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

    /** Trava do RI: enquanto houver RI ativo para a Nota da Ordem, nenhuma ação (fora da lista branca) é aceita. */
    private function assertNotLockedByInternalReturn(Order $order, string $eventType): void
    {
        if (in_array($eventType, self::INTERNAL_RETURN_LOCK_EXEMPT_EVENTS, true)) {
            return;
        }

        if (ClosureInternalReturn::query()->active()->where('note_id', $order->note_id)->exists()) {
            throw new ClosureOrderWorkflowException('A Ordem está travada: há um RI (Retorno Interno) com o Desenho para esta obra. Aguarde o retorno ou peça à gestão para cancelar o RI.');
        }
    }

    /**
     * Registra na linha do tempo da Ordem um evento do RI (RI_SENT / RI_RETURNED / RI_CANCELLED). Não muda status, posse nem período,
     * e não passa pelo guard (é o próprio RI que trava/destrava). Ordens encerradas ou canceladas não recebem o evento.
     *
     * @param array<string, mixed> $meta
     */
    public function recordInternalReturnEvent(ClosureOrderState $state, ?User $actor, string $eventType, string $reason, string $batchId, array $meta = [], string $origin = self::ORIGIN_UI): ?ClosureOrderState
    {
        if (in_array($state->operational_status, [self::STATUS_CLOSED, self::STATUS_CANCELLED], true)) {
            return null;
        }

        return $this->transition($state, $actor, $eventType, $batchId, fn () => ['reason' => $reason, 'meta' => $meta], false, $origin, false);
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
