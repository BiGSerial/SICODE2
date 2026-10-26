<?php

namespace App\Services\Closure;

use App\Models\Closure\{ClosureInternalReturn, ClosureOrderEvent, ClosureOrderState, ClosureOrderStatePeriod};
use App\Models\{Company, Note, Notetimeline, Order, Production, Service, User};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cria o envelope de Produção de Encerramento por Nota e os itens granulares
 * por Ordem. A fonte de verdade operacional continua sendo ClosureOrderState;
 * Production não representa o estado individual das Ordens.
 */
class ClosureDispatchService
{
    public const SERVICE_FOLDER = 'encerramento';

    public const STATUS_AVAILABLE = 'AVAILABLE';

    public const STATUS_ASSIGNED = 'ASSIGNED';

    public const HOLDER_INTERNAL_USER = 'INTERNAL_USER';

    public const HOLDER_PARTNER_COMPANY = 'PARTNER_COMPANY';

    public const EVENT_ITEM_ADDED_TO_PRODUCTION = 'ITEM_ADDED_TO_PRODUCTION';

    public const EVENT_DISPATCHED = 'DISPATCHED';

    public const EVENT_ASSIGNED = 'ASSIGNED';

    public function __construct(private ?ClosureMetaClassifier $classifier = null)
    {
        $this->classifier ??= new ClosureMetaClassifier();
    }

    /**
     * Despacha Ordens de VÁRIAS Notas: agrupa por Nota e reutiliza dispatch() por Nota.
     * Recusas são por Ordem (sucesso parcial; nada válido é revertido). Ordem F é despachável sem ClosureTarget.
     *
     * @param array<int, int|string> $orderIds
     */
    public function dispatchOrders(array $orderIds, Company $company, ?User $assignee, User $actor): ClosureMultiDispatchResult
    {
        $orderIds = collect($orderIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($orderIds->isEmpty()) {
            throw new ClosureDispatchException('Selecione ao menos uma Ordem para despacho.');
        }

        if ($assignee && (string) $assignee->company_id !== (string) $company->id) {
            throw new ClosureDispatchException('O usuário escolhido não pertence à empresa de destino.');
        }

        $orders = Order::query()->with('ClosureTarget')->whereIn('id', $orderIds)->get()->keyBy('id');
        $types  = $this->classifier->typesFor($orders->values());
        $items  = [];
        $valid  = collect();

        foreach ($orderIds as $id) {
            $order = $orders->get($id);

            if (!$order) {
                $items[$id] = $this->item($id, null, ClosureMultiDispatchResult::STATUS_REFUSED, 'A Ordem não existe.', null);

                continue;
            }

            $reason     = $this->refusalReason($order, $types->get($id));
            $items[$id] = $reason
                ? $this->item($id, $order->note_id, ClosureMultiDispatchResult::STATUS_REFUSED, $reason, $types->get($id))
                : $this->item($id, $order->note_id, ClosureMultiDispatchResult::STATUS_DISPATCHED, null, $types->get($id));

            if (!$reason) {
                $valid->push($order);
            }
        }

        $notes = [];

        foreach ($valid->groupBy('note_id') as $noteId => $noteOrders) {
            $ids = $noteOrders->pluck('id')->all();

            try {
                $notes[$noteId] = $this->dispatchOne((int) $noteId, $ids, $company, $assignee, $actor, $items);
            } catch (ClosureDispatchException $exception) {
                if (count($ids) === 1) {
                    $items[$ids[0]]['status']  = ClosureMultiDispatchResult::STATUS_REFUSED;
                    $items[$ids[0]]['message'] = $exception->getMessage();

                    continue;
                }

                // Isola a Ordem problemática: tenta cada uma separadamente.
                foreach ($ids as $id) {
                    try {
                        $result = $this->dispatchOne((int) $noteId, [$id], $company, $assignee, $actor, $items);
                        $notes[$noteId] ??= $result;
                    } catch (ClosureDispatchException $single) {
                        $items[$id]['status']  = ClosureMultiDispatchResult::STATUS_REFUSED;
                        $items[$id]['message'] = $single->getMessage();
                    }
                }
            }
        }

        return new ClosureMultiDispatchResult(array_values($items), $notes);
    }

    /**
     * @param array<int, int> $ids
     * @param array<int, array<string, mixed>> $items atualizado por referência (despachada x já despachada)
     */
    private function dispatchOne(int $noteId, array $ids, Company $company, ?User $assignee, User $actor, array &$items): ClosureDispatchResult
    {
        $result = $this->dispatch(Note::query()->findOrFail($noteId), $ids, $company, $assignee, $actor);

        foreach ($ids as $id) {
            if (!$result->addedOrderIds->contains((int) $id)) {
                $items[$id]['status'] = ClosureMultiDispatchResult::STATUS_ALREADY;
            }
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function item(int $orderId, ?int $noteId, string $status, ?string $message, ?string $type): array
    {
        return [
            'order_id'  => $orderId,
            'note_id'   => $noteId,
            'status'    => $status,
            'message'   => $message,
            'meta_type' => $type,
        ];
    }

    /**
     * @param array<int, int|string> $orderIds
     */
    public function dispatch(Note $note, array $orderIds, Company $company, ?User $assignee, User $actor): ClosureDispatchResult
    {
        $orderIds = collect($orderIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($orderIds->isEmpty()) {
            throw new ClosureDispatchException('Selecione ao menos uma Ordem para despacho.');
        }

        return DB::transaction(function () use ($note, $orderIds, $company, $assignee, $actor) {
            $note = Note::query()->whereKey($note->id)->lockForUpdate()->firstOrFail();

            if ($note->canceled) {
                throw new ClosureDispatchException('A Nota/OV selecionada está cancelada.');
            }

            if (ClosureInternalReturn::query()->active()->where('note_id', $note->id)->exists()) {
                throw new ClosureDispatchException('A obra está travada: há um RI (Retorno Interno) com o Desenho. Aguarde o retorno antes de despachar.');
            }

            if ($assignee && (string) $assignee->company_id !== (string) $company->id) {
                throw new ClosureDispatchException('O usuário escolhido não pertence à empresa de destino.');
            }

            $service = Service::query()
                ->where('folder', self::SERVICE_FOLDER)
                ->lockForUpdate()
                ->first();

            if (!$service) {
                throw new ClosureDispatchException('O serviço técnico de Encerramento não está provisionado.');
            }

            $orders = Order::query()
                ->with('ClosureTarget')
                ->whereIn('id', $orderIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($orders->count() !== $orderIds->count()) {
                throw new ClosureDispatchException('Uma ou mais Ordens selecionadas não existem.');
            }

            $this->assertEligibleOrders($note, $orders);
            $this->assertNotAlreadyDispatched($orders);

            $openProductions = Production::query()
                ->where('note_id', $note->id)
                ->where('service_id', $service->uuid)
                ->where('completed', false)
                ->where('confirmed', false)
                ->lockForUpdate()
                ->get();

            [$production, $productionWasCreated] = $this->resolveProduction(
                $note,
                $service,
                $company,
                $assignee,
                $actor,
                $openProductions
            );

            $statesByOrderId = ClosureOrderState::query()
                ->whereIn('order_id', $orderIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('order_id');

            $this->assertNoIncompatibleState($statesByOrderId, $production);

            $states        = collect();
            $addedOrderIds = collect();

            foreach ($orders as $order) {
                $state = $statesByOrderId->get($order->id);

                if ($state) {
                    $states->push($state);

                    continue;
                }

                $state = $this->createState($order, $production, $company, $assignee);
                $this->recordDispatchEvents($state, $order, $company, $assignee, $actor);

                $states->push($state->fresh());
                $addedOrderIds->push((int) $order->id);
            }

            if ($productionWasCreated || $addedOrderIds->isNotEmpty()) {
                $this->recordAggregatedTimeline($production, $actor, $company, $assignee, $addedOrderIds->count());
            }

            return new ClosureDispatchResult(
                production: $production->fresh(),
                states: $states,
                addedOrderIds: $addedOrderIds,
                productionWasCreated: $productionWasCreated,
            );
        }, 3);
    }

    /**
     * Ordem que já tem posse (despachada, em qualquer status não terminal) NUNCA é despachada de novo: para mudar o responsável
     * existe a Transferência/Controle. Vale para a mesma empresa e responsável também (não há mais despacho "idempotente").
     *
     * @param Collection<int, Order> $orders
     */
    private function assertNotAlreadyDispatched(Collection $orders): void
    {
        $states = ClosureOrderState::query()
            ->with(['Assignee', 'CurrentHolderCompany'])
            ->whereIn('order_id', $orders->keys())
            ->lockForUpdate()
            ->get();

        foreach ($states as $state) {
            $reason = self::alreadyDispatchedReason((string) $orders->get($state->order_id)?->ordem, $state);

            if ($reason !== null) {
                throw new ClosureDispatchException($reason);
            }
        }
    }

    /** Motivo de recusa por já ter posse (null para estado terminal: encerrada/cancelada não é "em posse"). */
    public static function alreadyDispatchedReason(?string $ordem, ClosureOrderState $state): ?string
    {
        if (in_array($state->operational_status, [ClosureOrderWorkflowService::STATUS_CLOSED, ClosureOrderWorkflowService::STATUS_CANCELLED], true)) {
            return null;
        }

        $state->loadMissing(['Assignee', 'CurrentHolderCompany']);

        $heldBy = $state->Assignee?->name
            ?? ($state->CurrentHolderCompany ? 'a pilha de ' . $state->CurrentHolderCompany->name : 'outro responsável');

        return "A Ordem {$ordem} já foi despachada e está com {$heldBy}. Para mudar o responsável use a Transferência.";
    }

    /**
     * @param Collection<int, Order> $orders
     */
    private function assertEligibleOrders(Note $note, Collection $orders): void
    {
        $types = $this->classifier->typesFor($orders->values());

        foreach ($orders as $order) {
            if ((int) $order->note_id !== (int) $note->id) {
                throw new ClosureDispatchException("A Ordem {$order->ordem} não pertence à Nota selecionada.");
            }

            if ($reason = $this->refusalReason($order, $types->get($order->id))) {
                throw new ClosureDispatchException($reason);
            }
        }
    }

    /** Motivo de recusa da Ordem, ou null quando despachável (ClosureTarget OU tipo P/M/F classificável). */
    private function refusalReason(Order $order, ?string $type): ?string
    {
        if ($order->canceled) {
            return "A Ordem {$order->ordem} está cancelada.";
        }

        if ($this->isSapClosed((string) $order->statusSist)) {
            return "A Ordem {$order->ordem} já está encerrada no SAP.";
        }

        if (!$order->ClosureTarget && !$type) {
            return "A Ordem {$order->ordem} não pertence à meta de Encerramento.";
        }

        // Elegibilidade (LIB%, Nota válida, 0020 CONF); Ordem com target existente é exceção atípica.
        if (!$order->ClosureTarget && !$this->isEligible($order)) {
            return "A Ordem {$order->ordem} não está elegível para o Encerramento (status LIB, Nota válida e 0020 confirmada).";
        }

        return null;
    }

    private function isEligible(Order $order): bool
    {
        return $this->classifier
            ->scopeEligible(Order::query())
            ->whereKey($order->id)
            ->exists();
    }

    private function isSapClosed(string $status): bool
    {
        return str_starts_with($status, 'ENTE') || str_starts_with($status, 'ENCE');
    }

    /**
     * @param Collection<int, Production> $openProductions
     * @return array{0: Production, 1: bool}
     */
    private function resolveProduction(
        Note $note,
        Service $service,
        Company $company,
        ?User $assignee,
        User $actor,
        Collection $openProductions,
    ): array {
        if ($openProductions->count() > 1) {
            throw new ClosureDispatchException('Há mais de uma Produção de Encerramento aberta para esta Nota.');
        }

        $production = $openProductions->first();

        if ($production) {
            if ((string) $production->company_id !== (string) $company->id
                || (string) $production->user_id !== (string) ($assignee?->id)) {
                throw new ClosureDispatchException('Já existe uma Produção de Encerramento aberta com destino incompatível.');
            }

            return [$production, false];
        }

        return [Production::query()->create([
            'note_id'     => $note->id,
            'service_id'  => $service->uuid,
            'user_id'     => $assignee?->id,
            'company_id'  => $company->id,
            'dispatch_by' => $actor->id,
            'att_by'      => $assignee ? $actor->id : null,
            'dt_note'     => $note->dt_status,
            'status_note' => $note->status_note,
            'dispatch_at' => now(),
            'att_at'      => $assignee ? now() : null,
            'completed'   => false,
            'confirmed'   => false,
            'status'      => $assignee ? 2 : 1,
        ]), true];
    }

    /**
     * @param Collection<int, ClosureOrderState> $statesByOrderId
     */
    private function assertNoIncompatibleState(Collection $statesByOrderId, Production $production): void
    {
        foreach ($statesByOrderId as $state) {
            if ((int) $state->production_id === (int) $production->id) {
                continue;
            }

            throw new ClosureDispatchException('Uma ou mais Ordens já estão vinculadas a outra Produção de Encerramento.');
        }
    }

    private function createState(Order $order, Production $production, Company $company, ?User $assignee): ClosureOrderState
    {
        return ClosureOrderState::query()->create([
            'order_id'                  => $order->id,
            'production_id'             => $production->id,
            'operational_status'        => $assignee ? self::STATUS_ASSIGNED : self::STATUS_AVAILABLE,
            'assigned_user_id'          => $assignee?->id,
            'current_holder_type'       => $assignee ? self::HOLDER_INTERNAL_USER : self::HOLDER_PARTNER_COMPANY,
            'current_holder_user_id'    => $assignee?->id,
            'current_holder_company_id' => $company->id,
            'entered_current_status_at' => now(),
        ]);
    }

    private function recordDispatchEvents(
        ClosureOrderState $state,
        Order $order,
        Company $company,
        ?User $assignee,
        User $actor,
    ): void {
        $after    = $this->stateSnapshot($state);
        $baseMeta = [
            'destination' => [
                'company_id' => $company->id,
                'user_id'    => $assignee?->id,
            ],
        ];

        $this->event($state, $order, $actor, self::EVENT_ITEM_ADDED_TO_PRODUCTION, $after, $baseMeta);
        $dispatched = $this->event($state, $order, $actor, self::EVENT_DISPATCHED, $after, $baseMeta);
        $lastEvent  = $dispatched;

        if ($assignee) {
            $lastEvent = $this->event($state, $order, $actor, self::EVENT_ASSIGNED, $after, $baseMeta);
        }

        ClosureOrderStatePeriod::query()->create([
            'closure_order_state_id' => $state->id,
            'operational_status'     => $state->operational_status,
            'holder_type'            => $state->current_holder_type,
            'holder_user_id'         => $state->current_holder_user_id,
            'holder_company_id'      => $state->current_holder_company_id,
            'started_at'             => $state->entered_current_status_at,
            'started_event_id'       => $lastEvent->id,
        ]);

        $state->update(['last_event_id' => $lastEvent->id]);
    }

    private function event(
        ClosureOrderState $state,
        Order $order,
        User $actor,
        string $type,
        array $after,
        array $meta,
    ): ClosureOrderEvent {
        return ClosureOrderEvent::query()->create([
            'closure_order_state_id' => $state->id,
            'order_id'               => $order->id,
            'actor_id'               => $actor->id,
            'type'                   => $type,
            'origin'                 => 'UI',
            'before_data'            => null,
            'after_data'             => $after,
            'meta'                   => $meta,
        ]);
    }

    private function stateSnapshot(ClosureOrderState $state): array
    {
        return [
            'operational_status' => $state->operational_status,
            'holder_type'        => $state->current_holder_type,
            'holder_user_id'     => $state->current_holder_user_id,
            'holder_company_id'  => $state->current_holder_company_id,
        ];
    }

    private function recordAggregatedTimeline(
        Production $production,
        User $actor,
        Company $company,
        ?User $assignee,
        int $addedCount,
    ): void {
        $destination = $assignee
            ? "usuário {$assignee->name}"
            : "pilha da empresa {$company->name}";
        $count = $addedCount === 1 ? '1 Ordem' : "{$addedCount} Ordens";

        Notetimeline::query()->create([
            'note_id'       => $production->note_id,
            'service_id'    => $production->service_id,
            'production_id' => $production->id,
            'user_id'       => $actor->id,
            'info'          => "Usuário {$actor->name} despachou {$count} de Encerramento para {$destination}.",
            'status'        => $assignee ? 2 : 1,
        ]);
    }
}
