<?php

use App\Models\Closure\{ClosureOrderEvent, ClosureOrderState};
use App\Models\{Company, Note, Operation, Order, Production, Service};
use App\Services\Closure\ClosureMetaClassifier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

function syncClosedState(string $fimReal, string $statusSist = 'LIB CNPA', array $orderAttributes = [], string $status = 'IN_PROGRESS'): ClosureOrderState
{
    $service = Service::query()->firstOrCreate(
        ['folder' => 'encerramento'],
        ['service' => 'Encerramento', 'project' => true, 'construction' => true, 'icon' => 'ri-checkbox-circle-line']
    );
    $company = Company::query()->create(['name' => 'Parceira ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()]);
    $note    = Note::query()->create(['note' => (string) fake()->unique()->numberBetween(1000000, 9999999), 'type_note' => 1]);
    $order   = Order::query()->create(array_merge([
        'note_id'    => $note->id,
        'ordem'      => (string) fake()->unique()->numberBetween(100000000, 999999999),
        'statusSist' => $statusSist,
    ], $orderAttributes));
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => $fimReal]);
    $production = Production::query()->create(['note_id' => $note->id, 'service_id' => $service->uuid, 'company_id' => $company->id]);

    return ClosureOrderState::query()->create([
        'order_id'                  => $order->id,
        'production_id'             => $production->id,
        'operational_status'        => $status,
        'current_holder_type'       => 'PARTNER_COMPANY',
        'current_holder_company_id' => $company->id,
        'entered_current_status_at' => now(),
    ]);
}

beforeEach(fn () => Carbon::setTestNow('2026-10-16 10:00:00'));
afterEach(fn () => Carbon::setTestNow());

it('closes SAP-closed orders, freezes the meta type and never changes it afterwards', function () {
    $meta    = syncClosedState('2026-09-10', 'ENTE CONF');
    $passive = syncClosedState('2026-05-10', 'ENCE CONF');
    $future  = syncClosedState('2026-10-05', 'ENTE CONF');
    $open    = syncClosedState('2026-09-10');

    expect(Artisan::call('closure:sync-closed'))->toBe(0);

    expect($meta->fresh()->operational_status)->toBe('CLOSED')
        ->and($meta->fresh()->closed_meta_type)->toBe('M')
        ->and($meta->fresh()->closed_meta_period)->toBe('2026-10')
        ->and($meta->fresh()->closed_at)->not->toBeNull()
        ->and($passive->fresh()->closed_meta_type)->toBe('P')
        ->and($future->fresh()->closed_meta_type)->toBe('F')
        ->and($open->fresh()->operational_status)->toBe('IN_PROGRESS')
        ->and($open->fresh()->closed_meta_type)->toBeNull()
        ->and($meta->fresh()->LastEvent->type)->toBe('SAP_CLOSED')
        ->and($meta->fresh()->LastEvent->origin)->toBe('SAP_SYNC')
        ->and($meta->fresh()->Periods()->whereNull('ended_at')->count())->toBe(0);

    $events = ClosureOrderEvent::query()->count();

    // Reexecução e virada de mês: idempotente, tipo congelado preservado.
    Carbon::setTestNow('2027-01-05 06:00:00');
    Artisan::call('closure:sync-closed');

    expect(ClosureOrderEvent::query()->count())->toBe($events)
        ->and($meta->fresh()->closed_meta_type)->toBe('M')
        ->and($meta->fresh()->closed_meta_period)->toBe('2026-10')
        ->and(app(ClosureMetaClassifier::class)->typeFor($meta->order))->toBe('M');
});

it('moves cancelled orders to CANCELLED once', function () {
    $state = syncClosedState('2026-09-10', 'LIB CNPA', ['canceled' => true]);

    Artisan::call('closure:sync-closed');
    $events = ClosureOrderEvent::query()->count();
    Artisan::call('closure:sync-closed');

    expect($state->fresh()->operational_status)->toBe('CANCELLED')
        ->and($state->fresh()->cancelled_at)->not->toBeNull()
        ->and($state->fresh()->LastEvent->type)->toBe('SAP_CANCELLED')
        ->and(ClosureOrderEvent::query()->count())->toBe($events);
});

it('closes states in any open status, including finished and waiting', function (string $status) {
    $state = syncClosedState('2026-09-10', 'ENTE CONF', [], $status);

    Artisan::call('closure:sync-closed');

    expect($state->fresh()->operational_status)->toBe('CLOSED')
        ->and($state->fresh()->closed_meta_type)->toBe('M');
})->with(['FINISHED', 'WAITING', 'PAUSED', 'AVAILABLE']);

it('freezes the meta type by the month the order was last seen open, not by the detection date', function () {
    $state = syncClosedState('2026-08-20', 'ENTE CONF');
    // Vista aberta pela última vez em setembro; SAP encerrou em setembro; detectado só em outubro.
    $state->update(['last_status_sist_checked_at' => '2026-09-28 22:00:00']);

    Artisan::call('closure:sync-closed');

    expect($state->fresh()->closed_meta_type)->toBe('M')
        ->and($state->fresh()->closed_meta_period)->toBe('2026-09');
});

it('records the closed period even when the type cannot be classified', function () {
    $state = syncClosedState('2026-12-20', 'ENTE CONF');

    Artisan::call('closure:sync-closed');

    expect($state->fresh()->closed_meta_type)->toBeNull()
        ->and($state->fresh()->closed_meta_period)->toBe('2026-10');
});
