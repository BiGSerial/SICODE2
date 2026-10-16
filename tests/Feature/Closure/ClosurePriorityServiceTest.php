<?php

use App\Models\Closure\{ClosureOrderEvent, ClosurePriorityRequest};
use App\Services\Closure\{ClosureOrderWorkflowService, ClosurePriorityException, ClosurePriorityService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

require_once __DIR__ . '/ClosureScreenFixture.php';

uses(RefreshDatabase::class);

it('prevents a duplicate pending priority request per note', function () {
    Notification::fake();
    $f       = closureCollabFixture();
    $service = app(ClosurePriorityService::class);

    $request = $service->request($f['note'], 'Cliente cobrando.', $f['internal']);

    expect($request->status)->toBe(ClosurePriorityRequest::STATUS_PENDING)
        ->and(fn () => $service->request($f['note'], 'De novo.', $f['dispatcher']))->toThrow(ClosurePriorityException::class, 'pendente')
        ->and(fn () => $service->request($f['note'], '  ', $f['internal']))->toThrow(ClosurePriorityException::class, 'motivo');

    $service->decline($request, $f['dispatcher'], 'Sem capacidade.');
    expect($service->request($f['note'], 'Nova tentativa.', $f['internal'])->status)->toBe(ClosurePriorityRequest::STATUS_PENDING);
});

it('accepts a request by prioritizing every open order of the note and recording events', function () {
    Notification::fake();
    $f       = closureCollabFixture();
    $second  = closureCollabExtraOrder($f, 'IN_PROGRESS');
    $closed  = closureCollabExtraOrder($f, 'CLOSED', 'ENTE');
    $service = app(ClosurePriorityService::class);
    $request = $service->request($f['note'], 'Prazo regulatório.', $f['internal']);

    $accepted = $service->accept($request, $f['dispatcher'], 'Atendido.');

    expect($accepted->status)->toBe(ClosurePriorityRequest::STATUS_ACCEPTED)
        ->and($accepted->decided_by)->toBe($f['dispatcher']->id)
        ->and($accepted->decided_at)->not->toBeNull()
        ->and($accepted->decision_note)->toBe('Atendido.')
        ->and($f['state']->fresh()->priority)->toBeTrue()
        ->and($second->fresh()->priority)->toBeTrue()
        ->and($closed->fresh()->priority)->toBeFalse()
        ->and(ClosureOrderEvent::query()->where('type', ClosureOrderWorkflowService::EVENT_PRIORITY_CHANGED)->count())->toBe(2)
        ->and(fn () => $service->accept($accepted, $f['dispatcher']))->toThrow(ClosurePriorityException::class, 'já foi decidido');

    expect($service->clear($f['note'], $f['dispatcher']))->toBe(2)
        ->and($f['state']->fresh()->priority)->toBeFalse()
        ->and($second->fresh()->priority)->toBeFalse();
});

it('declines without changing priority', function () {
    Notification::fake();
    $f       = closureCollabFixture();
    $service = app(ClosurePriorityService::class);

    $declined = $service->decline($service->request($f['note'], 'Urgente.', $f['internal']), $f['dispatcher'], 'Fora da meta.');

    expect($declined->status)->toBe(ClosurePriorityRequest::STATUS_DECLINED)
        ->and($declined->decision_note)->toBe('Fora da meta.')
        ->and($f['state']->fresh()->priority)->toBeFalse();
});

it('allows only dispatchers to decide and only internal users to ask', function () {
    Notification::fake();
    $f       = closureCollabFixture();
    $service = app(ClosurePriorityService::class);
    $request = $service->request($f['note'], 'Urgente.', $f['internal']);

    expect(fn () => $service->accept($request, $f['internal']))->toThrow(ClosurePriorityException::class, 'despachante')
        ->and(fn () => $service->decline($request, $f['partnerUser']))->toThrow(ClosurePriorityException::class, 'despachante')
        ->and(fn () => $service->clear($f['note'], $f['internal']))->toThrow(ClosurePriorityException::class, 'despachante')
        ->and(fn () => $service->request($f['note'], 'Eu quero.', $f['partnerUser']))->toThrow(ClosurePriorityException::class, 'não pode')
        ->and(fn () => $service->request($f['note'], 'Eu quero.', $f['outsider']))->toThrow(ClosurePriorityException::class, 'não pode')
        ->and($f['state']->fresh()->priority)->toBeFalse();
});
