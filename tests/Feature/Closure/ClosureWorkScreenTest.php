<?php

use App\Http\Livewire\Closure\Work\Index as WorkIndex;
use App\Models\Closure\{ClosureOrderEvent, ClosureOrderState};
use App\Models\{Company, Note, Operation, Order, Production, Service, User};
use App\Services\Closure\{ClosureOrderWorkflowService, ClosureSettings};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow('2026-10-16 10:00:00'));
afterEach(fn () => Carbon::setTestNow());

function workScreenUser(bool $manager = false, ?Company $company = null): User
{
    $company ??= Company::query()->create(['name' => 'Parceira ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()]);

    return User::factory()->create([
        'company_id'       => $company->id,
        'closure_operator' => true,
        'closure_manager'  => $manager,
        'admin'            => false,
        'superadm'         => false,
    ]);
}

/** fimReal define o tipo: 2026-09-10 = M, 2026-07-10 = P, 2026-10-05 = F. */
function workScreenState(User $owner, string $fimReal = '2026-09-10', string $status = 'IN_PROGRESS'): ClosureOrderState
{
    $service = Service::query()->firstOrCreate(
        ['folder' => 'encerramento'],
        ['service' => 'Encerramento', 'project' => true, 'construction' => true, 'icon' => 'ri-checkbox-circle-line'],
    );
    $note  = Note::query()->create(['note' => (string) fake()->unique()->numberBetween(1000000, 9999999), 'type_note' => 1, 'lexp' => 'Vitória']);
    $order = Order::query()->create(['note_id' => $note->id, 'ordem' => (string) fake()->unique()->numberBetween(100000000, 999999999), 'statusSist' => 'LIB CNPA', 'canceled' => false]);
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => $fimReal]);
    $production = Production::query()->create(['note_id' => $note->id, 'service_id' => $service->uuid, 'company_id' => $owner->company_id]);

    return ClosureOrderState::query()->create([
        'order_id'                  => $order->id,
        'production_id'             => $production->id,
        'operational_status'        => $status,
        'assigned_user_id'          => $owner->id,
        'current_holder_type'       => ClosureOrderWorkflowService::HOLDER_INTERNAL_USER,
        'current_holder_user_id'    => $owner->id,
        'current_holder_company_id' => $owner->company_id,
        'entered_current_status_at' => now()->subHour(),
    ]);
}

it('shows P, M and F badges per order and filters by type', function () {
    $user = workScreenUser();
    $p    = workScreenState($user, '2026-07-10');
    $m    = workScreenState($user, '2026-09-10');
    $f    = workScreenState($user, '2026-10-05');

    $this->actingAs($user);

    Livewire::test(WorkIndex::class)
        ->assertSeeHtml('badge bg-danger" title="Passivo">P<')
        ->assertSeeHtml('badge bg-success" title="Meta atual">M<')
        ->assertSeeHtml('badge bg-primary" title="Meta futura">F<')
        ->assertSee($p->Order->ordem)->assertSee($m->Order->ordem)->assertSee($f->Order->ordem)
        ->set('type', 'F')
        ->assertSee($f->Order->ordem)->assertDontSee($m->Order->ordem)->assertDontSee($p->Order->ordem)
        ->set('type', 'P')
        ->assertSee($p->Order->ordem)->assertDontSee($f->Order->ordem);
});

it('hides other users work from operators but shows everything to managers', function () {
    $company = Company::query()->create(['name' => 'Comum', 'email' => 'c@x.com']);
    $me      = workScreenUser(false, $company);
    $other   = workScreenUser(false, $company);
    $mine    = workScreenState($me);
    $theirs  = workScreenState($other);

    $this->actingAs($me);
    Livewire::test(WorkIndex::class)->assertSee($mine->Order->ordem)->assertDontSee($theirs->Order->ordem);

    $this->actingAs(workScreenUser(true, $company));
    Livewire::test(WorkIndex::class)->assertSee($mine->Order->ordem)->assertSee($theirs->Order->ordem);
});

it('changes status with a mandatory reason via toast feedback', function () {
    $user  = workScreenUser();
    $state = workScreenState($user);
    $this->actingAs($user);

    Livewire::test(WorkIndex::class)
        ->call('changeStatus', $state->id, 'PAUSED')
        ->assertSet('pendingAction', 'status')
        ->set('actionReason', '')
        ->call('confirmActionWithReason')
        ->assertDispatchedBrowserEvent('notification:toast', fn ($name, $data) => $data['icon'] === 'warning')
        ->set('actionReason', 'Aguardando material')
        ->call('confirmActionWithReason')
        ->assertDispatchedBrowserEvent('notification:toast', fn ($name, $data) => $data['title'] === 'Status alterado')
        ->assertSet('pendingAction', null);

    expect($state->fresh()->operational_status)->toBe('PAUSED');

    Livewire::test(WorkIndex::class)->call('changeStatus', $state->id, 'IN_PROGRESS');
    expect($state->fresh()->operational_status)->toBe('IN_PROGRESS');
});

it('rejects a status that the transition matrix does not allow', function () {
    $user  = workScreenUser();
    $state = workScreenState($user, '2026-09-10', 'ASSIGNED');
    $this->actingAs($user);

    Livewire::test(WorkIndex::class)
        ->call('changeStatus', $state->id, 'FINISHED')
        ->assertDispatchedBrowserEvent('notification:toast', fn ($name, $data) => $data['icon'] === 'warning');

    expect($state->fresh()->operational_status)->toBe('ASSIGNED');
});

it('blocks finishing a future-meta order when the setting is off', function () {
    $user  = workScreenUser();
    $state = workScreenState($user, '2026-10-05');
    $this->actingAs($user);

    Livewire::test(WorkIndex::class)
        ->assertSee('o despachante ainda não liberou a finalização')
        ->call('runAction', $state->id, 'finish')
        ->assertDispatchedBrowserEvent('notification:toast', fn ($name, $data) => $data['title'] === 'Finalização bloqueada');

    expect($state->fresh()->operational_status)->toBe('IN_PROGRESS');
});

it('sends a future-meta order to Aguardando when completion is allowed', function () {
    $user  = workScreenUser();
    $state = workScreenState($user, '2026-10-05');
    app(ClosureSettings::class)->setAllowFutureCompletion(true, $user);
    $this->actingAs($user);

    Livewire::test(WorkIndex::class)
        ->call('runAction', $state->id, 'finish')
        ->assertDispatchedBrowserEvent('closure:confirm')
        ->assertSet('confirmFinishId', $state->id)
        ->call('finishConfirmed')
        ->assertDispatchedBrowserEvent('notification:toast', fn ($name, $data) => $data['title'] === 'Ordem em Aguardando');

    expect($state->fresh()->operational_status)->toBe('WAITING');
});

it('finishes a regular order after confirmation', function () {
    $user  = workScreenUser();
    $state = workScreenState($user, '2026-09-10');
    $this->actingAs($user);

    Livewire::test(WorkIndex::class)
        ->call('runAction', $state->id, 'finish')
        ->call('finishConfirmed')
        ->assertDispatchedBrowserEvent('notification:toast', fn ($name, $data) => $data['title'] === 'Ordem finalizada');

    expect($state->fresh()->operational_status)->toBe('FINISHED');
});

it('runs batch status change and finish with a receipt per order', function () {
    $user = workScreenUser();
    $a    = workScreenState($user, '2026-09-10');
    $b    = workScreenState($user, '2026-10-05'); // F bloqueada
    $this->actingAs($user);

    Livewire::test(WorkIndex::class)
        ->set('selectedStateIds', [$a->id, $b->id])
        ->set('batchAction', 'finish')
        ->call('prepareBatchPreview')
        ->call('confirmBatchAction')
        ->assertSet('batchReceipt.successes', [$a->Order->ordem])
        ->assertSet('batchReceipt.refusals.0.order', $b->Order->ordem);

    expect($a->fresh()->operational_status)->toBe('FINISHED')
        ->and($b->fresh()->operational_status)->toBe('IN_PROGRESS');

    $c = workScreenState($user);
    Livewire::test(WorkIndex::class)
        ->set('selectedStateIds', [$c->id])
        ->set('batchAction', 'change_status')
        ->set('batchStatus', 'DEPOSIT_BLOCKED')
        ->call('prepareBatchPreview')
        ->assertDispatchedBrowserEvent('notification:toast', fn ($name, $data) => $data['title'] === 'Motivo obrigatório')
        ->set('batchReason', 'Depósito sem saldo')
        ->call('prepareBatchPreview')
        ->call('confirmBatchAction');

    expect($c->fresh()->operational_status)->toBe('DEPOSIT_BLOCKED')
        ->and(ClosureOrderEvent::query()->where('closure_order_state_id', $c->id)->where('type', 'STATUS_CHANGED')->exists())->toBeTrue();
});

it('shows the "Pegar atividades" link only when self assignment is enabled', function () {
    $user = workScreenUser();
    $this->actingAs($user);

    Livewire::test(WorkIndex::class)->assertDontSee('Pegar atividades');

    app(ClosureSettings::class)->setSelfAssignEnabled(true, $user);

    Livewire::test(WorkIndex::class)->assertSee('Pegar atividades');
});

it('does not toast on render', function () {
    $user = workScreenUser();
    workScreenState($user);
    $this->actingAs($user);

    Livewire::test(WorkIndex::class)->assertNotDispatchedBrowserEvent('notification:toast');
});
