<?php

use App\Models\Closure\{ClosureOrderEvent, ClosureOrderState, ClosureOrderStatePeriod};
use App\Models\{ClosureCycle, ClosureTarget, Company, Note, Operation, Order, Production, Service, User};
use App\Services\Closure\{ClosureDispatchException, ClosureDispatchService};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function closureDispatchFixture(): array
{
    $service = Service::query()->firstOrCreate(
        ['folder' => ClosureDispatchService::SERVICE_FOLDER],
        [
            'service'      => 'Encerramento',
            'project'      => true,
            'construction' => true,
            'icon'         => 'ri-checkbox-circle-line',
        ]
    );
    $company = Company::query()->create([
        'name'  => 'Parceira Encerramento',
        'email' => fake()->unique()->safeEmail(),
    ]);
    $actor = User::factory()->create();
    $note  = Note::query()->create([
        'note'      => (string) fake()->unique()->numberBetween(1000000, 9999999),
        'type_note' => 1,
        'dt_status' => now(),
    ]);
    $cycle = ClosureCycle::query()->create([
        'year'   => 2026,
        'month'  => 10,
        'label'  => '2026-10',
        'status' => ClosureCycle::STATUS_FROZEN,
    ]);

    return compact('service', 'company', 'actor', 'note', 'cycle');
}

function closureDispatchOrder(Note $note, ClosureCycle $cycle, array $attributes = []): Order
{
    $order = Order::query()->create(array_merge([
        'note_id'    => $note->id,
        'ordem'      => (string) fake()->unique()->numberBetween(100000000, 999999999),
        'statusSist' => 'LIB CNPA',
        'canceled'   => false,
    ], $attributes));

    ClosureTarget::query()->create([
        'closure_cycle_id'     => $cycle->id,
        'order_id'             => $order->id,
        'note_id'              => $note->id,
        'entry_rule'           => 'test',
        'snapshot_status_sist' => $order->statusSist,
        'frozen_at'            => now(),
    ]);

    return $order;
}

it('creates the production, granular states, events, periods, and one aggregate timeline entry', function () {
    ['company' => $company, 'actor' => $actor, 'note' => $note, 'cycle' => $cycle] = closureDispatchFixture();
    $assignee                                                                      = User::factory()->create(['company_id' => $company->id]);
    $orders                                                                        = collect([
        closureDispatchOrder($note, $cycle),
        closureDispatchOrder($note, $cycle),
    ]);

    $result = app(ClosureDispatchService::class)->dispatch($note, $orders->pluck('id')->all(), $company, $assignee, $actor);

    expect($result->productionWasCreated)->toBeTrue()
        ->and($result->addedOrderIds->all())->toEqualCanonicalizing($orders->pluck('id')->map(fn ($id) => (int) $id)->all())
        ->and($result->production->company_id)->toBe($company->id)
        ->and($result->production->user_id)->toBe($assignee->id);

    expect(Production::query()->count())->toBe(1)
        ->and(ClosureOrderState::query()->count())->toBe(2)
        ->and(ClosureOrderStatePeriod::query()->count())->toBe(2)
        ->and(ClosureOrderEvent::query()->count())->toBe(6);

    foreach ($orders as $order) {
        $state = ClosureOrderState::query()->where('order_id', $order->id)->firstOrFail();

        expect($state->production_id)->toBe($result->production->id)
            ->and($state->operational_status)->toBe(ClosureDispatchService::STATUS_ASSIGNED)
            ->and($state->current_holder_user_id)->toBe($assignee->id);

        expect(ClosureOrderEvent::query()->where('order_id', $order->id)->pluck('type')->all())
            ->toEqualCanonicalizing([
                ClosureDispatchService::EVENT_ITEM_ADDED_TO_PRODUCTION,
                ClosureDispatchService::EVENT_DISPATCHED,
                ClosureDispatchService::EVENT_ASSIGNED,
            ]);
    }

    $this->assertDatabaseCount('notetimelines', 1);
});

it('refuses to repeat an identical dispatch and creates nothing new (an Order with an owner is never dispatched again)', function () {
    ['company' => $company, 'actor' => $actor, 'note' => $note, 'cycle' => $cycle] = closureDispatchFixture();
    $order                                                                         = closureDispatchOrder($note, $cycle);
    $service                                                                       = app(ClosureDispatchService::class);

    $first = $service->dispatch($note, [$order->id], $company, null, $actor);

    expect($first->productionWasCreated)->toBeTrue()
        ->and(fn () => $service->dispatch($note, [$order->id], $company, null, $actor))->toThrow(\App\Services\Closure\ClosureDispatchException::class, 'já foi despachada')
        ->and(Production::query()->count())->toBe(1)
        ->and(ClosureOrderState::query()->count())->toBe(1)
        ->and(ClosureOrderStatePeriod::query()->count())->toBe(1)
        ->and(ClosureOrderEvent::query()->count())->toBe(2);

    $this->assertDatabaseCount('notetimelines', 1);
});

it('refuses an ineligible order before creating any dispatch records', function (array $attributes, bool $createTarget, string $message) {
    ['company' => $company, 'actor' => $actor, 'note' => $note, 'cycle' => $cycle] = closureDispatchFixture();
    $order                                                                         = Order::query()->create(array_merge([
        'note_id'    => $note->id,
        'ordem'      => (string) fake()->unique()->numberBetween(100000000, 999999999),
        'statusSist' => 'LIB CNPA',
        'canceled'   => false,
    ], $attributes));

    if ($createTarget) {
        ClosureTarget::query()->create([
            'closure_cycle_id'     => $cycle->id,
            'order_id'             => $order->id,
            'note_id'              => $note->id,
            'entry_rule'           => 'test',
            'snapshot_status_sist' => $order->statusSist,
            'frozen_at'            => now(),
        ]);
    }

    expect(fn () => app(ClosureDispatchService::class)->dispatch($note, [$order->id], $company, null, $actor))
        ->toThrow(ClosureDispatchException::class, $message);

    expect(Production::query()->count())->toBe(0)
        ->and(ClosureOrderState::query()->count())->toBe(0)
        ->and(ClosureOrderEvent::query()->count())->toBe(0);
})->with([
    'cancelada'     => [['canceled' => true], true, 'cancelada'],
    'SAP encerrado' => [['statusSist' => 'ENCE CONF'], true, 'encerrada no SAP'],
    'sem meta'      => [[], false, 'não pertence à meta'],
]);

it('refuses a destination that conflicts with an open closure production', function () {
    ['service' => $service, 'company' => $company, 'actor' => $actor, 'note' => $note, 'cycle' => $cycle] = closureDispatchFixture();
    $order                                                                                                = closureDispatchOrder($note, $cycle);
    $otherCompany                                                                                         = Company::query()->create([
        'name'  => 'Outra Parceira',
        'email' => fake()->unique()->safeEmail(),
    ]);

    Production::query()->create([
        'note_id'     => $note->id,
        'service_id'  => $service->uuid,
        'company_id'  => $otherCompany->id,
        'dispatch_by' => $actor->id,
        'dispatch_at' => now(),
        'completed'   => false,
        'confirmed'   => false,
        'status'      => 1,
    ]);

    expect(fn () => app(ClosureDispatchService::class)->dispatch($note, [$order->id], $company, null, $actor))
        ->toThrow(ClosureDispatchException::class, 'destino incompatível');

    expect(Production::query()->count())->toBe(1)
        ->and(ClosureOrderState::query()->count())->toBe(0)
        ->and(ClosureOrderEvent::query()->count())->toBe(0);
});

it('dispatches a future-goal order that has no closure target', function () {
    ['company' => $company, 'actor' => $actor, 'note' => $note] = closureDispatchFixture();
    $order                                                      = Order::query()->create([
        'note_id' => $note->id, 'ordem' => '700000001', 'statusSist' => 'LIB CNPA', 'canceled' => false,
    ]);
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => now()->startOfMonth()->addDay()]);

    $result = app(ClosureDispatchService::class)->dispatch($note, [$order->id], $company, null, $actor);

    expect($result->addedOrderIds->all())->toBe([(int) $order->id])
        ->and(ClosureTarget::query()->count())->toBe(0)
        ->and(ClosureOrderState::query()->count())->toBe(1);
});

it('dispatches orders from several notes, reporting partial success without reverting valid items', function () {
    ['company' => $company, 'actor' => $actor, 'note' => $noteA, 'cycle' => $cycle] = closureDispatchFixture();
    $noteB                                                                          = Note::query()->create(['note' => '9100002', 'type_note' => 1, 'dt_status' => now()]);
    $a1                                                                             = closureDispatchOrder($noteA, $cycle);
    $a2                                                                             = closureDispatchOrder($noteA, $cycle);
    $b1                                                                             = closureDispatchOrder($noteB, $cycle);
    $cancelled                                                                      = closureDispatchOrder($noteB, $cycle, ['canceled' => true]);
    $closed                                                                         = closureDispatchOrder($noteA, $cycle, ['statusSist' => 'ENCE CONF']);
    $noMeta                                                                         = Order::query()->create(['note_id' => $noteB->id, 'ordem' => '700000002', 'statusSist' => 'LIB CNPA', 'canceled' => false]);

    $result = app(ClosureDispatchService::class)->dispatchOrders(
        [$a1->id, $a2->id, $b1->id, $cancelled->id, $closed->id, $noMeta->id, 99999999],
        $company,
        null,
        $actor
    );

    expect($result->dispatchedOrderIds()->all())->toEqualCanonicalizing([(int) $a1->id, (int) $a2->id, (int) $b1->id])
        ->and($result->refusedOrderIds()->all())->toEqualCanonicalizing([(int) $cancelled->id, (int) $closed->id, (int) $noMeta->id, 99999999])
        ->and($result->isPartial())->toBeTrue()
        ->and($result->refusals()[(int) $cancelled->id])->toContain('cancelada')
        ->and($result->refusals()[(int) $closed->id])->toContain('encerrada no SAP')
        ->and($result->refusals()[(int) $noMeta->id])->toContain('não pertence à meta')
        ->and($result->notes)->toHaveCount(2)
        ->and(Production::query()->count())->toBe(2)
        ->and(ClosureOrderState::query()->count())->toBe(3);

    // Despachar de novo NÃO é possível: Ordem que já tem posse é recusada (mudar o responsável é pela Transferência).
    $again = app(ClosureDispatchService::class)->dispatchOrders([$a1->id, $b1->id], $company, null, $actor);

    expect($again->dispatchedOrderIds()->all())->toBe([])
        ->and($again->alreadyDispatchedOrderIds()->all())->toBe([])
        ->and($again->refusedOrderIds()->all())->toEqualCanonicalizing([(int) $a1->id, (int) $b1->id])
        ->and($again->refusals()[(int) $a1->id])->toContain('já foi despachada')->toContain('Transferência')
        ->and(Production::query()->count())->toBe(2)
        ->and(ClosureOrderState::query()->count())->toBe(3);
});

it('isolates a note with an incompatible open production while dispatching the other notes', function () {
    ['service' => $service, 'company' => $company, 'actor' => $actor, 'note' => $noteA, 'cycle' => $cycle] = closureDispatchFixture();
    $noteB                                                                                                 = Note::query()->create(['note' => '9100003', 'type_note' => 1, 'dt_status' => now()]);
    $a                                                                                                     = closureDispatchOrder($noteA, $cycle);
    $b                                                                                                     = closureDispatchOrder($noteB, $cycle);
    $other                                                                                                 = Company::query()->create(['name' => 'Outra', 'email' => fake()->unique()->safeEmail()]);

    Production::query()->create([
        'note_id'     => $noteB->id, 'service_id' => $service->uuid, 'company_id' => $other->id,
        'dispatch_by' => $actor->id, 'dispatch_at' => now(), 'completed' => false, 'confirmed' => false, 'status' => 1,
    ]);

    $result = app(ClosureDispatchService::class)->dispatchOrders([$a->id, $b->id], $company, null, $actor);

    expect($result->dispatchedOrderIds()->all())->toBe([(int) $a->id])
        ->and($result->refusals()[(int) $b->id])->toContain('destino incompatível')
        ->and(ClosureOrderState::query()->count())->toBe(1);
});

it('refuses a typed order that is not eligible (status outside LIB) when the id is forged', function () {
    ['company' => $company, 'actor' => $actor, 'note' => $note] = closureDispatchFixture();
    $order                                                      = Order::query()->create([
        'note_id' => $note->id, 'ordem' => '700000077', 'statusSist' => 'PREP CNPA', 'canceled' => false,
    ]);
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => now()->startOfMonth()->addDay()]);

    $result = app(ClosureDispatchService::class)->dispatchOrders([$order->id], $company, null, $actor);

    expect($result->refusals()[(int) $order->id])->toContain('não está elegível')
        ->and(ClosureOrderState::query()->count())->toBe(0);

    expect(fn () => app(ClosureDispatchService::class)->dispatch($note, [$order->id], $company, null, $actor))
        ->toThrow(ClosureDispatchException::class, 'não está elegível');
});
