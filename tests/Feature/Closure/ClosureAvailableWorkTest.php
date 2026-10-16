<?php

use App\Http\Livewire\Closure\Work\Available;
use App\Models\Closure\{ClosureOrderEvent, ClosureOrderState};
use App\Models\{Company, Note, Operation, Order, Production, Service, User};
use App\Services\Closure\{ClosureOrderWorkflowService, ClosureSettings};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow('2026-10-16 10:00:00'));
afterEach(fn () => Carbon::setTestNow());

function availUser(?Company $company = null, bool $operator = true): User
{
    $company ??= Company::query()->create(['name' => 'Parceira ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()]);

    return User::factory()->create([
        'company_id'       => $company->id,
        'closure_operator' => $operator,
        'closure_manager'  => false,
        'admin'            => false,
        'superadm'         => false,
    ]);
}

function availState(Company $company, string $fimReal = '2026-09-10', string $status = 'AVAILABLE'): ClosureOrderState
{
    $service = Service::query()->firstOrCreate(
        ['folder' => 'encerramento'],
        ['service' => 'Encerramento', 'project' => true, 'construction' => true, 'icon' => 'ri-checkbox-circle-line'],
    );
    $note  = Note::query()->create(['note' => (string) fake()->unique()->numberBetween(1000000, 9999999), 'type_note' => 1]);
    $order = Order::query()->create(['note_id' => $note->id, 'ordem' => (string) fake()->unique()->numberBetween(100000000, 999999999), 'statusSist' => 'LIB CNPA', 'canceled' => false]);
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => $fimReal]);
    $production = Production::query()->create(['note_id' => $note->id, 'service_id' => $service->uuid, 'company_id' => $company->id]);

    return ClosureOrderState::query()->create([
        'order_id'                  => $order->id,
        'production_id'             => $production->id,
        'operational_status'        => $status,
        'current_holder_type'       => ClosureOrderWorkflowService::HOLDER_PARTNER_COMPANY,
        'current_holder_company_id' => $company->id,
        'entered_current_status_at' => now()->subHour(),
    ]);
}

it('shows a clear message and no list when self assignment is disabled', function () {
    $user = availUser();
    availState($user->Company ?? Company::find($user->company_id));
    $this->actingAs($user);

    Livewire::test(Available::class)
        ->assertSee('Escolha de atividades desabilitada')
        ->assertDontSee('Assumir selecionadas')
        ->set('selectedStateIds', [1])
        ->call('askClaim')
        ->assertNotDispatchedBrowserEvent('closure:confirm')
        ->assertDispatchedBrowserEvent('notification:toast', fn ($n, $d) => $d['icon'] === 'warning');
});

it('enforces the closure.work gate', function () {
    $user = availUser(null, false);
    $this->actingAs($user)->get(route('closure.work.available'))->assertForbidden();
    Livewire::test(Available::class)->assertStatus(403);
});

it('lists only available orders of the allowed types from the user company pile', function () {
    $user    = availUser();
    $company = Company::find($user->company_id);
    $other   = Company::query()->create(['name' => 'Outra', 'email' => 'o@x.com']);
    app(ClosureSettings::class)->setSelfAssignEnabled(true, $user);
    app(ClosureSettings::class)->setSelfAssignTypes(['P', 'M'], $user);

    $m      = availState($company, '2026-09-10');
    $p      = availState($company, '2026-07-10');
    $f      = availState($company, '2026-10-05');
    $busy   = availState($company, '2026-09-11', 'IN_PROGRESS');
    $theirs = availState($other, '2026-09-12');

    $this->actingAs($user);

    Livewire::test(Available::class)
        ->assertSee($m->Order->ordem)->assertSee($p->Order->ordem)
        ->assertDontSee($f->Order->ordem)
        ->assertDontSee($busy->Order->ordem)
        ->assertDontSee($theirs->Order->ordem)
        ->assertSeeHtml('title="Passivo">P<')
        ->set('type', 'P')
        ->assertSee($p->Order->ordem)->assertDontSee($m->Order->ordem);

    app(ClosureSettings::class)->setSelfAssignTypes(['F'], $user);
    Livewire::test(Available::class)->assertSee($f->Order->ordem)->assertDontSee($m->Order->ordem);
});

it('claims selected orders after confirmation without notifying anyone', function () {
    Notification::fake();
    $user    = availUser();
    $company = Company::find($user->company_id);
    app(ClosureSettings::class)->setSelfAssignEnabled(true, $user);
    $a = availState($company);
    $b = availState($company, '2026-07-10');
    $this->actingAs($user);

    Livewire::test(Available::class)
        ->set('selectedStateIds', [$a->id, $b->id])
        ->call('askClaim')
        ->assertDispatchedBrowserEvent('closure:confirm')
        ->call('claimConfirmed')
        ->assertDispatchedBrowserEvent('notification:toast', fn ($n, $d) => $d['title'] === 'Ordens assumidas')
        ->assertSet('receipt.successes', [$a->Order->ordem, $b->Order->ordem])
        ->assertSet('selectedStateIds', []);

    expect($a->fresh()->operational_status)->toBe('ASSIGNED')
        ->and($a->fresh()->assigned_user_id)->toBe($user->id)
        ->and($b->fresh()->current_holder_user_id)->toBe($user->id)
        ->and(ClosureOrderEvent::query()->where('type', 'CLAIMED')->count())->toBe(2);

    Notification::assertNothingSent();
});

it('refuses the second claim of the same order and keeps partial results', function () {
    $first   = availUser();
    $company = Company::find($first->company_id);
    $second  = availUser($company);
    app(ClosureSettings::class)->setSelfAssignEnabled(true, $first);
    $contested = availState($company);
    $free      = availState($company, '2026-07-10');

    // Segundo usuário abre a lista (seleção feita) antes do primeiro assumir.
    $this->actingAs($second);
    $component = Livewire::test(Available::class)->set('selectedStateIds', [$contested->id, $free->id]);

    $this->actingAs($first);
    Livewire::test(Available::class)->set('selectedStateIds', [$contested->id])->call('claimConfirmed');
    expect($contested->fresh()->assigned_user_id)->toBe($first->id);

    $this->actingAs($second);
    $component->call('claimConfirmed')
        ->assertSet('receipt.successes', [$free->Order->ordem])
        ->assertSet('receipt.refusals.0.order', $contested->Order->ordem)
        ->assertDispatchedBrowserEvent('notification:toast', fn ($n, $d) => $d['icon'] === 'warning');

    expect($contested->fresh()->assigned_user_id)->toBe($first->id)
        ->and($free->fresh()->assigned_user_id)->toBe($second->id);
});

it('refuses an order whose type is no longer allowed at confirmation time', function () {
    $user    = availUser();
    $company = Company::find($user->company_id);
    app(ClosureSettings::class)->setSelfAssignEnabled(true, $user);
    $state = availState($company, '2026-09-10');
    $this->actingAs($user);

    $component = Livewire::test(Available::class)->set('selectedStateIds', [$state->id]);
    app(ClosureSettings::class)->setSelfAssignTypes(['P'], $user);
    \Illuminate\Support\Facades\Cache::flush();

    $component->call('claimConfirmed')->assertSet('receipt.refusals.0.order', $state->Order->ordem);

    expect($state->fresh()->operational_status)->toBe('AVAILABLE');
});

it('limits a claim to one hundred orders', function () {
    $user = availUser();
    app(ClosureSettings::class)->setSelfAssignEnabled(true, $user);
    $this->actingAs($user);

    Livewire::test(Available::class)
        ->set('selectedStateIds', range(1, 101))
        ->call('askClaim')
        ->assertNotDispatchedBrowserEvent('closure:confirm')
        ->assertDispatchedBrowserEvent('notification:toast', fn ($n, $d) => $d['title'] === 'Seleção grande demais');
});
