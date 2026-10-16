<?php

namespace App\Services\Closure;

use App\Models\Closure\{ClosureOrderState, ClosurePriorityRequest};
use App\Models\{Note, User};
use Illuminate\Support\Facades\{DB, Gate};

/**
 * Pedido de prioridade por obra. Quem tem acesso interno à obra pede; só o
 * despachante (gate closure.dispatch) decide. Aceitar marca prioridade em
 * todas as Ordens abertas da Nota (evento PRIORITY_CHANGED por Ordem).
 */
class ClosurePriorityService
{
    public function __construct(
        private readonly ClosureOrderWorkflowService $workflow,
        private readonly ClosureWorkService $work,
        private readonly ClosureNotifier $notifier,
    ) {
    }

    public function request(Note $note, string $reason, User $actor): ClosurePriorityRequest
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new ClosurePriorityException('Informe o motivo do pedido de prioridade.');
        }

        $level = $this->work->accessLevel($note, $actor);

        if ($level === null || $level === ClosureWorkService::LEVEL_PARTNER) {
            throw new ClosurePriorityException('Você não pode pedir prioridade para esta obra.');
        }

        $request = DB::transaction(function () use ($note, $reason, $actor) {
            Note::query()->whereKey($note->id)->lockForUpdate()->firstOrFail();

            $pending = ClosurePriorityRequest::query()
                ->where('note_id', $note->id)
                ->where('status', ClosurePriorityRequest::STATUS_PENDING)
                ->exists();

            if ($pending) {
                throw new ClosurePriorityException('Já existe um pedido de prioridade pendente para esta obra.');
            }

            $request = ClosurePriorityRequest::query()->create([
                'note_id'      => $note->id,
                'requested_by' => $actor->id,
                'reason'       => $reason,
                'status'       => ClosurePriorityRequest::STATUS_PENDING,
            ]);

            $this->notifier->priorityRequested($request);

            return $request;
        }, 3);

        return $request->fresh();
    }

    public function accept(ClosurePriorityRequest $request, User $actor, ?string $decisionNote = null): ClosurePriorityRequest
    {
        $this->assertDispatcher($actor);

        return DB::transaction(function () use ($request, $actor, $decisionNote) {
            $request = $this->lockPending($request);
            $this->applyPriority($request->note_id, true, $actor);
            $this->decide($request, ClosurePriorityRequest::STATUS_ACCEPTED, $actor, $decisionNote);

            return $request->fresh();
        }, 3);
    }

    public function decline(ClosurePriorityRequest $request, User $actor, ?string $decisionNote = null): ClosurePriorityRequest
    {
        $this->assertDispatcher($actor);

        return DB::transaction(function () use ($request, $actor, $decisionNote) {
            $request = $this->lockPending($request);
            $this->decide($request, ClosurePriorityRequest::STATUS_DECLINED, $actor, $decisionNote);

            return $request->fresh();
        }, 3);
    }

    /** Remove a prioridade das Ordens abertas da Nota. Retorna quantas Ordens mudaram. */
    public function clear(Note $note, User $actor): int
    {
        $this->assertDispatcher($actor);

        return DB::transaction(fn () => $this->applyPriority($note->id, false, $actor), 3);
    }

    private function applyPriority(int $noteId, bool $priority, User $actor): int
    {
        $states = ClosureOrderState::query()
            ->whereHas('Order', fn ($q) => $q->where('note_id', $noteId))
            ->whereNotIn('operational_status', [ClosureOrderWorkflowService::STATUS_CLOSED, ClosureOrderWorkflowService::STATUS_CANCELLED])
            ->where('priority', !$priority)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $changed = 0;

        foreach ($states as $state) {
            try {
                $this->workflow->setPriority($state, $priority, $actor);
                $changed++;
            } catch (ClosureOrderWorkflowException) {
                // Ordem encerrada no SAP/cancelada: não é mais "aberta"; segue para as demais.
            }
        }

        return $changed;
    }

    private function decide(ClosurePriorityRequest $request, string $status, User $actor, ?string $note): void
    {
        $request->update([
            'status'        => $status,
            'decided_by'    => $actor->id,
            'decided_at'    => now(),
            'decision_note' => $note !== null && trim($note) !== '' ? trim($note) : null,
        ]);

        $this->notifier->priorityDecided($request->fresh());
    }

    private function lockPending(ClosurePriorityRequest $request): ClosurePriorityRequest
    {
        $request = ClosurePriorityRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

        if ($request->status !== ClosurePriorityRequest::STATUS_PENDING) {
            throw new ClosurePriorityException('Este pedido de prioridade já foi decidido.');
        }

        return $request;
    }

    private function assertDispatcher(User $actor): void
    {
        if (!Gate::forUser($actor)->allows('closure.dispatch')) {
            throw new ClosurePriorityException('Apenas o despachante pode decidir sobre prioridade.');
        }
    }
}
