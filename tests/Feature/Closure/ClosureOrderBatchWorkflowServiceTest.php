<?php

use App\Models\Closure\{ClosureOrderEvent, ClosureOrderState};
use App\Models\{Company, Note, Order, Production, Service, User};
use App\Services\Closure\{ClosureOrderBatchException, ClosureOrderBatchItemResult, ClosureOrderBatchWorkflowService, ClosureOrderWorkflowService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function closureBatchState(string $status = ClosureOrderWorkflowService::STATUS_AVAILABLE, ?string $sapStatus = null): array
{
    $service = Service::query()->firstOrCreate(
        ['folder' => 'encerramento'],
        ['service' => 'Encerramento', 'project' => true, 'construction' => true, 'icon' => 'ri-checkbox-circle-line'],
    );
    $company = Company::query()->create([
        'name'  => 'Parceira Lote ' . fake()->unique()->numerify('###'),
        'email' => fake()->unique()->safeEmail(),
    ]);
    $actor = User::factory()->create(['company_id' => $company->id]);
    $note  = Note::query()->create(['note' => 'BATCH-' . fake()->unique()->numerify('######'), 'type_note' => 1]);
    $order = Order::query()->create([
        'note_id'    => $note->id,
        'ordem'      => fake()->unique()->numerify('200#########'),
        'statusSist' => $sapStatus ?? 'LIB CNPA',
    ]);
    $production = Production::query()->create([
        'note_id'    => $note->id,
        'service_id' => $service->uuid,
        'company_id' => $company->id,
    ]);
    $state = ClosureOrderState::query()->create([
        'order_id'                  => $order->id,
        'production_id'             => $production->id,
        'operational_status'        => $status,
        'current_holder_type'       => ClosureOrderWorkflowService::HOLDER_PARTNER_COMPANY,
        'current_holder_company_id' => $company->id,
        'entered_current_status_at' => now()->subMinute(),
    ]);

    return compact('company', 'actor', 'order', 'production', 'state');
}

it('rejects batches larger than one hundred items', function () {
    $service = app(ClosureOrderBatchWorkflowService::class);

    expect(fn () => $service->preview(range(1, 101), 'claim', [], User::factory()->make()))
        ->toThrow(ClosureOrderBatchException::class, 'no máximo 100');
});

it('previews eligibility without writing states or events', function () {
    ['actor' => $actor, 'state' => $eligible] = closureBatchState();
    ['state' => $closed]                      = closureBatchState(ClosureOrderWorkflowService::STATUS_CLOSED);
    $service                                  = app(ClosureOrderBatchWorkflowService::class);

    $preview = $service->preview([$eligible->id, $closed->id, 999999], 'claim', [], $actor);

    expect($preview->eligible())->toHaveCount(1)
        ->and($preview->refused())->toHaveCount(2)
        ->and($preview->refused()->pluck('status')->unique()->all())->toBe([ClosureOrderBatchItemResult::STATUS_REFUSED])
        ->and(ClosureOrderEvent::query()->count())->toBe(0)
        ->and($eligible->fresh()->operational_status)->toBe(ClosureOrderWorkflowService::STATUS_AVAILABLE);

    expect(fn () => $service->preview([$eligible->id], 'invalid', [], $actor))
        ->toThrow(ClosureOrderBatchException::class, 'inválida');
    expect($service->preview([$eligible->id], 'assign', [], $actor)->refused()->first()->reason)
        ->toBe('Informe a empresa de destino para atribuir.')
        ->and(ClosureOrderEvent::query()->count())->toBe(0);
});

it('keeps successful items when another item is refused and returns a receipt with batch id', function () {
    ['actor' => $actor, 'state' => $successful] = closureBatchState();
    ['state' => $refused]                       = closureBatchState(ClosureOrderWorkflowService::STATUS_AVAILABLE, 'ENCE CONF');
    $service                                    = app(ClosureOrderBatchWorkflowService::class);

    $receipt = $service->execute([$successful->id, $refused->id], 'claim', [], $actor);

    expect(Str::isUuid($receipt->batchId))->toBeTrue()
        ->and($receipt->action)->toBe(ClosureOrderBatchWorkflowService::ACTION_CLAIM)
        ->and($receipt->successful())->toHaveCount(1)
        ->and($receipt->refused())->toHaveCount(1)
        ->and($receipt->successful()->first()->stateId)->toBe($successful->id)
        ->and($receipt->refused()->first()->stateId)->toBe($refused->id)
        ->and($receipt->refused()->first()->reason)->toContain('encerrada no SAP')
        ->and($successful->fresh()->operational_status)->toBe(ClosureOrderWorkflowService::STATUS_ASSIGNED)
        ->and($refused->fresh()->operational_status)->toBe(ClosureOrderWorkflowService::STATUS_AVAILABLE)
        ->and(ClosureOrderEvent::query()->where('closure_order_state_id', $successful->id)->value('batch_id'))->toBe($receipt->batchId)
        ->and(ClosureOrderEvent::query()->where('closure_order_state_id', $refused->id)->count())->toBe(0);
});
