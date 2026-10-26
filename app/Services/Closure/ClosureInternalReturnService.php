<?php

namespace App\Services\Closure;

use App\Models\Closure\{ClosureInternalReturn, ClosureOrderState};
use App\Models\{Note, Reclaim, Service, Subcategory, User};
use Illuminate\Database\QueryException;
use Illuminate\Support\{Collection, Str};
use Illuminate\Support\Facades\DB;

/**
 * RI (Retorno Interno) enviado ao Desenho a partir do Encerramento. Contrato completo (ações × estados, invariantes, lista branca):
 * docs/encerramento/CONTRATO_RI_ENCERRAMENTO.md.
 *
 *  - O RI é da NOTA e vale para todas as Ordens dela: enquanto ativo (SENT), todas ficam travadas por um guard no
 *    ClosureOrderWorkflowService::transition() — trava SOBREPOSTA, o status/posse das Ordens não muda.
 *  - Criar o RI reproduz o fluxo existente de `ReturnD5` (Reclaim + comentário + viabilidades em status 11). O Desenho trata na tela dele.
 *  - O RI retorna quando o `Reclaim` fica `completed` (observer pós-save + varredura `closure:sync-internal-returns`); é idempotente.
 *  - Cancelar só DESTRAVA as Ordens do Encerramento. Não fecha o Reclaim nem mexe nas viabilidades: isso é de outra atividade.
 *  - Reabertura do Reclaim pelo Desenho avisa, mas NUNCA re-trava sozinha (novo RI é sempre explícito, ciclo +1).
 *  - `closed_at` é preenchido em todo encerramento do RI (libera o índice único de "um RI ativo por Nota").
 */
class ClosureInternalReturnService
{
    public const DESIGN_FOLDER = 'desenho';

    /** Status da viabilidade "Criado RI" (App\Custom\Viabilitiesstatus), definido pelo fluxo existente ao criar o Reclaim. */
    private const VIABILITY_STATUS_RI_CREATED = 11;

    public function __construct(private ?ClosureOrderWorkflowService $workflow = null)
    {
        $this->workflow ??= app(ClosureOrderWorkflowService::class);
    }

    // ------------------------------------------------------------------ consulta

    public function activeForNote(int $noteId): ?ClosureInternalReturn
    {
        return ClosureInternalReturn::query()->active()->where('note_id', $noteId)->first();
    }

    /**
     * RI ativos por Nota, para listas (uma consulta só).
     *
     * @param iterable<int, int> $noteIds
     *
     * @return Collection<int, ClosureInternalReturn> note_id => RI ativo
     */
    public function activeForNotes(iterable $noteIds): Collection
    {
        $ids = collect($noteIds)->unique()->values();

        return $ids->isEmpty() ? collect() : ClosureInternalReturn::query()->active()->whereIn('note_id', $ids)->get()->keyBy('note_id');
    }

    /** Motivo da trava para a UI (selo "Travada · …" e tooltip dos botões desabilitados), ou null quando a Ordem está livre. */
    public function lockFor(ClosureOrderState $state): ?array
    {
        $state->loadMissing('Order');
        $ri = $state->Order ? $this->activeForNote((int) $state->Order->note_id) : null;

        return $ri ? [
            'reason'             => 'RI com o Desenho',
            'since'              => $ri->sent_at,
            'internal_return_id' => $ri->id,
        ] : null;
    }

    // ------------------------------------------------------------------ ações

    public function send(Note $note, User $actor, string $message, ?Subcategory $subcategory = null): ClosureInternalReturn
    {
        if (!$actor->can('closure.manage') && !$actor->can('closure.work')) {
            throw new ClosureInternalReturnException('Você não tem permissão para enviar um RI.');
        }

        $message = trim($message);

        if ($message === '') {
            throw new ClosureInternalReturnException('Informe o comentário do RI.');
        }

        $batch = (string) Str::uuid();

        $internalReturn = DB::transaction(function () use ($note, $actor, $message, $batch, $subcategory) {
            $note = Note::query()->whereKey($note->id)->lockForUpdate()->firstOrFail();

            if ($note->canceled) {
                throw new ClosureInternalReturnException('A Nota/OV está cancelada.');
            }

            $design = Service::query()->where('folder', self::DESIGN_FOLDER)->first();

            if (!$design) {
                throw new ClosureInternalReturnException('O serviço Desenho não está cadastrado.');
            }

            $states = $this->activeStates($note->id, lock: true);

            if ($states->isEmpty()) {
                throw new ClosureInternalReturnException('Esta obra não tem Ordens ativas no Encerramento.');
            }

            if ($this->activeForNote($note->id)) {
                throw new ClosureInternalReturnException('Já existe um RI ativo para esta obra.');
            }

            if (Reclaim::hasActiveForService($note->id, $design->uuid)) {
                throw new ClosureInternalReturnException('O Desenho já tem um retorno interno em andamento para esta obra. Aguarde a conclusão.');
            }

            if (!$note->Viabilities()->exists()) {
                throw new ClosureInternalReturnException('A obra não tem viabilidade vinculada: não é possível abrir o RI.');
            }

            // Mesmo fluxo de Construction\Hiring\Actions\ReturnD5: Reclaim + comentário + viabilidades em "Criado RI".
            // Categoria/subcategoria do Retorno Interno (as mesmas do RI da Contratação), para o Desenho saber o motivo.
            $reclaim = Reclaim::create([
                'note_id'        => $note->id,
                'service_id'     => $design->uuid,
                'subcategory_id' => $subcategory?->id,
                'category'       => $subcategory?->Category?->name,
            ]);
            $reclaim->Comments()->create(['user_id' => $actor->id, 'message' => $message]);

            foreach ($note->Viabilities as $viability) {
                $viability->update(['status' => self::VIABILITY_STATUS_RI_CREATED]);
                $viability->Reclaims()->attach($reclaim->id);
            }

            try {
                $internalReturn = ClosureInternalReturn::query()->create([
                    'note_id'           => $note->id,
                    'reclaim_id'        => $reclaim->id,
                    'design_service_id' => $design->uuid,
                    'status'            => ClosureInternalReturn::STATUS_SENT,
                    'cycle'             => (int) ClosureInternalReturn::query()->where('note_id', $note->id)->max('cycle') + 1,
                    'message'           => $message,
                    'sent_by'           => $actor->id,
                    'sent_at'           => now(),
                ]);
            } catch (QueryException $exception) {
                // Duas sessões ao mesmo tempo: o índice único de RI ativo por Nota segura a segunda.
                throw new ClosureInternalReturnException('Já existe um RI ativo para esta obra.', previous: $exception);
            }

            $this->recordEvents($states, $actor, ClosureOrderWorkflowService::EVENT_INTERNAL_RETURN_SENT, 'RI enviado ao Desenho: ' . $message, $batch, $internalReturn, ClosureOrderWorkflowService::ORIGIN_UI);

            return $internalReturn;
        }, 3);

        app(ClosureNotifier::class)->internalReturnChanged($internalReturn, $actor, ClosureNotifier::RI_SENT);

        return $internalReturn->fresh();
    }

    /**
     * Fecha o RI quando o Reclaim foi concluído. Idempotente: RI que já não está ativo, ou Reclaim ainda aberto, não muda nada.
     *
     * @return bool true quando este chamado encerrou o RI
     */
    public function syncFromReclaim(ClosureInternalReturn $internalReturn): bool
    {
        $returned = DB::transaction(function () use ($internalReturn) {
            $locked = ClosureInternalReturn::query()->whereKey($internalReturn->id)->lockForUpdate()->first();

            if (!$locked || !$locked->isActive()) {
                return null;
            }

            $reclaim = $locked->reclaim_id ? Reclaim::query()->find($locked->reclaim_id) : null;

            if (!$reclaim || !$reclaim->completed) {
                return null;
            }

            $locked->update([
                'status'      => ClosureInternalReturn::STATUS_RETURNED,
                'returned_at' => $reclaim->completed_at ?? now(),
                'closed_at'   => now(),
            ]);

            $this->recordEvents(
                $this->activeStates($locked->note_id, lock: true),
                null,
                ClosureOrderWorkflowService::EVENT_INTERNAL_RETURN_RETURNED,
                'RI retornado pelo Desenho; Ordens destravadas.',
                (string) Str::uuid(),
                $locked,
                ClosureOrderWorkflowService::ORIGIN_SCHEDULER,
            );

            return $locked;
        }, 3);

        if ($returned) {
            app(ClosureNotifier::class)->internalReturnChanged($returned, null, ClosureNotifier::RI_RETURNED);
        }

        return $returned !== null;
    }

    /**
     * Cancelamento (só gestão): DESTRAVA as Ordens. O Reclaim segue com o Desenho — nada de outra atividade é alterado, apenas um
     * comentário informativo é acrescentado a ele. Um novo RI só poderá ser enviado depois que o Desenho concluir o atual.
     */
    public function cancel(ClosureInternalReturn $internalReturn, User $actor, string $reason): ClosureInternalReturn
    {
        if (!$actor->can('closure.manage')) {
            throw new ClosureInternalReturnException('Somente a gestão pode cancelar um RI.');
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new ClosureInternalReturnException('Informe o motivo do cancelamento.');
        }

        $cancelled = DB::transaction(function () use ($internalReturn, $actor, $reason) {
            $locked = ClosureInternalReturn::query()->whereKey($internalReturn->id)->lockForUpdate()->firstOrFail();

            if (!$locked->isActive()) {
                throw new ClosureInternalReturnException('Este RI já foi encerrado.');
            }

            $locked->update([
                'status'        => ClosureInternalReturn::STATUS_CANCELLED,
                'cancelled_by'  => $actor->id,
                'cancelled_at'  => now(),
                'cancel_reason' => $reason,
                'closed_at'     => now(),
            ]);

            $reclaim = $locked->reclaim_id ? Reclaim::query()->find($locked->reclaim_id) : null;
            $reclaim?->Comments()->create(['user_id' => $actor->id, 'message' => 'O Encerramento cancelou a espera por este RI (as Ordens foram destravadas). Motivo: ' . $reason]);

            $this->recordEvents(
                $this->activeStates($locked->note_id, lock: true),
                $actor,
                ClosureOrderWorkflowService::EVENT_INTERNAL_RETURN_CANCELLED,
                'RI cancelado: ' . $reason,
                (string) Str::uuid(),
                $locked,
                ClosureOrderWorkflowService::ORIGIN_UI,
            );

            return $locked;
        }, 3);

        app(ClosureNotifier::class)->internalReturnChanged($cancelled, $actor, ClosureNotifier::RI_CANCELLED);

        return $cancelled->fresh();
    }

    /** Reclaim salvo: conclusão fecha o RI; reabertura apenas avisa (nunca re-trava). */
    public function handleReclaimSaved(Reclaim $reclaim): void
    {
        if (!$reclaim->wasChanged('completed')) {
            return;
        }

        if ($reclaim->completed) {
            ClosureInternalReturn::query()->active()->where('reclaim_id', $reclaim->id)->get()->each(fn (ClosureInternalReturn $ri) => $this->syncFromReclaim($ri));

            return;
        }

        $returned = ClosureInternalReturn::query()
            ->where('reclaim_id', $reclaim->id)
            ->where('status', ClosureInternalReturn::STATUS_RETURNED)
            ->latest('id')
            ->first();

        if ($returned && !$this->activeForNote((int) $returned->note_id)) {
            app(ClosureNotifier::class)->internalReturnChanged($returned, null, ClosureNotifier::RI_REOPENED);
        }
    }

    /**
     * Varredura de segurança (comando agendado): o Reclaim também é concluído por caminhos que não disparam o observer
     * (UPDATE em massa, comandos). RI ativo cujo Reclaim sumiu é contado em `orphans` para a gestão cancelar.
     *
     * @return array{checked: int, returned: int, orphans: int}
     */
    public function syncAll(): array
    {
        $summary = ['checked' => 0, 'returned' => 0, 'orphans' => 0];

        ClosureInternalReturn::query()->active()->orderBy('id')->each(function (ClosureInternalReturn $ri) use (&$summary) {
            $summary['checked']++;

            if ($ri->reclaim_id === null || !Reclaim::query()->whereKey($ri->reclaim_id)->exists()) {
                $summary['orphans']++;

                return;
            }

            if ($this->syncFromReclaim($ri)) {
                $summary['returned']++;
            }
        });

        return $summary;
    }

    // ------------------------------------------------------------------ internos

    /** @return Collection<int, ClosureOrderState> */
    private function activeStates(int $noteId, bool $lock = false): Collection
    {
        return ClosureOrderState::query()
            ->whereHas('Order', fn ($order) => $order->where('note_id', $noteId))
            ->whereNotIn('operational_status', [ClosureOrderWorkflowService::STATUS_CLOSED, ClosureOrderWorkflowService::STATUS_CANCELLED])
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->orderBy('id')
            ->get();
    }

    /** @param Collection<int, ClosureOrderState> $states */
    private function recordEvents(Collection $states, ?User $actor, string $eventType, string $reason, string $batchId, ClosureInternalReturn $internalReturn, string $origin): void
    {
        foreach ($states as $state) {
            $this->workflow->recordInternalReturnEvent($state, $actor, $eventType, $reason, $batchId, [
                'internal_return_id' => $internalReturn->id,
                'reclaim_id'         => $internalReturn->reclaim_id,
                'note_id'            => $internalReturn->note_id,
            ], $origin);
        }
    }
}
