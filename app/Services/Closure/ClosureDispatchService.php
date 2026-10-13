<?php

namespace App\Services\Closure;

use App\Models\Closure\{ClosureOrderEvent, ClosureOrderState, ClosureOrderStatePeriod};
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
     * @param Collection<int, Order> $orders
     */
    private function assertEligibleOrders(Note $note, Collection $orders): void
    {
        foreach ($orders as $order) {
            if ((int) $order->note_id !== (int) $note->id) {
                throw new ClosureDispatchException("A Ordem {$order->ordem} não pertence à Nota selecionada.");
            }

            if ($order->canceled) {
                throw new ClosureDispatchException("A Ordem {$order->ordem} está cancelada.");
            }

            if (!$order->ClosureTarget) {
                throw new ClosureDispatchException("A Ordem {$order->ordem} não pertence à meta de Encerramento.");
            }

            if ($this->isSapClosed((string) $order->statusSist)) {
                throw new ClosureDispatchException("A Ordem {$order->ordem} já está encerrada no SAP.");
            }
        }
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
