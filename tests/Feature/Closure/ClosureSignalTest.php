<?php

use App\Models\Closure\ClosureOrderState;
use App\Models\{Note, Order};
use App\Services\Closure\ClosureSignal;
use Carbon\Carbon;

// Sinalização por cor das Ordens (regra única): azul atribuída · verde concluída · vermelho +24h sem baixa no SAP · amarelo na pilha sem atribuição.

const SIG_NOW = '2026-10-25 12:00:00';

beforeEach(fn () => Carbon::setTestNow(SIG_NOW));
afterEach(fn () => Carbon::setTestNow());

function sigKey(?string $status, $assigned = null, $holderCompany = null, $holderUser = null, ?string $enteredAt = null, ?string $statusSist = 'LIB CNPA'): ?string
{
    return ClosureSignal::resolve($status, $assigned, $holderCompany, $holderUser, $enteredAt, $statusSist)['key'] ?? null;
}

it('is blue when the Order is assigned and being worked', function () {
    foreach (['ASSIGNED', 'IN_PROGRESS', 'PAUSED', 'WAITING_PARTNER', 'DEPOSIT_BLOCKED', 'DEPOSIT_RELEASED'] as $status) {
        expect(sigKey($status, 'user-1', 'company-1'))->toBe(ClosureSignal::ASSIGNED, $status);
    }

    // em andamento mas sem responsável atribuído: sem sinal
    expect(sigKey('IN_PROGRESS', null, 'company-1'))->toBeNull();
});

it('is green when the Order is concluded (finished, waiting for the goal, or closed in SAP)', function () {
    expect(sigKey('FINISHED', 'user-1', null, null, '2026-10-25 10:00:00'))->toBe(ClosureSignal::DONE)
        ->and(sigKey('WAITING', 'user-1'))->toBe(ClosureSignal::DONE)
        ->and(sigKey('CLOSED', 'user-1', null, null, '2026-10-01 10:00:00', 'ENTE'))->toBe(ClosureSignal::DONE);
});

it('is red when finished for more than 24h and the SAP still did not close the Order', function () {
    // 25h depois, SAP ainda aberto (LIB): vermelho
    expect(sigKey('FINISHED', 'user-1', null, null, '2026-10-24 11:00:00', 'LIB CNPA'))->toBe(ClosureSignal::OVERDUE)
        // exatamente 24h: já passou do limite
        ->and(sigKey('FINISHED', 'user-1', null, null, '2026-10-24 12:00:00', 'LIB CNPA'))->toBe(ClosureSignal::OVERDUE)
        // 23h: ainda dentro do prazo, fica verde
        ->and(sigKey('FINISHED', 'user-1', null, null, '2026-10-24 13:00:00', 'LIB CNPA'))->toBe(ClosureSignal::DONE)
        // SAP já encerrou (ENTE/ENCE): verde, mesmo depois de 24h
        ->and(sigKey('FINISHED', 'user-1', null, null, '2026-10-20 10:00:00', 'ENTE'))->toBe(ClosureSignal::DONE)
        ->and(sigKey('FINISHED', 'user-1', null, null, '2026-10-20 10:00:00', 'ENCE'))->toBe(ClosureSignal::DONE)
        // aguardando a meta é espera por desenho: nunca vermelho
        ->and(sigKey('WAITING', 'user-1', null, null, '2026-09-01 10:00:00', 'LIB CNPA'))->toBe(ClosureSignal::DONE);

    $signal = ClosureSignal::resolve('FINISHED', 'user-1', null, null, '2026-10-23 06:00:00', 'LIB', Carbon::parse(SIG_NOW));

    expect($signal['label'])->toBe('Sem baixa no SAP')->and($signal['hint'])->toContain('Finalizada há 54h');
});

it('is yellow when it sits in someone\'s pile and was not assigned', function () {
    expect(sigKey('AVAILABLE', null, 'company-1'))->toBe(ClosureSignal::PILE)
        ->and(sigKey('AVAILABLE', null, null, 'user-9'))->toBe(ClosureSignal::PILE)
        // disponível sem dono nenhum, ou já atribuída (inconsistente): sem sinal amarelo
        ->and(sigKey('AVAILABLE'))->toBeNull()
        ->and(sigKey('AVAILABLE', 'user-1', 'company-1'))->toBeNull();
});

it('has no signal for cancelled or empty status and red wins over green', function () {
    expect(sigKey('CANCELLED', 'user-1'))->toBeNull()
        ->and(sigKey(null))->toBeNull()
        ->and(sigKey(''))->toBeNull()
        ->and(sigKey('FINISHED', 'user-1', null, null, '2026-10-01 00:00:00', 'LIB'))->toBe(ClosureSignal::OVERDUE);
});

it('reads a list row and a state model with the same rule', function () {
    $row = (object) ['operational_status' => 'FINISHED', 'assigned_user_id' => 'u', 'current_holder_company_id' => null, 'current_holder_user_id' => null, 'entered_current_status_at' => '2026-10-20 10:00:00', 'statusSist' => 'LIB CNPA'];

    expect(ClosureSignal::forRow($row)['key'])->toBe(ClosureSignal::OVERDUE)
        ->and(ClosureSignal::forRow((object) ['operational_status' => null]))->toBeNull();

    $order = new Order(['statusSist' => 'LIB CNPA']);
    $state = new ClosureOrderState(['operational_status' => 'AVAILABLE', 'current_holder_company_id' => 'c-1', 'entered_current_status_at' => '2026-10-25 10:00:00']);
    $state->setRelation('Order', $order);

    expect(ClosureSignal::forState($state)['key'])->toBe(ClosureSignal::PILE);
    expect(new Note())->toBeInstanceOf(Note::class);
});

it('exposes one legend, in precedence order, with the same labels everywhere', function () {
    expect(collect(ClosureSignal::legend())->pluck('key')->all())->toBe(['overdue', 'done', 'assigned', 'pile'])
        ->and(collect(ClosureSignal::legend())->pluck('label')->all())->toBe(['Sem baixa no SAP', 'Concluída', 'Atribuída', 'Na pilha, sem atribuição']);

    $html = (string) $this->blade('<x-closure.signal-legend />');

    expect($html)->toContain('signal-legend')->toContain('signal-assigned')->toContain('signal-done')->toContain('signal-overdue')->toContain('signal-pile')
        ->and((string) $this->blade('<x-closure.signal :signal="$s" />', ['s' => ClosureSignal::legend()[0]]))->toContain('signal signal-overdue')->toContain('data-signal="overdue"')
        ->and(trim((string) $this->blade('<x-closure.signal :signal="null" />')))->toBe('');
});
