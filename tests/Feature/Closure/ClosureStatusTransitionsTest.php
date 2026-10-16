<?php

use App\Enum\ClosureOperationalStatus;
use App\Models\Closure\ClosureOrderState;
use App\Models\{Company, Note, Operation, Order, Production, Service, User};
use App\Services\Closure\{ClosureOrderWorkflowException, ClosureOrderWorkflowService, ClosureSettings};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

function transitionFixture(string $fimReal, string $status = 'IN_PROGRESS'): array
{
    $service = Service::query()->firstOrCreate(
        ['folder' => 'encerramento'],
        ['service' => 'Encerramento', 'project' => true, 'construction' => true, 'icon' => 'ri-checkbox-circle-line']
    );
    $company = Company::query()->create(['name' => 'Parceira ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()]);
    $plain   = ['company_id' => $company->id, 'admin' => false, 'superadm' => false, 'closure_manager' => false];
    $owner   = User::factory()->create($plain);
    $other   = User::factory()->create($plain);
    $manager = User::factory()->create(['closure_manager' => true] + $plain);
    $note    = Note::query()->create(['note' => (string) fake()->unique()->numberBetween(1000000, 9999999), 'type_note' => 1]);
    $order   = Order::query()->create(['note_id' => $note->id, 'ordem' => (string) fake()->unique()->numberBetween(100000000, 999999999), 'statusSist' => 'LIB CNPA']);
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => $fimReal]);
    $production = Production::query()->create(['note_id' => $note->id, 'service_id' => $service->uuid, 'company_id' => $company->id]);
    $state      = ClosureOrderState::query()->create([
        'order_id'                  => $order->id,
        'production_id'             => $production->id,
        'operational_status'        => $status,
        'assigned_user_id'          => $owner->id,
        'current_holder_type'       => ClosureOrderWorkflowService::HOLDER_INTERNAL_USER,
        'current_holder_user_id'    => $owner->id,
        'current_holder_company_id' => $company->id,
        'entered_current_status_at' => now(),
    ]);

    return compact('owner', 'other', 'manager', 'order', 'state');
}

beforeEach(fn () => Carbon::setTestNow('2026-10-16 10:00:00'));
afterEach(fn () => Carbon::setTestNow());

it('changes status for the owner with immutable event and rotated period', function () {
    ['owner' => $owner, 'state' => $state] = transitionFixture('2026-09-10');
    $service                               = app(ClosureOrderWorkflowService::class);

    $paused = $service->changeStatus($state, 'PAUSED', $owner, 'Aguardando material');

    expect($paused->operational_status)->toBe('PAUSED')
        ->and($paused->LastEvent->type)->toBe('STATUS_CHANGED')
        ->and($paused->LastEvent->meta['reason'])->toBe('Aguardando material')
        ->and($paused->Periods()->whereNull('ended_at')->count())->toBe(1)
        ->and($paused->Periods()->where('operational_status', 'PAUSED')->count())->toBe(1);

    $partner = $service->changeStatus($paused, 'WAITING_PARTNER', $owner, 'Documento pendente');
    expect($partner->current_holder_type)->toBe(ClosureOrderWorkflowService::HOLDER_SYSTEM);

    $back = $service->changeStatus($partner, 'IN_PROGRESS', $owner);
    expect($back->operational_status)->toBe('IN_PROGRESS')
        ->and($back->current_holder_user_id)->toBe($owner->id);
});

it('enforces the transition matrix, mandatory reasons and ownership', function () {
    ['owner' => $owner, 'other' => $other, 'manager' => $manager, 'state' => $state] = transitionFixture('2026-09-10');
    $service                                                                         = app(ClosureOrderWorkflowService::class);

    expect(fn () => $service->changeStatus($state, 'PAUSED', $owner))->toThrow(ClosureOrderWorkflowException::class, 'motivo')
        ->and(fn () => $service->changeStatus($state, 'DEPOSIT_BLOCKED', $owner, ' '))->toThrow(ClosureOrderWorkflowException::class, 'motivo')
        ->and(fn () => $service->changeStatus($state, 'AVAILABLE', $owner))->toThrow(ClosureOrderWorkflowException::class, 'não é permitida')
        ->and(fn () => $service->changeStatus($state, 'CLOSED', $owner))->toThrow(ClosureOrderWorkflowException::class, 'não é permitida')
        ->and(fn () => $service->changeStatus($state, 'PAUSED', $other, 'x'))->toThrow(ClosureOrderWorkflowException::class, 'responsável');

    $released = $service->changeStatus($state, 'DEPOSIT_RELEASED', $owner);
    expect($released->operational_status)->toBe('DEPOSIT_RELEASED');

    $byManager = $service->changeStatus($released, 'PAUSED', $manager, 'Gestor pausou');
    expect($byManager->operational_status)->toBe('PAUSED');
});

it('finishes non-future orders straight to FINISHED', function () {
    ['owner' => $owner, 'state' => $state] = transitionFixture('2026-09-10');

    $finished = app(ClosureOrderWorkflowService::class)->finish($state, $owner);

    expect($finished->operational_status)->toBe('FINISHED')
        ->and($finished->LastEvent->type)->toBe('FINISHED')
        ->and($finished->LastEvent->meta['meta_type'])->toBe('M');
});

it('blocks finishing a future-goal order when the setting is off', function () {
    ['owner' => $owner, 'state' => $state] = transitionFixture('2026-10-05');

    expect(fn () => app(ClosureOrderWorkflowService::class)->finish($state, $owner))
        ->toThrow(ClosureOrderWorkflowException::class, 'meta futura');

    expect($state->fresh()->operational_status)->toBe('IN_PROGRESS');
});

it('sends a future-goal order to WAITING when allowed and promotes it to FINISHED once it stops being F', function () {
    ['owner' => $owner, 'state' => $state] = transitionFixture('2026-10-05');
    app(ClosureSettings::class)->setAllowFutureCompletion(true, $owner);
    $service = app(ClosureOrderWorkflowService::class);

    $waiting = $service->finish($state, $owner);

    expect($waiting->operational_status)->toBe('WAITING')
        ->and($waiting->LastEvent->type)->toBe('WAITING_FUTURE');

    expect($service->promoteWaiting())->toBe(0)
        ->and($state->fresh()->operational_status)->toBe('WAITING');

    Carbon::setTestNow('2026-11-01 06:00:00');

    expect(Artisan::call('closure:promote-waiting'))->toBe(0);

    $promoted = $state->fresh();
    expect($promoted->operational_status)->toBe('FINISHED')
        ->and($promoted->LastEvent->type)->toBe('WAITING_PROMOTED')
        ->and($promoted->LastEvent->actor_id)->toBeNull()
        ->and($promoted->LastEvent->origin)->toBe('SCHEDULER')
        ->and(Artisan::call('closure:promote-waiting'))->toBe(0)
        ->and($state->fresh()->Events()->where('type', 'WAITING_PROMOTED')->count())->toBe(1);
});

it('exposes localized labels, badges and terminal flags for the status enum', function () {
    expect(ClosureOperationalStatus::WAITING_PARTNER->label())->toBe('Aguardando Parceira')
        ->and(ClosureOperationalStatus::PAUSED->label())->toBe('Pausa')
        ->and(ClosureOperationalStatus::CLOSED->isTerminal())->toBeTrue()
        ->and(ClosureOperationalStatus::FINISHED->isTerminal())->toBeFalse()
        ->and(ClosureOperationalStatus::FINISHED->isActive())->toBeFalse()
        ->and(ClosureOperationalStatus::IN_PROGRESS->isActive())->toBeTrue()
        ->and(ClosureOperationalStatus::DEPOSIT_BLOCKED->badgeClass())->toBeString()
        ->and(ClosureOperationalStatus::labelFor('WAITING'))->toBe('Aguardando');
});

it('does not promote a WAITING order whose type cannot be classified', function () {
    ['owner' => $owner, 'state' => $state, 'order' => $order] = transitionFixture('2026-10-05');
    app(ClosureSettings::class)->setAllowFutureCompletion(true, $owner);
    app(ClosureOrderWorkflowService::class)->finish($state, $owner);

    Operation::query()->where('order_id', $order->id)->update(['fimReal' => null]);

    expect(app(ClosureOrderWorkflowService::class)->promoteWaiting())->toBe(0)
        ->and($state->fresh()->operational_status)->toBe('WAITING');
});
