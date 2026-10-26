<?php

use App\Http\Livewire\Closure\Work\All;
use App\Models\Closure\ClosureOrderState;
use App\Models\{Company, Note, Operation, Order, Service, User};
use App\Services\Closure\{ClosureDispatchService, ClosureSettings};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, Notification};
use Livewire\Livewire;

// "Lista Encerramento" do Serviço: todas as Ordens elegíveis; assumir só quando o despacho liberou a auto-atribuição.

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-10-16 10:00:00');
    Cache::flush();
    Notification::fake();

    Service::query()->firstOrCreate(
        ['folder' => ClosureDispatchService::SERVICE_FOLDER],
        ['service' => 'Encerramento', 'project' => true, 'construction' => true, 'icon' => 'ri-checkbox-circle-line']
    );
});

afterEach(fn () => Carbon::setTestNow());

function allOperator(?Company $company = null): User
{
    $company ??= Company::query()->create(['name' => 'Empresa ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()]);

    return User::factory()->create(['closure_operator' => true, 'closure_manager' => false, 'admin' => false, 'superadm' => false, 'company_id' => $company->id]);
}

/** @param string $fimReal 2026-07-10 = P (passiva), 2026-09-10 = M (meta), 2026-10-05 = F (futura) */
function allOrder(string $fimReal): Order
{
    $note  = Note::query()->create(['note' => (string) fake()->unique()->numberBetween(1000000, 9999999), 'type_note' => 1, 'dt_status' => now()]);
    $order = Order::query()->create(['note_id' => $note->id, 'ordem' => (string) fake()->unique()->numberBetween(100000000, 999999999), 'statusSist' => 'LIB CNPA', 'canceled' => false]);

    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => $fimReal]);

    return $order;
}

function allEnable(User $by, array $types = ['P', 'M']): void
{
    app(ClosureSettings::class)->setSelfAssignEnabled(true, $by);
    app(ClosureSettings::class)->setSelfAssignTypes($types, $by);
    Cache::flush();
}

it('lists every eligible order, dispatched or not, not only the dispatched ones', function () {
    $user       = allOperator();
    $undispatch = allOrder('2026-07-10');
    $taken      = allOrder('2026-09-10');
    allEnable($user);

    app(ClosureDispatchService::class)->dispatchOrders([$taken->id], $user->Company, $user, User::factory()->create(['closure_manager' => true]));

    Livewire::actingAs($user)->test(All::class)
        ->assertSee($undispatch->ordem)
        ->assertSee($taken->ordem)
        ->assertSee('Não despachada');
});

it('hides the list (tab and access) while the dispatch has not enabled self-assign', function () {
    $user = allOperator();

    Livewire::actingAs($user)->test(All::class)->assertForbidden();

    $service = Service::query()->where('folder', ClosureDispatchService::SERVICE_FOLDER)->first();
    \App\Models\Bancoupdate::query()->forceCreate(['inserts' => 0, 'updates' => 0]);
    $this->actingAs($user);
    \App\Models\ServiceUser::query()->create(['user_id' => $user->id, 'service_id' => $service->uuid, 'service' => true, 'dispatch' => false]);

    foreach ([[], ['aba' => 'todas'], ['aba' => 'disponiveis']] as $query) {
        $this->get(route('services.main', ['service' => $service->uuid] + $query))
            ->assertOk()->assertSee('Minha Atividade')->assertDontSee('Todas as Ordens')->assertDontSee('Lista Encerramento');
    }
});

it('shows the tab and the list as the default once the dispatch enabled self-assign', function () {
    $user = allOperator();
    allEnable($user);

    $service = Service::query()->where('folder', ClosureDispatchService::SERVICE_FOLDER)->first();
    \App\Models\Bancoupdate::query()->forceCreate(['inserts' => 0, 'updates' => 0]);
    \App\Models\ServiceUser::query()->create(['user_id' => $user->id, 'service_id' => $service->uuid, 'service' => true, 'dispatch' => false]);

    $this->actingAs($user)->get(route('services.main', ['service' => $service->uuid]))
        ->assertOk()->assertSee('Todas as Ordens')->assertSee('Lista Encerramento');

    $this->get(route('services.main', ['service' => $service->uuid, 'aba' => 'minhas']))->assertOk()->assertSee('Minha Atividade');
});

it('does not take anything if the dispatch revokes the permission after the list was opened', function () {
    $user  = allOperator();
    $order = allOrder('2026-07-10');
    allEnable($user);

    $component = Livewire::actingAs($user)->test(All::class)->call('toggleOrder', $order->id);

    app(ClosureSettings::class)->setSelfAssignEnabled(false, $user);
    Cache::flush();

    $component->call('assumeConfirmed')->assertDispatchedBrowserEvent('notification:toast', ['title' => 'Auto-atribuição desabilitada', 'text' => 'O despacho não liberou a escolha de atividades.', 'icon' => 'warning']);

    expect(ClosureOrderState::query()->count())->toBe(0);
});

it('self-dispatches an undispatched order to the user when the dispatch allowed it', function () {
    $user  = allOperator();
    $order = allOrder('2026-07-10');
    allEnable($user);

    Livewire::actingAs($user)->test(All::class)
        ->assertSee('Auto-atribuição liberada')
        ->call('toggleOrder', $order->id)
        ->call('assumeConfirmed')
        ->assertSee('Assumidas');

    $state = ClosureOrderState::query()->where('order_id', $order->id)->firstOrFail();

    expect($state->assigned_user_id)->toBe($user->id)
        ->and((string) $state->current_holder_company_id)->toBe((string) $user->company_id);
});

it('refuses a type the dispatch did not release, and does not even offer the checkbox', function () {
    $user   = allOperator();
    $future = allOrder('2026-10-05');
    allEnable($user, ['P', 'M']);

    $component = Livewire::actingAs($user)->test(All::class)->assertSeeHtml('Ordem ' . $future->ordem . ': O despacho não liberou este tipo');

    $component->set('selectedOrderIds', [$future->id])->call('assumeConfirmed');

    expect(ClosureOrderState::query()->count())->toBe(0);
});

it('never takes an order that already has an owner or sits in another company pile', function () {
    $user    = allOperator();
    $other   = allOperator();
    $owned   = allOrder('2026-07-10');
    $foreign = allOrder('2026-09-10');
    $manager = User::factory()->create(['closure_manager' => true]);
    allEnable($user);

    app(ClosureDispatchService::class)->dispatchOrders([$owned->id], $other->Company, $other, $manager);
    app(ClosureDispatchService::class)->dispatchOrders([$foreign->id], $other->Company, null, $manager);

    Livewire::actingAs($user)->test(All::class)
        ->set('selectedOrderIds', [$owned->id, $foreign->id])
        ->call('assumeConfirmed')
        ->assertSee('já está com')
        ->assertSee('pilha de');

    expect(ClosureOrderState::query()->where('order_id', $owned->id)->value('assigned_user_id'))->toBe($other->id)
        ->and(ClosureOrderState::query()->where('order_id', $foreign->id)->value('assigned_user_id'))->toBeNull();
});

it('claims an order sitting in the pile of the user company', function () {
    $user    = allOperator();
    $order   = allOrder('2026-07-10');
    $manager = User::factory()->create(['closure_manager' => true]);
    allEnable($user);

    app(ClosureDispatchService::class)->dispatchOrders([$order->id], $user->Company, null, $manager);

    Livewire::actingAs($user)->test(All::class)->set('selectedOrderIds', [$order->id])->call('assumeConfirmed');

    expect(ClosureOrderState::query()->where('order_id', $order->id)->value('assigned_user_id'))->toBe($user->id);
});

it('filters by scope, type and search', function () {
    $user    = allOperator();
    $mine    = allOrder('2026-07-10');
    $free    = allOrder('2026-09-10');
    $manager = User::factory()->create(['closure_manager' => true]);
    allEnable($user);

    app(ClosureDispatchService::class)->dispatchOrders([$mine->id], $user->Company, $user, $manager);

    Livewire::actingAs($user)->test(All::class)
        ->set('scope', 'free')->assertSee($free->ordem)->assertDontSee($mine->ordem)
        ->set('scope', 'mine')->assertSee($mine->ordem)->assertDontSee($free->ordem)
        ->set('scope', 'all')->set('type', 'M')->assertSee($free->ordem)->assertDontSee($mine->ordem)
        ->set('type', 'all')->set('search', $mine->ordem)->assertSee($mine->ordem)->assertDontSee($free->ordem);
});

it('refuses users without Encerramento access', function () {
    $outsider = User::factory()->create(['closure_operator' => false, 'closure_manager' => false, 'admin' => false, 'superadm' => false]);

    Livewire::actingAs($outsider)->test(All::class)->assertForbidden();
});
