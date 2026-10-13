<?php

use App\Models\Closure\{ClosureOrderEvent, ClosureOrderState, ClosureOrderStatePeriod};
use App\Models\{ClosureCycle, ClosureTarget, Company, Note, Order, Production, Service, User};
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

it('repeats an identical dispatch without duplicating production, state, event, period, or timeline', function () {
    ['company' => $company, 'actor' => $actor, 'note' => $note, 'cycle' => $cycle] = closureDispatchFixture();
    $order                                                                         = closureDispatchOrder($note, $cycle);
    $service                                                                       = app(ClosureDispatchService::class);

    $first  = $service->dispatch($note, [$order->id], $company, null, $actor);
    $second = $service->dispatch($note, [$order->id], $company, null, $actor);

    expect($first->productionWasCreated)->toBeTrue()
        ->and($second->isIdempotent())->toBeTrue()
        ->and($second->production->id)->toBe($first->production->id)
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
