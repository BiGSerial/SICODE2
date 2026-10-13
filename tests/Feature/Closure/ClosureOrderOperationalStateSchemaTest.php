<?php

use App\Models\Closure\{ClosureOrderEvent, ClosureOrderState, ClosureOrderStatePeriod};
use App\Models\{Company, Note, Order, Production, Service, User};
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function closureOperationalStateFixture(): array
{
    $company = Company::query()->create([
        'name'  => 'Parceira Schema ' . fake()->unique()->numerify('###'),
        'email' => fake()->unique()->safeEmail(),
    ]);
    $user       = User::factory()->create(['company_id' => $company->id]);
    $note       = Note::query()->create(['note' => 'CLOSURE-' . fake()->unique()->numerify('######'), 'type_note' => 1]);
    $order      = Order::query()->create(['note_id' => $note->id, 'ordem' => fake()->unique()->numerify('200#########')]);
    $service    = Service::query()->where('folder', 'encerramento')->sole();
    $production = Production::query()->create([
        'note_id'    => $note->id,
        'service_id' => $service->uuid,
        'company_id' => $company->id,
    ]);

    return compact('company', 'user', 'order', 'production');
}

function closureOperationalState(array $fixture): ClosureOrderState
{
    return ClosureOrderState::query()->create([
        'order_id'                  => $fixture['order']->id,
        'production_id'             => $fixture['production']->id,
        'operational_status'        => 'ASSIGNED',
        'assigned_user_id'          => $fixture['user']->id,
        'current_holder_type'       => 'INTERNAL_USER',
        'current_holder_user_id'    => $fixture['user']->id,
        'current_holder_company_id' => $fixture['company']->id,
        'entered_current_status_at' => now(),
    ]);
}

it('persists operational state, event and open period through their relationships', function () {
    $fixture = closureOperationalStateFixture();
    $state   = closureOperationalState($fixture);
    $event   = ClosureOrderEvent::query()->create([
        'closure_order_state_id' => $state->id,
        'order_id'               => $fixture['order']->id,
        'actor_id'               => $fixture['user']->id,
        'type'                   => 'ASSIGNED',
        'origin'                 => 'UI',
        'after_data'             => ['operational_status' => 'ASSIGNED'],
    ]);
    $state->update(['last_event_id' => $event->id]);

    $period = ClosureOrderStatePeriod::query()->create([
        'closure_order_state_id' => $state->id,
        'operational_status'     => 'ASSIGNED',
        'holder_type'            => 'INTERNAL_USER',
        'holder_user_id'         => $fixture['user']->id,
        'holder_company_id'      => $fixture['company']->id,
        'started_at'             => now(),
        'started_event_id'       => $event->id,
    ]);

    expect($fixture['order']->fresh()->ClosureState->is($state))->toBeTrue()
        ->and($fixture['production']->fresh()->ClosureOrderStates->sole()->is($state))->toBeTrue()
        ->and($state->fresh()->LastEvent->is($event))->toBeTrue()
        ->and($state->Events->sole()->is($event))->toBeTrue()
        ->and($state->Periods->sole()->is($period))->toBeTrue()
        ->and($period->StartedEvent->is($event))->toBeTrue();
});

it('enforces one operational state and one open period per order state', function () {
    $fixture = closureOperationalStateFixture();
    $state   = closureOperationalState($fixture);
    $event   = ClosureOrderEvent::query()->create([
        'closure_order_state_id' => $state->id,
        'order_id'               => $fixture['order']->id,
        'type'                   => 'ENTERED_TARGET',
        'origin'                 => 'SYSTEM',
    ]);

    ClosureOrderStatePeriod::query()->create([
        'closure_order_state_id' => $state->id,
        'operational_status'     => 'ASSIGNED',
        'started_at'             => now(),
        'started_event_id'       => $event->id,
    ]);

    expect(fn () => ClosureOrderState::query()->create([
        'order_id'      => $fixture['order']->id,
        'production_id' => $fixture['production']->id,
    ]))->toThrow(QueryException::class);

    expect(fn () => ClosureOrderStatePeriod::query()->create([
        'closure_order_state_id' => $state->id,
        'operational_status'     => 'IN_PROGRESS',
        'started_at'             => now(),
        'started_event_id'       => $event->id,
    ]))->toThrow(QueryException::class);
});

it('has the indexes that support operational queues and period lookup', function () {
    $stateIndexes  = collect(DB::select('SHOW INDEX FROM closure_order_states'))->pluck('Key_name');
    $periodIndexes = collect(DB::select('SHOW INDEX FROM closure_order_state_periods'))->pluck('Key_name');

    expect($stateIndexes)->toContain(
        'closure_order_states_order_id_unique',
        'closure_state_production_status_index',
        'closure_state_status_entered_index',
        'closure_state_holder_user_status_index',
        'closure_state_holder_company_status_index',
    )->and($periodIndexes)->toContain(
        'closure_state_period_state_started_index',
        'closure_state_period_one_open_per_state',
    );
});
