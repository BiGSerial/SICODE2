<?php

use App\Models\{Company, Operation, User};
use App\Services\Closure\{ClosureOrderWorkflowException, ClosureOrderWorkflowService};
use Illuminate\Foundation\Testing\RefreshDatabase;

require_once __DIR__ . '/ClosureScreenFixture.php';

uses(RefreshDatabase::class);

beforeEach(fn () => \Illuminate\Support\Facades\Notification::fake());

it('claims an available order while preserving the event and period audit trail', function () {
    ['actor' => $actor, 'order' => $order, 'state' => $state] = closureWorkflowFixture();
    $operation                                                = Operation::query()->create([
        'order_id' => $order->id,
        'operacao' => '0010',
        'status'   => 'LIBERADA',
    ]);

    $result = app(ClosureOrderWorkflowService::class)->claim($state, $actor, 'f09c407e-0596-4cc9-af04-a1d5c72c6d04');

    expect($result->operational_status)->toBe(ClosureOrderWorkflowService::STATUS_ASSIGNED)
        ->and($result->assigned_user_id)->toBe($actor->id)
        ->and($result->current_holder_user_id)->toBe($actor->id)
        ->and($result->LastEvent->type)->toBe(ClosureOrderWorkflowService::EVENT_CLAIMED)
        ->and($result->LastEvent->batch_id)->toBe('f09c407e-0596-4cc9-af04-a1d5c72c6d04')
        ->and($result->LastEvent->before_data['operational_status'])->toBe(ClosureOrderWorkflowService::STATUS_AVAILABLE)
        ->and($result->LastEvent->after_data['operational_status'])->toBe(ClosureOrderWorkflowService::STATUS_ASSIGNED)
        ->and($result->Periods()->whereNull('ended_at')->count())->toBe(1)
        ->and($result->Periods()->count())->toBe(2)
        ->and($result->Periods()->whereNotNull('ended_at')->first()->ended_event_id)->toBe($result->last_event_id)
        ->and($order->fresh()->statusSist)->toBe('LIB CNPA')
        ->and($operation->fresh()->status)->toBe('LIBERADA');
});

it('requires a reason for transfers and returns, and records the destination change', function () {
    ['actor' => $actor, 'state' => $state] = closureWorkflowFixture();
    $destinationCompany                    = Company::query()->create([
        'name'  => 'Destino Workflow ' . fake()->unique()->numerify('###'),
        'email' => fake()->unique()->safeEmail(),
    ]);
    $destinationUser = User::factory()->create(['company_id' => $destinationCompany->id]);
    $workflow        = app(ClosureOrderWorkflowService::class);

    expect(fn () => $workflow->transfer($state, $destinationCompany, $destinationUser, $actor, '  '))
        ->toThrow(ClosureOrderWorkflowException::class, 'motivo');

    $transferred = $workflow->transfer($state, $destinationCompany, $destinationUser, $actor, 'Redistribuição por capacidade');

    expect($transferred->operational_status)->toBe(ClosureOrderWorkflowService::STATUS_ASSIGNED)
        ->and($transferred->assigned_user_id)->toBe($destinationUser->id)
        ->and($transferred->current_holder_company_id)->toBe($destinationCompany->id)
        ->and($transferred->LastEvent->type)->toBe(ClosureOrderWorkflowService::EVENT_TRANSFERRED)
        ->and($transferred->LastEvent->meta['reason'])->toBe('Redistribuição por capacidade');

    expect(fn () => $workflow->assign($transferred, $destinationCompany, null, $actor))
        ->toThrow(ClosureOrderWorkflowException::class, 'motivo');

    $returned = $workflow->returnToPool($transferred, $actor, 'Devolvido à pilha');

    expect($returned->operational_status)->toBe(ClosureOrderWorkflowService::STATUS_AVAILABLE)
        ->and($returned->assigned_user_id)->toBeNull()
        ->and($returned->current_holder_user_id)->toBeNull()
        ->and($returned->LastEvent->type)->toBe(ClosureOrderWorkflowService::EVENT_RETURNED_TO_POOL);
});

it('starts treatment, waits for return, and records priority without rotating the period', function () {
    ['actor' => $actor, 'state' => $state] = closureWorkflowFixture();
    $workflow                              = app(ClosureOrderWorkflowService::class);

    $assigned = $workflow->claim($state, $actor);
    $started  = $workflow->start($assigned, $actor);
    $waiting  = $workflow->waitForReturn($started, $actor, 'Aguardando confirmação do SAP');
    $periods  = $waiting->Periods()->count();
    $priority = $workflow->setPriority($waiting, true, $actor);

    expect($waiting->operational_status)->toBe(ClosureOrderWorkflowService::STATUS_WAITING_PARTNER)
        ->and($waiting->current_holder_type)->toBe(ClosureOrderWorkflowService::HOLDER_SYSTEM)
        ->and($priority->priority)->toBeTrue()
        ->and($priority->LastEvent->type)->toBe(ClosureOrderWorkflowService::EVENT_PRIORITY_CHANGED)
        ->and($priority->Periods()->count())->toBe($periods)
        ->and($priority->Periods()->whereNull('ended_at')->count())->toBe(1);
});

it('refuses state changes for SAP-closed or cancelled orders and invalid transitions', function () {
    ['actor' => $actor, 'state' => $state, 'order' => $order] = closureWorkflowFixture();
    $workflow                                                 = app(ClosureOrderWorkflowService::class);

    expect(fn () => $workflow->start($state, $actor))
        ->toThrow(ClosureOrderWorkflowException::class, 'transição');

    $order->update(['statusSist' => 'ENCE CONF']);

    expect(fn () => $workflow->claim($state, $actor))
        ->toThrow(ClosureOrderWorkflowException::class, 'encerrada no SAP');

    $order->update(['statusSist' => 'LIB CNPA', 'canceled' => true]);

    expect(fn () => $workflow->claim($state, $actor))
        ->toThrow(ClosureOrderWorkflowException::class, 'cancelada');
});
