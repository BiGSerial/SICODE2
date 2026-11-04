<?php

use App\Http\Livewire\Closure\Dispatch\Index;
use App\Models\Closure\ClosurePartnerSignal;
use App\Models\{Note, Operation, Order, Service, User};
use App\Services\Closure\{ClosureDispatchService, ClosureEligibleOrders};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-10-16 10:00:00');
    Service::query()->firstOrCreate(
        ['folder' => ClosureDispatchService::SERVICE_FOLDER],
        ['service' => 'Encerramento', 'project' => true, 'construction' => true, 'icon' => 'ri-checkbox-circle-line']
    );
    ClosureEligibleOrders::forget();
});

afterEach(fn () => Carbon::setTestNow());

function depOrder(string $cenTrab): Order
{
    $note  = Note::query()->create(['note' => (string) fake()->unique()->numberBetween(1000000, 9999999), 'type_note' => 1, 'dt_status' => now()]);
    $order = Order::query()->create(['note_id' => $note->id, 'ordem' => (string) fake()->unique()->numberBetween(100000000, 999999999), 'statusSist' => 'LIB CNPA', 'canceled' => false]);
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => '2026-09-10']);
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0010', 'status' => 'CONF', 'cenTrab' => $cenTrab]);

    return $order;
}

function depBlock(string $code, array $centers): ClosurePartnerSignal
{
    return ClosurePartnerSignal::query()->create([
        'type'         => ClosurePartnerSignal::TYPE_DEPOSIT_BLOCKED, 'status' => ClosurePartnerSignal::STATUS_ACTIVE, 'origin' => ClosurePartnerSignal::ORIGIN_PARTNER,
        'deposit_code' => $code, 'work_centers' => $centers, 'reason' => 'Inventário', 'created_by' => User::factory()->create()->id,
    ]);
}

function depManager(): User
{
    return User::factory()->create(['closure_manager' => true, 'closure_operator' => false, 'admin' => false, 'superadm' => false]);
}

it('marks an undispatched Order whose work center belongs to a blocked deposit, and only that one', function () {
    $hit   = depOrder('CT-A');
    $other = depOrder('CT-B');
    depBlock('5503', ['CT-A']);

    $html = Livewire::actingAs(depManager())->test(Index::class)->assertSee('Depósito bloqueado')->lastRenderedDom;

    $row = fn (Order $o) => preg_match('/<tr[^>]*data-order-row="' . $o->id . '".*?<\/tr>/s', $html, $m) ? $m[0] : '';

    expect($row($hit))->toContain('No alcance do depósito 5503')->and($row($hit))->toContain('ainda não foi despachada')
        ->and($row($other))->not->toContain('depósito 5503');
});

it('counts the affected Orders in a card and filters to them with one click that undoes with a second', function () {
    $hit   = depOrder('CT-A');
    $other = depOrder('CT-B');
    depBlock('5503', ['CT-A']);

    $component = Livewire::actingAs(depManager())->test(Index::class)
        ->call('toggleCard', 'blocked')
        ->assertSet('onlyBlocked', true)
        ->assertSee($hit->ordem)->assertDontSee($other->ordem)
        ->assertSee('Só as afetadas por bloqueio')
        ->call('toggleCard', 'blocked')
        ->assertSee($other->ordem);

    expect(app(\App\Services\Closure\Queries\ClosureDispatchListQuery::class)->overview(['blockedCenters' => ['CT-A']], 25, 1)['counts']['blocked'])->toBe(1);
});

it('shows no deposit card or banner when nothing is blocked', function () {
    depOrder('CT-A');

    Livewire::actingAs(depManager())->test(Index::class)->assertDontSee('Depósito bloqueado')->assertDontSee('No alcance do depósito');
});
