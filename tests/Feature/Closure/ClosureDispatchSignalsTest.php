<?php

use App\Http\Livewire\Closure\Dispatch\Index;
use App\Models\Closure\ClosureOrderState;
use App\Models\{Company, Note, Operation, Order, User};
use App\Services\Closure\{ClosureDispatchException, ClosureDispatchService, ClosureEligibleOrders};
use App\Services\Closure\Queries\ClosureDispatchListQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

// Despacho: Ordem que já tem posse nunca é despachada de novo + sinalização por cor em cada linha.

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-10-25 12:00:00');
    ClosureEligibleOrders::forget();
});

afterEach(fn () => Carbon::setTestNow());

/** Estado da Ordem pronto para a lista do Despacho (elegível: LIB + 0020 CONF com fim real). */
function sigState(string $status, array $stateAttrs = [], string $statusSist = 'LIB CNPA'): ClosureOrderState
{
    $f = closureWorkflowFixture($status);

    $f['order']->update(['statusSist' => $statusSist]);
    Operation::query()->create(['order_id' => $f['order']->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => '2026-09-10']);
    $f['state']->update($stateAttrs);
    ClosureEligibleOrders::forget();

    return $f['state']->fresh();
}

/** Ordem elegível ainda sem posse (não despachada). */
function sigUndispatched(): Order
{
    $note  = Note::query()->create(['note' => (string) fake()->unique()->numberBetween(1000000, 9999999), 'type_note' => 1, 'dt_status' => now()]);
    $order = Order::query()->create(['note_id' => $note->id, 'ordem' => (string) fake()->unique()->numberBetween(100000000, 999999999), 'statusSist' => 'LIB CNPA', 'canceled' => false]);
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => '2026-09-10']);
    ClosureEligibleOrders::forget();

    return $order;
}

function sigManager(): User
{
    return User::factory()->create(['closure_manager' => true, 'closure_operator' => false, 'admin' => false, 'superadm' => false]);
}

it('paints the whole row: yellow pile, blue assigned, green done, red 24h without SAP closure, with the legend on the list', function () {
    $assignee = User::factory()->create();

    $pile     = sigState('AVAILABLE', ['assigned_user_id' => null, 'entered_current_status_at' => '2026-10-25 09:00:00']);
    $assigned = sigState('IN_PROGRESS', ['assigned_user_id' => $assignee->id, 'entered_current_status_at' => '2026-10-25 09:00:00']);
    $done     = sigState('FINISHED', ['assigned_user_id' => $assignee->id, 'entered_current_status_at' => '2026-10-25 08:00:00']);
    $overdue  = sigState('FINISHED', ['assigned_user_id' => $assignee->id, 'entered_current_status_at' => '2026-10-23 08:00:00']);
    $closedOk = sigState('FINISHED', ['assigned_user_id' => $assignee->id, 'entered_current_status_at' => '2026-10-20 08:00:00'], 'LIB CNPA');
    $closedOk->Order->update(['statusSist' => 'LIB CNPA']);

    $html = Livewire::actingAs(sigManager())->test(Index::class)
        ->assertSee('Na pilha, sem atribuição')->assertSee('Atribuída')->assertSee('Concluída')->assertSee('Sem baixa no SAP')
        ->assertSeeHtml('signal-legend')
        ->lastRenderedDom;

    foreach ([
        [$pile, 'pile'], [$assigned, 'assigned'], [$done, 'done'], [$overdue, 'overdue'],
    ] as [$state, $key]) {
        $row = preg_match('/<tr[^>]*data-order-row="' . $state->order_id . '".*?<\/tr>/s', $html, $m) ? $m[0] : '';

        expect($row)->toContain('signal-row signal-' . $key)->and($row)->toContain('data-signal="' . $key . '"');
    }

    // o vermelho cobre também a Ordem finalizada há dias sem baixa no SAP
    $row = preg_match('/<tr[^>]*data-order-row="' . $closedOk->order_id . '".*?<\/tr>/s', $html, $m) ? $m[0] : '';
    expect($row)->toContain('data-signal="overdue"');
});

it('does not color an Order that was not dispatched, and a SAP-closed finished Order stays green', function () {
    $undispatched = sigUndispatched();
    $assignee     = User::factory()->create();
    $finished     = sigState('FINISHED', ['assigned_user_id' => $assignee->id, 'entered_current_status_at' => '2026-10-10 08:00:00']);
    $finished->Order->update(['statusSist' => 'LIB CNPA']);

    $html = Livewire::actingAs(sigManager())->test(Index::class)->lastRenderedDom;

    $row = preg_match('/<tr[^>]*data-order-row="' . $undispatched->id . '".*?<\/tr>/s', $html, $m) ? $m[0] : '';

    expect($row)->not->toContain('signal-row')->and($row)->not->toContain('data-signal=')->and($row)->toContain('Não despachada');
});

it('disables selection and dispatch on an Order that already has an owner, explaining why', function () {
    $assignee = User::factory()->create(['name' => 'Fulana Responsável']);
    $held     = sigState('ASSIGNED', ['assigned_user_id' => $assignee->id]);
    $free     = sigUndispatched();

    $component = Livewire::actingAs(sigManager())->test(Index::class);
    $html      = $component->lastRenderedDom;

    $heldRow = preg_match('/<tr[^>]*data-order-row="' . $held->order_id . '".*?<\/tr>/s', $html, $m) ? $m[0] : '';
    $freeRow = preg_match('/<tr[^>]*data-order-row="' . $free->id . '".*?<\/tr>/s', $html, $m) ? $m[0] : '';

    expect($heldRow)->toContain('is-dispatched')
        ->and($heldRow)->toContain('Já despachada: está com Fulana Responsável')
        ->and($heldRow)->toContain('Transferência')
        ->and($heldRow)->not->toContain('data-order-check')
        ->and($heldRow)->not->toContain('openModal([' . $held->order_id . '])')
        ->and($heldRow)->toContain('disabled')
        ->and($freeRow)->toContain('data-order-check="' . $free->id . '"')
        ->and($freeRow)->toContain('openModal([' . $free->id . '])');
});

it('says the pile when the dispatched Order has no assignee yet', function () {
    $company = Company::query()->create(['name' => 'Manserv Pilha', 'email' => fake()->unique()->safeEmail()]);
    $held    = sigState('AVAILABLE', ['assigned_user_id' => null, 'current_holder_company_id' => $company->id]);

    Livewire::actingAs(sigManager())->test(Index::class)->assertSee('Já despachada: está com a pilha de Manserv Pilha');

    expect($held->operational_status)->toBe('AVAILABLE');
});

it('selects only the Orders that can still be dispatched when selecting all filtered', function () {
    sigState('ASSIGNED', ['assigned_user_id' => User::factory()->create()->id]);
    $a = sigUndispatched();
    $b = sigUndispatched();

    $component = Livewire::actingAs(sigManager())->test(Index::class)->call('selectAllFiltered');
    $selected  = collect($component->get('selectedOrderIds'))->map(fn ($id) => (int) $id)->sort()->values()->all();

    expect($selected)->toBe(collect([$a->id, $b->id])->sort()->values()->all());

    $query = app(ClosureDispatchListQuery::class);

    expect($query->filteredIds([], null))->toHaveCount(3)
        ->and($query->filteredIds([], null, true))->toHaveCount(2)
        ->and($query->overview([], 25, 1)['counts']['selectable'])->toBe(2);

    // o rótulo do botão conta só as despacháveis
    $component->assertSee('Selecionar todas filtradas (2)');
});

it('refuses to dispatch again an Order that already has an owner, even to the same company and assignee', function () {
    $f       = closureWorkflowFixture('AVAILABLE');
    $service = app(ClosureDispatchService::class);

    $f['order']->update(['statusSist' => 'LIB CNPA']);
    Operation::query()->create(['order_id' => $f['order']->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => '2026-09-10']);

    $dispatcher = User::factory()->create(['closure_manager' => true]);

    // mesma empresa, sem responsável (o destino que já está em vigor)
    expect(fn () => $service->dispatch($f['order']->Note, [$f['order']->id], $f['company'], null, $dispatcher))
        ->toThrow(ClosureDispatchException::class, 'já foi despachada');

    // empresa diferente
    $other = Company::query()->create(['name' => 'Outra', 'email' => fake()->unique()->safeEmail()]);
    expect(fn () => $service->dispatch($f['order']->Note, [$f['order']->id], $other, null, $dispatcher))
        ->toThrow(ClosureDispatchException::class, 'Transferência');

    // em lote: recusada com o motivo, sem criar nada
    $multi = $service->dispatchOrders([$f['order']->id], $f['company'], null, $dispatcher);

    expect($multi->refusedOrderIds()->all())->toBe([(int) $f['order']->id])
        ->and($multi->refusals()[(int) $f['order']->id])->toContain('Transferência')
        ->and(ClosureOrderState::query()->count())->toBe(1);
});

it('flags it in the eligibility preview of the dispatch modal', function () {
    $f = closureWorkflowFixture('ASSIGNED');
    $f['state']->update(['assigned_user_id' => User::factory()->create(['name' => 'Beltrano'])->id]);
    Operation::query()->create(['order_id' => $f['order']->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => '2026-09-10']);
    ClosureEligibleOrders::forget();

    $rows = app(ClosureDispatchListQuery::class)->eligibility([$f['order']->id], null, null);

    expect($rows[0]['eligible'])->toBeFalse()
        ->and($rows[0]['reason'])->toContain('já foi despachada')->toContain('Beltrano');
});

it('shows the same signals and legend on the Control list', function () {
    $assignee = User::factory()->create();
    $overdue  = sigState('FINISHED', ['assigned_user_id' => $assignee->id, 'entered_current_status_at' => '2026-10-23 08:00:00']);

    $html = Livewire::actingAs(sigManager())->test(\App\Http\Livewire\Closure\Dispatch\Control::class)->lastRenderedDom;

    expect($html)->toContain('signal-legend')->toContain('data-signal="overdue"')->toContain('signal-row signal-overdue');

    expect($overdue->operational_status)->toBe('FINISHED');
});
