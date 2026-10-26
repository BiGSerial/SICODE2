<?php

use App\Http\Livewire\Closure\Dispatch\DispatchModal;
use App\Models\Closure\ClosureOrderState;
use App\Models\{Company, Note, Operation, Order, Service, ServiceUser, User};
use App\Services\Closure\{ClosureDispatchDirectory, ClosureDispatchService, ClosureMultiDispatchResult, ClosureNotifier};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-10-16 10:00:00');
    ClosureDispatchDirectory::forget();

    $this->service = Service::query()->firstOrCreate(
        ['folder' => ClosureDispatchService::SERVICE_FOLDER],
        ['service' => 'Encerramento', 'project' => true, 'construction' => true, 'icon' => 'ri-checkbox-circle-line']
    );
});

afterEach(fn () => Carbon::setTestNow());

function modalManager(): User
{
    return User::factory()->create(['closure_manager' => true, 'closure_operator' => false, 'admin' => false, 'superadm' => false]);
}

function modalOrder(string $fimReal, array $attrs = []): Order
{
    $note  = Note::query()->create(['note' => (string) fake()->unique()->numberBetween(1000000, 9999999), 'type_note' => 1, 'dt_status' => now()]);
    $order = Order::query()->create(array_merge([
        'note_id'    => $note->id,
        'ordem'      => (string) fake()->unique()->numberBetween(100000000, 999999999),
        'statusSist' => 'LIB CNPA',
        'canceled'   => false,
    ], $attrs));

    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => $fimReal]);

    return $order;
}

/** Empresa habilitada no serviço Encerramento, com um usuário habilitado. */
function modalCompany(Service $service): array
{
    $company = Company::query()->create(['name' => 'Parceira ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()]);
    $user    = User::factory()->create(['company_id' => $company->id]);
    ServiceUser::create(['user_id' => $user->id, 'service_id' => $service->uuid, 'service' => true, 'dispatch' => false]);

    return [$company, $user];
}

/** Chama um método do modal e devolve o retorno (efeito `returns` do Livewire). */
function modalCall($component, string $method, array $args)
{
    $component->call($method, ...$args);

    return collect($component->payload['effects']['returns'] ?? [])->last();
}

it('embeds only the companies and users enabled in the Closure service as JSON in the page', function () {
    [$company, $user] = modalCompany($this->service);
    $other            = Company::query()->create(['name' => 'Sem acesso', 'email' => fake()->unique()->safeEmail()]);
    User::factory()->create(['company_id' => $other->id, 'name' => 'Usuario Desabilitado']);
    $disabled = User::factory()->create(['company_id' => $company->id, 'name' => 'Colega Sem Servico']);
    ServiceUser::create(['user_id' => $disabled->id, 'service_id' => $this->service->uuid, 'service' => false, 'dispatch' => false]);

    $directory = app(ClosureDispatchDirectory::class)->all();

    expect($directory)->toHaveCount(1)
        ->and($directory[0]['id'])->toBe((string) $company->id)
        ->and(collect($directory[0]['users'])->pluck('id')->all())->toBe([(string) $user->id]);

    Livewire::actingAs(modalManager())->test(DispatchModal::class)
        ->assertSeeHtml('type="application/json" x-ref="directory"')
        ->assertSeeHtml($company->name)
        ->assertSeeHtml($user->name)
        ->assertDontSeeHtml('Sem acesso')
        ->assertDontSeeHtml('Usuario Desabilitado')
        ->assertDontSeeHtml('Colega Sem Servico');
});

it('renders the client-side modal shell with no server-driven rows or wire:click on open', function () {
    Livewire::actingAs(modalManager())->test(DispatchModal::class)
        ->assertSeeHtml('x-data="closureDispatchModal()"')
        ->assertSeeHtml('closure-dispatch-open.window')
        ->assertSeeHtml('Compet.')
        ->assertDontSeeHtml('wire:click')
        ->assertDontSeeHtml('wire:model');
});

it('previews eligibility with competence and does not re-render the component', function () {
    $a        = modalOrder('2026-09-10');
    $canceled = modalOrder('2026-09-12', ['canceled' => true]);

    $component = Livewire::actingAs(modalManager())->test(DispatchModal::class);
    $res       = modalCall($component, 'preview', [[$a->id, $canceled->id, 'x', -3], null, null]);

    expect($res['error'])->toBeNull()
        ->and($res['rows'])->toHaveCount(2)
        ->and(collect($res['rows'])->firstWhere('id', $a->id))->toMatchArray(['eligible' => true, 'type' => 'M', 'comp' => '10/2026', 'ordem' => $a->ordem])
        ->and(collect($res['rows'])->firstWhere('id', $canceled->id))->toMatchArray(['eligible' => false, 'reason' => 'Ordem cancelada.']);

    expect($component->payload['effects']['html'] ?? null)->toBeNull();
});

it('previews an already dispatched Order as ineligible whatever the chosen company, naming who holds it', function () {
    [$company, $user] = modalCompany($this->service);
    [$other]          = modalCompany($this->service);
    $a                = modalOrder('2026-09-10');

    app(ClosureDispatchService::class)->dispatchOrders([$a->id], $company, null, modalManager());

    $component = Livewire::actingAs(modalManager())->test(DispatchModal::class);

    foreach ([null, (string) $other->id, (string) $company->id] as $chosen) {
        $row = modalCall($component, 'preview', [[$a->id], $chosen, null])['rows'][0];

        expect($row['eligible'])->toBeFalse()
            ->and($row['reason'])->toContain('já foi despachada')->toContain($company->name)->toContain('Transferência');
    }
});

it('confirms and dispatches only the eligible orders, with receipt, one toast and one notification per assigned order', function () {
    [$company, $assignee] = modalCompany($this->service);
    $a                    = modalOrder('2026-09-10');
    $b                    = modalOrder('2026-07-10');
    $canceled             = modalOrder('2026-09-12', ['canceled' => true]);

    $notifier = Mockery::mock(ClosureNotifier::class);
    $notifier->shouldReceive('orderAssigned')->twice();
    app()->instance(ClosureNotifier::class, $notifier);

    $component = Livewire::actingAs(modalManager())->test(DispatchModal::class);
    $res       = modalCall($component, 'confirm', [[$a->id, $b->id, $canceled->id], (string) $company->id, (string) $assignee->id, true]);

    $component->assertDispatchedBrowserEvent('notification:toast')->assertEmitted('closureOrdersDispatched');

    expect($res['processed'])->toBeTrue()
        ->and($res['receipt'])->toHaveCount(2)
        ->and(ClosureOrderState::query()->count())->toBe(2)
        ->and(ClosureOrderState::query()->where('assigned_user_id', $assignee->id)->count())->toBe(2);
});

it('blocks confirm while there are refused orders unless only-eligible is set', function () {
    [$company] = modalCompany($this->service);
    $ok        = modalOrder('2026-09-10');
    $canceled  = modalOrder('2026-09-12', ['canceled' => true]);

    $component = Livewire::actingAs(modalManager())->test(DispatchModal::class);
    $res       = modalCall($component, 'confirm', [[$ok->id, $canceled->id], (string) $company->id, null, false]);

    expect($res['processed'])->toBeFalse()
        ->and($res['error'])->toContain('Despachar só as elegíveis')
        ->and(ClosureOrderState::query()->count())->toBe(0);
});

it('revalidates everything on the server: tampered ids, company and user are refused', function () {
    [$company, $user]    = modalCompany($this->service);
    [$other, $otherUser] = modalCompany($this->service);
    $stranger            = Company::query()->create(['name' => 'Sem acesso', 'email' => fake()->unique()->safeEmail()]);
    $strangerUser        = User::factory()->create(['company_id' => $stranger->id]);
    $a                   = modalOrder('2026-09-10');
    $ghostId             = $a->id + 9999;

    $component = Livewire::actingAs(modalManager())->test(DispatchModal::class);

    // Empresa não habilitada no serviço.
    expect(modalCall($component, 'confirm', [[$a->id], (string) $stranger->id, null, false])['error'])->toContain('não está habilitada');
    // Empresa inexistente / vazia.
    expect(modalCall($component, 'confirm', [[$a->id], '999999', null, false])['processed'])->toBeFalse()
        ->and(modalCall($component, 'confirm', [[$a->id], '', null, false])['error'])->toContain('Informe a empresa');
    // Usuário de outra empresa, e usuário sem o serviço.
    expect(modalCall($component, 'confirm', [[$a->id], (string) $company->id, (string) $otherUser->id, false])['error'])->toContain('não está habilitado')
        ->and(modalCall($component, 'confirm', [[$a->id], (string) $stranger->id, (string) $strangerUser->id, false])['processed'])->toBeFalse();
    // Ids adulterados: Ordem inexistente, vazio, lixo.
    expect(modalCall($component, 'confirm', [[$a->id, $ghostId], (string) $company->id, null, true])['processed'])->toBeFalse()
        ->and(modalCall($component, 'confirm', [[], (string) $company->id, null, true])['error'])->toContain('Nenhuma Ordem')
        ->and(modalCall($component, 'confirm', [['abc', 0, -1], (string) $company->id, null, true])['error'])->toContain('Nenhuma Ordem');
    // Acima do limite.
    expect(modalCall($component, 'confirm', [range(1, DispatchModal::MAX_ORDERS + 1), (string) $company->id, null, true])['error'])->toContain('no máximo');

    expect(ClosureOrderState::query()->count())->toBe(0);
});

it('refuses ineligible orders even when the client claims they are eligible', function () {
    [$company] = modalCompany($this->service);
    $canceled  = modalOrder('2026-09-12', ['canceled' => true]);
    $ended     = modalOrder('2026-09-12', ['statusSist' => 'ENTE X']);

    $component = Livewire::actingAs(modalManager())->test(DispatchModal::class);
    $res       = modalCall($component, 'confirm', [[$canceled->id, $ended->id], (string) $company->id, null, true]);

    expect($res['processed'])->toBeFalse()->and($res['error'])->toContain('Nenhuma Ordem elegível')
        ->and(ClosureOrderState::query()->count())->toBe(0);
});

it('reports partial success when the service refuses an order that passed the preview', function () {
    [$company] = modalCompany($this->service);
    $a         = modalOrder('2026-09-10');
    $b         = modalOrder('2026-07-10');

    $result = new ClosureMultiDispatchResult([
        $a->id => ['order_id' => $a->id, 'note_id' => $a->note_id, 'status' => ClosureMultiDispatchResult::STATUS_DISPATCHED, 'message' => null, 'meta_type' => 'M'],
        $b->id => ['order_id' => $b->id, 'note_id' => $b->note_id, 'status' => ClosureMultiDispatchResult::STATUS_REFUSED, 'message' => 'Recusa do service.', 'meta_type' => 'P'],
    ], []);

    $service = Mockery::mock(ClosureDispatchService::class);
    $service->shouldReceive('dispatchOrders')->once()->andReturn($result);
    app()->instance(ClosureDispatchService::class, $service);

    $component = Livewire::actingAs(modalManager())->test(DispatchModal::class);
    $res       = modalCall($component, 'confirm', [[$a->id, $b->id], (string) $company->id, null, false]);

    $component->assertEmitted('closureOrdersDispatched', [$b->id])->assertDispatchedBrowserEvent('notification:toast');

    expect($res['processed'])->toBeTrue()
        ->and(collect($res['receipt'])->firstWhere('order_id', $b->id)['message'])->toBe('Recusa do service.');
});

it('does not notify when sending to the company stack', function () {
    [$company] = modalCompany($this->service);
    $a         = modalOrder('2026-09-10');

    $notifier = Mockery::mock(ClosureNotifier::class);
    $notifier->shouldReceive('orderAssigned')->never();
    app()->instance(ClosureNotifier::class, $notifier);

    $component = Livewire::actingAs(modalManager())->test(DispatchModal::class);

    expect(modalCall($component, 'confirm', [[$a->id], (string) $company->id, null, false])['processed'])->toBeTrue()
        ->and(ClosureOrderState::query()->count())->toBe(1);
});

it('forbids users without the dispatch gate', function () {
    $user = User::factory()->create(['closure_manager' => false, 'closure_operator' => true, 'admin' => false, 'superadm' => false]);

    Livewire::actingAs($user)->test(DispatchModal::class)->assertStatus(403);
});
