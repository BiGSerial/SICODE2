<?php

use App\Http\Livewire\Closure\Settings\DispatchSettings;
use App\Models\{Note, Operation, Order, Service, User};
use App\Services\Closure\{ClosureDispatchService, ClosureEligibleOrders, ClosureSettings};
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

function cfgManager(): User
{
    return User::factory()->create(['closure_manager' => true, 'closure_operator' => false, 'admin' => false, 'superadm' => false]);
}

function cfgOrder(?string $rubrica): Order
{
    $note  = Note::query()->create(['note' => (string) fake()->unique()->numberBetween(1000000, 9999999), 'type_note' => 1, 'dt_status' => now(), 'rubrica' => $rubrica]);
    $order = Order::query()->create(['note_id' => $note->id, 'ordem' => (string) fake()->unique()->numberBetween(100000000, 999999999), 'statusSist' => 'LIB CNPA', 'canceled' => false]);
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => '2026-09-10']);

    return $order;
}

it('persists the settings page and shows a single toast', function () {
    Livewire::actingAs(cfgManager())->test(DispatchSettings::class)
        ->set('allowFutureCompletion', true)
        ->set('selfAssignEnabled', true)
        ->set('selfAssignTypes', ['M', 'F'])
        ->call('save')
        ->assertDispatchedBrowserEvent('notification:toast');

    $settings = app(ClosureSettings::class);

    expect($settings->allowFutureCompletion())->toBeTrue()
        ->and($settings->selfAssignEnabled())->toBeTrue()
        ->and($settings->selfAssignTypes())->toBe(['M', 'F']);
});

it('forbids the settings page without the dispatch gate and serves it to the dispatcher', function () {
    $operator = User::factory()->create(['closure_manager' => false, 'closure_operator' => true, 'admin' => false, 'superadm' => false]);

    Livewire::actingAs($operator)->test(DispatchSettings::class)->assertStatus(403);
    $route = app('router')->getRoutes()->getByName('closure.settings');

    expect($route->uri())->toBe('encerramento/configuracoes')->and($route->gatherMiddleware())->toContain('can:closure.dispatch');
    Livewire::actingAs(cfgManager())->test(DispatchSettings::class)->assertSee('Configurações do Encerramento')->assertSee('Rubricas fora do Encerramento');
});

it('hides the "Recomposição" rubric by default and lets the dispatcher change the list', function () {
    $recomp  = cfgOrder('Recomposição');
    $mercado = cfgOrder('Mercado');
    $semRub  = cfgOrder(null);
    $manager = cfgManager();

    expect(app(ClosureSettings::class)->excludedRubrics())->toBe(['Recomposição']);

    $ids = fn () => array_keys((new ClosureEligibleOrders())->all());

    expect($ids())->toContain($mercado->id, $semRub->id)->not->toContain($recomp->id);

    // O despachante troca a lista: salvar limpa a elegibilidade em cache na hora.
    Livewire::actingAs($manager)->test(DispatchSettings::class)
        ->assertSet('excludedRubrics', ['Recomposição'])
        ->set('excludedRubrics', ['Mercado'])
        ->call('save');

    expect($ids())->toContain($recomp->id, $semRub->id)->not->toContain($mercado->id);

    Livewire::actingAs($manager)->test(DispatchSettings::class)->call('restoreDefaultRubrics')->assertSet('excludedRubrics', ['Recomposição']);
});

it('keeps an excluded-rubric Order visible once it was dispatched, and filters the list by rubric', function () {
    $recomp  = cfgOrder('Recomposição');
    $mercado = cfgOrder('Mercado');
    $manager = cfgManager();
    app(ClosureSettings::class)->setExcludedRubrics([], $manager);

    \Livewire\Livewire::actingAs($manager)->test(\App\Http\Livewire\Closure\Dispatch\Index::class)
        ->assertSee($recomp->ordem)->assertSee($mercado->ordem)
        ->call('applyListFilters', ['rubric' => ['Mercado']])
        ->assertSee($mercado->ordem)->assertDontSee($recomp->ordem)
        ->call('applyListFilters', ['rubric' => ['Inexistente']])
        ->assertSet('filters', []);
});
