<?php

use App\Http\Livewire\Closure\Dispatch\Dashboard;
use App\Models\Closure\{ClosureOrderEvent, ClosureOrderState, ClosurePriorityRequest};
use App\Models\{Company, Note, Operation, Order, Production, Service, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function dashUser(array $flags = []): User
{
    return User::factory()->create(array_merge(['superadm' => false, 'admin' => false, 'closure_manager' => false, 'closure_operator' => false], $flags));
}

function dashOrder(?User $user, string $status, string $fim, ?Company $company = null, ?Note $note = null): ClosureOrderState
{
    $service = Service::query()->firstOrCreate(
        ['folder' => 'encerramento'],
        ['service' => 'Encerramento', 'project' => true, 'construction' => true, 'icon' => 'ri-checkbox-circle-line']
    );
    $company ??= Company::query()->create(['name' => 'Parceira ' . fake()->unique()->numerify('####'), 'email' => fake()->unique()->safeEmail()]);
    $note ??= Note::query()->create(['note' => (string) fake()->unique()->numberBetween(1000000, 9999999), 'type_note' => 1]);
    $order = Order::query()->create(['note_id' => $note->id, 'ordem' => (string) fake()->unique()->numberBetween(100000000, 999999999), 'statusSist' => 'LIB CNPA', 'canceled' => false]);
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => $fim]);
    $production = Production::query()->firstOrCreate(['note_id' => $note->id, 'service_id' => $service->uuid], ['company_id' => $company->id]);

    return ClosureOrderState::query()->create([
        'order_id'                  => $order->id,
        'production_id'             => $production->id,
        'operational_status'        => $status,
        'assigned_user_id'          => $user?->id,
        'current_holder_type'       => $user ? 'INTERNAL_USER' : 'PARTNER_COMPANY',
        'current_holder_user_id'    => $user?->id,
        'current_holder_company_id' => $company->id,
        'entered_current_status_at' => now()->subDay(),
    ]);
}

it('rejects users without the dispatch gate', function () {
    $this->actingAs(dashUser(['closure_operator' => true]))->get(route('dispatch.dashboard', ['service' => \App\Services\Closure\ClosureServiceLocator::uuid()]))->assertForbidden();

    $this->actingAs(dashUser(['closure_operator' => true]));
    Livewire::test(Dashboard::class)->assertStatus(403);
});

it('renders the analytical dashboard for the dispatcher with the matrix numbers', function () {
    $dispatcher = dashUser(['closure_manager' => true]);
    $ana        = dashUser(['closure_operator' => true, 'name' => 'Ana Painel']);

    dashOrder($ana, 'IN_PROGRESS', now()->subMonths(3)->toDateString());
    dashOrder($ana, 'IN_PROGRESS', now()->subMonthNoOverflow()->toDateString());

    // A página completa depende do menu/banco de dados de layout; aqui valida-se o contrato da rota.
    $route = app('router')->getRoutes()->getByName('dispatch.dashboard');
    expect($route->getActionMethod())->toBe('dashboard')->and($route->middleware())->toContain('check.service.dispatch:services');

    $this->actingAs($dispatcher);
    Livewire::test(Dashboard::class)
        ->assertSee('Painel analítico do Encerramento')
        ->assertSee('Ana Painel')
        ->assertSeeHtml('id="dash-total">2<')
        ->assertViewHas('matrix', fn ($matrix) => $matrix['totals']['total'] === 2 && $matrix['totals']['types']['P'] === 1 && $matrix['totals']['types']['M'] === 1);
});

it('filters the order list when a matrix cell is clicked and by global filter', function () {
    $dispatcher = dashUser(['closure_manager' => true]);
    $ana        = dashUser(['closure_operator' => true]);
    $bruno      = dashUser(['closure_operator' => true]);

    dashOrder($ana, 'IN_PROGRESS', now()->subMonths(3)->toDateString());
    dashOrder($ana, 'PAUSED', now()->subMonths(3)->toDateString());
    dashOrder($bruno, 'PAUSED', now()->subMonthNoOverflow()->toDateString());

    $this->actingAs($dispatcher);
    Livewire::test(Dashboard::class)
        ->assertViewHas('orders', fn ($orders) => $orders->count() === 3)
        ->call('selectCell', (string) $ana->id, '', '')
        ->assertViewHas('orders', fn ($orders) => $orders->count() === 2)
        ->call('selectCell', '', '', 'PAUSED')
        ->assertViewHas('orders', fn ($orders) => $orders->count() === 2)
        ->call('selectCell', (string) $ana->id, 'P', 'PAUSED')
        ->assertViewHas('orders', fn ($orders) => $orders->count() === 1)
        ->call('clearCell')
        ->set('filterType', 'M')
        ->assertViewHas('orders', fn ($orders) => $orders->count() === 1 && $orders->first()->meta_type === 'M');
});

it('accepts and declines priority requests with a decision note', function () {
    Notification::fake();
    $dispatcher = dashUser(['closure_manager' => true]);
    $requester  = dashUser(['closure_operator' => true]);
    $stateA     = dashOrder($requester, 'IN_PROGRESS', now()->subMonths(3)->toDateString());
    $stateB     = dashOrder($requester, 'IN_PROGRESS', now()->subMonths(3)->toDateString());

    $accept  = ClosurePriorityRequest::query()->create(['note_id' => $stateA->Order->note_id, 'requested_by' => $requester->id, 'reason' => 'Urgente', 'status' => 'PENDING']);
    $decline = ClosurePriorityRequest::query()->create(['note_id' => $stateB->Order->note_id, 'requested_by' => $requester->id, 'reason' => 'Talvez', 'status' => 'PENDING']);

    $this->actingAs($dispatcher);
    Livewire::test(Dashboard::class)
        ->assertSee('Urgente')
        ->call('decidePriority', $accept->id, 'accept', 'Ok, atendido')
        ->call('decidePriority', $decline->id, 'decline', 'Sem capacidade')
        ->assertViewHas('priorities', fn ($p) => $p->isEmpty());

    expect($accept->fresh()->status)->toBe('ACCEPTED')
        ->and($accept->fresh()->decision_note)->toBe('Ok, atendido')
        ->and($stateA->fresh()->priority)->toBeTrue()
        ->and($decline->fresh()->status)->toBe('DECLINED')
        ->and($decline->fresh()->decision_note)->toBe('Sem capacidade')
        ->and($stateB->fresh()->priority)->toBeFalse();
});

it('prioritizes and clears a work directly', function () {
    Notification::fake();
    $dispatcher = dashUser(['closure_manager' => true]);
    $state      = dashOrder(dashUser(['closure_operator' => true]), 'IN_PROGRESS', now()->subMonths(3)->toDateString());

    $this->actingAs($dispatcher);
    Livewire::test(Dashboard::class)
        ->call('prioritizeNote', $state->Order->note_id)
        ->call('prioritizeNote', $state->Order->note_id, false);

    expect($state->fresh()->priority)->toBeFalse()
        ->and(ClosureOrderEvent::query()->where('type', 'PRIORITY_CHANGED')->count())->toBe(2)
        ->and(ClosurePriorityRequest::query()->where('note_id', $state->Order->note_id)->where('status', 'ACCEPTED')->count())->toBe(1);

    Livewire::test(Dashboard::class)->call('prioritizeNote', $state->Order->note_id);
    expect($state->fresh()->priority)->toBeTrue();
});

it('transfers the transferable orders of a work and issues a receipt', function () {
    Notification::fake();
    $dispatcher = dashUser(['closure_manager' => true]);
    $ana        = dashUser(['closure_operator' => true]);
    $company    = Company::query()->create(['name' => 'Interna', 'email' => 'int@example.com']);
    $bruno      = dashUser(['closure_operator' => true, 'company_id' => $company->id]);
    $note       = Note::query()->create(['note' => '9001', 'type_note' => 1]);
    $moving     = dashOrder($ana, 'IN_PROGRESS', now()->subMonths(3)->toDateString(), $company, $note);
    $waiting    = dashOrder($ana, 'WAITING_PARTNER', now()->subMonths(3)->toDateString(), $company, $note);

    $this->actingAs($dispatcher);
    Livewire::test(Dashboard::class)
        ->call('openTransfer', $note->id)
        ->set('transferUserId', (string) $bruno->id)
        ->call('transfer')
        ->assertSet('feedbackType', 'warning') // sem motivo
        ->set('transferReason', 'Férias da Ana')
        ->call('transfer')
        ->assertSet('transferNoteId', null)
        ->assertSee('Recibo da transferência')
        ->assertViewHas('matrix', fn ($m) => collect($m['rows'])->firstWhere('user_id', $bruno->id)['total'] === 1);

    expect($moving->fresh()->assigned_user_id)->toBe($bruno->id)
        ->and($moving->fresh()->operational_status)->toBe('ASSIGNED')
        ->and($waiting->fresh()->assigned_user_id)->toBe($ana->id)
        ->and(ClosureOrderEvent::query()->where('type', 'TRANSFERRED')->where('order_id', $moving->order_id)->count())->toBe(1);
});
