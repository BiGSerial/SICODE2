<?php

use App\Http\Livewire\Closure\Dispatch\Index;
use App\Models\Closure\ClosureOrderState;
use App\Models\{Company, Note, Operation, Order, Service, User};
use App\Services\Closure\{ClosureDispatchService, ClosureNotifier, ClosureSettings};
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
});

afterEach(fn () => Carbon::setTestNow());

function screenManager(): User
{
    return User::factory()->create(['closure_manager' => true, 'closure_operator' => false, 'admin' => false, 'superadm' => false]);
}

function screenOrder(string $fimReal, ?Note $note = null, array $attrs = []): Order
{
    $note ??= Note::query()->create([
        'note'      => (string) fake()->unique()->numberBetween(1000000, 9999999),
        'type_note' => 1,
        'dt_status' => now(),
    ]);

    $order = Order::query()->create(array_merge([
        'note_id'    => $note->id,
        'ordem'      => (string) fake()->unique()->numberBetween(100000000, 999999999),
        'statusSist' => 'LIB CNPA',
        'canceled'   => false,
    ], $attrs));

    Operation::query()->create([
        'order_id' => $order->id,
        'operacao' => '0020',
        'status'   => 'CONF',
        'fimReal'  => $fimReal,
    ]);

    return $order;
}

it('lists one row per order with P/M/F badges', function () {
    $passive = screenOrder('2026-07-10');
    $meta    = screenOrder('2026-09-10');
    $future  = screenOrder('2026-10-05');

    Livewire::actingAs(screenManager())->test(Index::class)
        ->assertSee($passive->ordem)
        ->assertSee($meta->ordem)
        ->assertSee($future->ordem)
        ->assertSeeHtml('class="badge bg-danger" title="Passivo">P<')
        ->assertSeeHtml('class="badge bg-success" title="Meta atual">M<')
        ->assertSeeHtml('class="badge bg-primary" title="Meta futura">F<');
});

it('filters by meta type and by undispatched', function () {
    $passive = screenOrder('2026-07-10');
    $meta    = screenOrder('2026-09-10');
    $future  = screenOrder('2026-10-05');

    Livewire::actingAs(screenManager())->test(Index::class)
        ->set('types', ['P', 'F'])
        ->assertSee($passive->ordem)
        ->assertSee($future->ordem)
        ->assertDontSee($meta->ordem)
        ->set('types', ['M'])
        ->assertSee($meta->ordem)
        ->assertDontSee($passive->ordem)
        ->set('types', [])
        ->set('filterAssigneeId', '')
        ->set('search', $future->ordem)
        ->assertSee($future->ordem)
        ->assertDontSee($meta->ordem);
});

it('hides dispatched orders when only undispatched is on', function () {
    $company    = Company::query()->create(['name' => 'Parceira', 'email' => fake()->unique()->safeEmail()]);
    $manager    = screenManager();
    $dispatched = screenOrder('2026-09-10');
    $open       = screenOrder('2026-09-11');

    app(ClosureDispatchService::class)->dispatchOrders([$dispatched->id], $company, null, $manager);

    Livewire::actingAs($manager)->test(Index::class)
        ->assertSee($dispatched->ordem)
        ->assertSee('Disponível')
        ->set('onlyUndispatched', true)
        ->assertDontSee($dispatched->ordem)
        ->assertSee($open->ordem);
});

it('dispatches orders of several notes with partial success and notifies each assigned order once', function () {
    $company  = Company::query()->create(['name' => 'Parceira', 'email' => fake()->unique()->safeEmail()]);
    $assignee = User::factory()->create(['company_id' => $company->id]);
    $manager  = screenManager();
    $a        = screenOrder('2026-09-10');
    $b        = screenOrder('2026-07-10');
    $canceled = screenOrder('2026-09-12', null, ['canceled' => true]);

    $notifier = Mockery::mock(ClosureNotifier::class);
    $notifier->shouldReceive('orderAssigned')->twice();
    app()->instance(ClosureNotifier::class, $notifier);

    $component = Livewire::actingAs($manager)->test(Index::class)
        ->call('toggleOrder', $a->id)
        ->call('toggleOrder', $b->id)
        ->call('toggleOrder', $canceled->id)
        ->set('companyId', (string) $company->id)
        ->set('assigneeId', $assignee->id)
        ->call('preparePreview')
        ->assertSet('showPreview', true)
        ->assertSee('Ordem cancelada.')
        ->call('confirmDispatch')
        ->assertSee('2 despachada(s)')
        ->assertSee('1 recusada(s)')
        ->assertSet('selectedOrderIds', [$canceled->id]);

    expect($component->get('receipt'))->toHaveCount(3);
    expect(ClosureOrderState::query()->count())->toBe(2)
        ->and(ClosureOrderState::query()->where('assigned_user_id', $assignee->id)->count())->toBe(2);
});

it('persists settings and shows a single toast on save', function () {
    $manager = screenManager();

    Livewire::actingAs($manager)->test(Index::class)
        ->call('openSettings')
        ->assertSet('showSettings', true)
        ->set('allowFutureCompletion', true)
        ->set('selfAssignEnabled', true)
        ->set('selfAssignTypes', ['M', 'F'])
        ->call('saveSettings')
        ->assertDispatchedBrowserEvent('notification:toast')
        ->assertSet('showSettings', false);

    $settings = app(ClosureSettings::class);

    expect($settings->allowFutureCompletion())->toBeTrue()
        ->and($settings->selfAssignEnabled())->toBeTrue()
        ->and($settings->selfAssignTypes())->toBe(['M', 'F']);
});

it('forbids users without the dispatch gate', function () {
    $user = User::factory()->create(['closure_manager' => false, 'closure_operator' => true, 'admin' => false, 'superadm' => false]);

    Livewire::actingAs($user)->test(Index::class)->assertStatus(403);
    $this->actingAs($user)->get(route('closure.dispatch'))->assertForbidden();
});
