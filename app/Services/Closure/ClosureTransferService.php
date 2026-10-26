<?php

namespace App\Services\Closure;

use App\Models\Closure\{ClosureOrderState, ClosureTransferRequest, ClosureTransferRequestItem};
use App\Models\{Company, User};
use Illuminate\Support\Facades\{DB, Gate};
use Illuminate\Support\Str;

/**
 * Transferência de atividade ENTRE USUÁRIOS do Encerramento, sem aprovação da gestão:
 *  - quem tem as Ordens (responsável) envia a outro usuário do Encerramento (com empresa);
 *  - o destinatário aceita (as Ordens passam a ele, pela transição `transfer` do workflow) ou rejeita;
 *  - quem enviou pode cancelar enquanto estiver pendente.
 * Enquanto pendente, as Ordens seguem com quem enviou (não há trava). Ao aceitar, a Ordem que já mudou de responsável, foi encerrada
 * ou está travada por RI é pulada e o motivo é registrado no item.
 */
class ClosureTransferService
{
    /** Status em que a Ordem pode ser transferida (as de trabalho com responsável). */
    public const TRANSFERABLE_STATUSES = [
        ClosureOrderWorkflowService::STATUS_ASSIGNED,
        ClosureOrderWorkflowService::STATUS_IN_PROGRESS,
        ClosureOrderWorkflowService::STATUS_DEPOSIT_BLOCKED,
        ClosureOrderWorkflowService::STATUS_DEPOSIT_RELEASED,
        ClosureOrderWorkflowService::STATUS_PAUSED,
    ];

    public function __construct(private ClosureOrderWorkflowService $workflow, private ClosureInternalReturnService $internalReturns)
    {
    }

    /** Pode receber transferência: usuário do Encerramento (gate closure.work) e com empresa. */
    public function canReceive(User $user): bool
    {
        return $user->company_id !== null && Gate::forUser($user)->allows('closure.work');
    }

    /** @param array<int, int> $stateIds */
    public function send(User $from, User $to, array $stateIds, string $reason): ClosureTransferRequest
    {
        $reason   = trim($reason);
        $stateIds = array_values(array_unique(array_map('intval', $stateIds)));

        if ($stateIds === []) {
            throw new ClosureTransferException('Selecione ao menos uma Ordem para transferir.');
        }

        if ($reason === '') {
            throw new ClosureTransferException('Informe o motivo da transferência.');
        }

        if ((string) $from->id === (string) $to->id) {
            throw new ClosureTransferException('Escolha outro usuário: você já é o responsável.');
        }

        if (!$this->canReceive($to)) {
            throw new ClosureTransferException('O usuário de destino precisa ser do Encerramento e ter empresa.');
        }

        $request = DB::transaction(function () use ($from, $to, $stateIds, $reason) {
            $states = ClosureOrderState::query()->whereIn('id', $stateIds)->lockForUpdate()->with('Order:id,ordem,note_id')->get();

            if ($states->count() !== count($stateIds)) {
                throw new ClosureTransferException('Alguma Ordem selecionada não foi encontrada.');
            }

            foreach ($states as $state) {
                $this->assertSendable($state, $from);
            }

            $request = ClosureTransferRequest::query()->create([
                'from_user_id' => $from->id,
                'to_user_id'   => $to->id,
                'status'       => ClosureTransferRequest::STATUS_PENDING,
                'reason'       => $reason,
            ]);

            foreach ($states as $state) {
                $request->Items()->create(['closure_order_state_id' => $state->id]);
            }

            return $request;
        });

        app(ClosureNotifier::class)->transferChanged($request, 'sent');

        return $request;
    }

    /** Destinatário aceita: move cada Ordem ainda válida; devolve o pedido com o resultado por item. */
    public function accept(ClosureTransferRequest $request, User $actor): ClosureTransferRequest
    {
        $request = DB::transaction(function () use ($request, $actor) {
            $request = $this->lockPending($request);
            $this->assertRecipient($request, $actor);

            $company = Company::query()->find($actor->company_id);

            if (!$company) {
                throw new ClosureTransferException('Seu usuário precisa ter empresa para receber Ordens.');
            }

            $batch  = (string) Str::uuid();
            $reason = 'Transferência de ' . ($request->From?->name ?? 'usuário') . ' aceita por ' . $actor->name . ': ' . $request->reason;

            foreach ($request->Items()->with('State')->get() as $item) {
                $state = $item->State;

                try {
                    if ((string) $state->assigned_user_id !== (string) $request->from_user_id) {
                        throw new ClosureOrderWorkflowException('A Ordem já não está com quem enviou.');
                    }

                    if (!in_array($state->operational_status, self::TRANSFERABLE_STATUSES, true)) {
                        throw new ClosureOrderWorkflowException('A Ordem mudou de status e não pode mais ser transferida.');
                    }

                    $this->workflow->transfer($state, $company, $actor, $actor, $reason, $batch);
                    $item->update(['result' => ClosureTransferRequestItem::RESULT_MOVED]);
                } catch (ClosureOrderWorkflowException $exception) {
                    $item->update(['result' => ClosureTransferRequestItem::RESULT_SKIPPED, 'result_reason' => Str::limit($exception->getMessage(), 250, '')]);
                }
            }

            $request->update(['status' => ClosureTransferRequest::STATUS_ACCEPTED, 'decided_at' => now()]);

            return $request;
        });

        app(ClosureNotifier::class)->transferChanged($request->fresh(['Items']), 'accepted');

        return $request->fresh(['Items.State.Order']);
    }

    public function reject(ClosureTransferRequest $request, User $actor, ?string $note = null): ClosureTransferRequest
    {
        $request = DB::transaction(function () use ($request, $actor, $note) {
            $request = $this->lockPending($request);
            $this->assertRecipient($request, $actor);
            $request->update(['status' => ClosureTransferRequest::STATUS_REJECTED, 'decided_at' => now(), 'decision_note' => $note !== null && trim($note) !== '' ? trim($note) : null]);

            return $request;
        });

        app(ClosureNotifier::class)->transferChanged($request, 'rejected');

        return $request;
    }

    /** Quem enviou cancela enquanto estiver pendente. */
    public function cancel(ClosureTransferRequest $request, User $actor): ClosureTransferRequest
    {
        $request = DB::transaction(function () use ($request, $actor) {
            $request = $this->lockPending($request);

            if ((string) $request->from_user_id !== (string) $actor->id) {
                throw new ClosureTransferException('Somente quem enviou a transferência pode cancelá-la.');
            }

            $request->update(['status' => ClosureTransferRequest::STATUS_CANCELLED, 'decided_at' => now()]);

            return $request;
        });

        app(ClosureNotifier::class)->transferChanged($request, 'cancelled');

        return $request;
    }

    /** Pedidos pendentes que o usuário recebeu (para o menu e a tela). */
    public function pendingCountFor(User $user): int
    {
        return ClosureTransferRequest::query()->where('to_user_id', $user->id)->where('status', ClosureTransferRequest::STATUS_PENDING)->count();
    }

    private function assertSendable(ClosureOrderState $state, User $from): void
    {
        $label = $state->Order?->ordem ?? "#{$state->id}";

        if ((string) $state->assigned_user_id !== (string) $from->id) {
            throw new ClosureTransferException("A Ordem {$label} não está com você.");
        }

        if (!in_array($state->operational_status, self::TRANSFERABLE_STATUSES, true)) {
            throw new ClosureTransferException("A Ordem {$label} está em um status que não permite transferir.");
        }

        if ($this->internalReturns->lockFor($state) !== null) {
            throw new ClosureTransferException("A Ordem {$label} está travada por um RI com o Desenho.");
        }

        $pending = ClosureTransferRequestItem::query()
            ->where('closure_order_state_id', $state->id)
            ->whereHas('Request', fn ($q) => $q->where('status', ClosureTransferRequest::STATUS_PENDING))
            ->exists();

        if ($pending) {
            throw new ClosureTransferException("A Ordem {$label} já tem uma transferência pendente. Cancele-a antes de enviar de novo.");
        }
    }

    private function assertRecipient(ClosureTransferRequest $request, User $actor): void
    {
        if ((string) $request->to_user_id !== (string) $actor->id) {
            throw new ClosureTransferException('Somente o destinatário pode aceitar ou rejeitar a transferência.');
        }
    }

    private function lockPending(ClosureTransferRequest $request): ClosureTransferRequest
    {
        $locked = ClosureTransferRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

        if (!$locked->isPending()) {
            throw new ClosureTransferException('Esta transferência já foi decidida ou cancelada.');
        }

        return $locked;
    }
}
