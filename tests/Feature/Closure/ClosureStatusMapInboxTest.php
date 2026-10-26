<?php

use App\Http\Livewire\Closure\Inbox\Index as InboxIndex;
use App\Http\Livewire\Closure\Settings\StatusUserMap;
use App\Models\{Bancoupdate, Company, Service, ServiceUser, User};
use App\Models\Closure\{ClosureIssue, ClosureStatusUserRoute};
use App\Services\Closure\{ClosureArea, ClosureStatusUserRouteService};
use App\Services\Closure\Queries\ClosureInboxQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

// Fase 4/5: mapa de status por serviço, caixa "Em acompanhamento" (visão) e acesso dentro das atividades.

uses(RefreshDatabase::class);

beforeEach(function () {
    \Carbon\Carbon::setTestNow('2026-10-16 10:00:00');
    \Illuminate\Support\Facades\Cache::flush();
    Service::query()->firstOrCreate(['folder' => 'encerramento'], ['service' => 'Encerramento', 'project' => true, 'construction' => true, 'icon' => 'ri-checkbox-circle-line']);
});

afterEach(fn () => \Carbon\Carbon::setTestNow());

/** Ordem elegível do Encerramento (operação 0020 confirmada), sem despacho, com o status usuário informado. */
function inboxOrder(string $statusUser, string $fimReal = '2026-07-10'): \App\Models\Order
{
    $note  = \App\Models\Note::query()->create(['note' => (string) fake()->unique()->numberBetween(1000000, 9999999), 'type_note' => 1, 'dt_status' => now()]);
    $order = \App\Models\Order::query()->create(['note_id' => $note->id, 'ordem' => (string) fake()->unique()->numberBetween(100000000, 999999999), 'statusSist' => 'LIB CNPA', 'canceled' => false, 'statusUser' => $statusUser]);

    \App\Models\Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => $fimReal]);

    return $order;
}

function inboxIds(string $area, array $filters = []): array
{
    return app(ClosureInboxQuery::class)->accompanying($area, $filters, 50, 1)->getCollection()->pluck('id')->all();
}

function mapActor(array $flags = ['closure_manager' => true]): User
{
    // As páginas completas (layout) leem o último update do banco e contadores que usam a empresa do usuário.
    if (!Bancoupdate::query()->exists()) {
        Bancoupdate::create(['last_update' => now(), 'error' => 0, 'inserts' => 0, 'updates' => 0, 'info' => 'teste']);
    }

    $company = Company::query()->create(['name' => 'Mapa ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()]);

    return User::factory()->create(array_merge(['superadm' => false, 'admin' => false, 'closure_manager' => false, 'closure_operator' => false, 'company_id' => $company->id], $flags));
}

function mapService(string $folder, User $user, array $columns = ['service' => true, 'dispatch' => false]): Service
{
    $service = Service::query()->firstOrCreate(['folder' => $folder], ['service' => ucfirst($folder), 'project' => true, 'construction' => true, 'icon' => 'ri-folder-line']);
    ServiceUser::query()->create(array_merge(['user_id' => $user->id, 'service_id' => $service->uuid], $columns));

    return $service;
}

it('maps each status prefix to a single service and refuses a prefix owned by another', function () {
    $actor   = mapActor();
    $service = app(ClosureStatusUserRouteService::class);

    // 'jand inve bloq' é normalizado para o prefixo de 2 tokens em maiúsculas e conta como o mesmo 'JAND INVE'.
    expect($service->sync(ClosureArea::MEASUREMENT, ['JAND INVE', 'jand inve bloq', 'ENC PEND'], $actor))->toBe(['added' => 2, 'removed' => 0])
        ->and($service->prefixesFor(ClosureArea::MEASUREMENT))->toBe(['ENC PEND', 'JAND INVE']);

    expect(fn () => $service->sync(ClosureArea::SUPERVISION, ['ENC PEND'], $actor))
        ->toThrow(InvalidArgumentException::class, 'já pertence a Medição');

    expect(ClosureStatusUserRoute::query()->where('area', ClosureArea::SUPERVISION)->count())->toBe(0);
});

it('replaces the area selection, adding and removing prefixes', function () {
    $actor   = mapActor();
    $service = app(ClosureStatusUserRouteService::class);

    $service->sync(ClosureArea::SUPERVISION, ['AAA BBB', 'CCC DDD'], $actor);
    $result = $service->sync(ClosureArea::SUPERVISION, ['CCC DDD', 'EEE FFF'], $actor);

    expect($result)->toBe(['added' => 1, 'removed' => 1])
        ->and($service->prefixesFor(ClosureArea::SUPERVISION))->toBe(['CCC DDD', 'EEE FFF'])
        ->and($service->map())->toBe(['CCC DDD' => 'SUPERVISION', 'EEE FFF' => 'SUPERVISION']);

    // liberar um prefixo permite outra área usá-lo
    $service->sync(ClosureArea::SUPERVISION, [], $actor);
    $service->sync(ClosureArea::MEASUREMENT, ['CCC DDD'], $actor);

    expect($service->map())->toBe(['CCC DDD' => 'MEASUREMENT']);
});

it('refuses areas without an inbox', function () {
    expect(fn () => app(ClosureStatusUserRouteService::class)->sync(ClosureArea::PARTNER, ['AAA BBB'], mapActor()))
        ->toThrow(InvalidArgumentException::class);
});

it('lists every status prefix of the SAP orders (not only the Encerramento ones) with counts and owners', function () {
    \Illuminate\Support\Facades\Cache::forget('closure:status-user-catalog:v1');

    $f = closureScreenFixture();
    $f['order']->update(['statusUser' => 'JAND INVE BLOQ']);
    // Ordem fora do Encerramento, em outro status
    \App\Models\Order::query()->create(['note_id' => $f['note']->id, 'ordem' => '499000000001', 'statusSist' => 'LIB', 'statusUser' => 'ZZZZ OUTRO X']);
    app(ClosureStatusUserRouteService::class)->sync(ClosureArea::MEASUREMENT, ['JAND INVE', 'SEM ORDEM'], $f['dispatcher']);

    $catalog = collect(app(ClosureStatusUserRouteService::class)->catalog())->keyBy('prefix');

    expect($catalog['JAND INVE'])->toMatchArray(['count' => 1, 'total' => 1, 'area' => 'MEASUREMENT'])
        ->and($catalog['ZZZZ OUTRO'])->toMatchArray(['count' => 0, 'total' => 1, 'area' => null])
        // prefixo mapeado sem Ordens agora continua na lista, com 0, para poder ser desmarcado
        ->and($catalog['SEM ORDEM'])->toMatchArray(['count' => 0, 'total' => 0, 'area' => 'MEASUREMENT']);
});

it('shows in the inbox every eligible order (dispatched or not) in a mapped status, and drops it when the status changes', function () {
    $mine  = inboxOrder('JAND INVE BLOQ');
    $other = inboxOrder('OUTRO STAT');

    $routes = app(ClosureStatusUserRouteService::class);
    $admin  = mapActor();

    // Sem mapa a caixa fica vazia (nunca "tudo").
    expect(inboxIds(ClosureArea::MEASUREMENT))->toBe([]);

    $routes->sync(ClosureArea::MEASUREMENT, ['JAND INVE'], $admin);

    expect(inboxIds(ClosureArea::MEASUREMENT))->toBe([$mine->id])
        // outra área não enxerga a Ordem
        ->and(inboxIds(ClosureArea::SUPERVISION))->toBe([])
        ->and(app(ClosureInboxQuery::class)->accompanyingCounts(ClosureArea::MEASUREMENT))->toBe(['JAND INVE' => 1]);

    // O status muda no SAP: a Ordem sai da caixa sozinha (o cache do conjunto dura 5 minutos).
    $mine->update(['statusUser' => 'ENC PEND']);
    \Illuminate\Support\Facades\Cache::flush();

    expect(inboxIds(ClosureArea::MEASUREMENT))->toBe([])
        ->and(app(ClosureInboxQuery::class)->accompanyingCounts(ClosureArea::MEASUREMENT))->toBe(['JAND INVE' => 0]);
});

it('does not match a prefix that merely starts with the same letters', function () {
    inboxOrder('JAND INVESTIGA X');
    app(ClosureStatusUserRouteService::class)->sync(ClosureArea::MEASUREMENT, ['JAND INVE'], mapActor());

    expect(inboxIds(ClosureArea::MEASUREMENT))->toBe([]);
});

it('lists dispatched issues only for the destination area', function () {
    $f        = closureScreenFixture();
    $taxonomy = closureIssueTaxonomy();

    foreach ([[ClosureArea::MEASUREMENT, ClosureIssue::STATUS_REQUESTED], [ClosureArea::SUPERVISION, ClosureIssue::STATUS_REQUESTED], [ClosureArea::MEASUREMENT, ClosureIssue::STATUS_RESOLVED]] as [$area, $status]) {
        ClosureIssue::query()->create([
            'order_id'                     => $f['order']->id,
            'closure_order_state_id'       => $f['state']->id,
            'origin_area'                  => ClosureArea::CLOSURE,
            'closure_issue_category_id'    => $taxonomy['category']->id,
            'closure_issue_subcategory_id' => $taxonomy['subcategory']->id,
            'destination_area'             => $area,
            'status'                       => $status,
            'opened_by'                    => $f['dispatcher']->id,
            'description'                  => 'x',
            'opened_at'                    => now(),
        ]);
    }

    $query = app(ClosureInboxQuery::class);

    expect($query->dispatched(ClosureArea::MEASUREMENT, 'open')->count())->toBe(1)
        ->and($query->dispatched(ClosureArea::MEASUREMENT, 'done')->count())->toBe(1)
        ->and($query->dispatched(ClosureArea::MEASUREMENT)->count())->toBe(2)
        ->and($query->dispatched(ClosureArea::SUPERVISION, 'open')->count())->toBe(1);
});

it('lets only closure managers open the status map screen', function () {
    $manager = mapActor();
    $other   = mapActor(['closure_operator' => true]);

    $this->actingAs($manager)->get(route('closure.status_map'))->assertOk()->assertSee('Mapa de status por serviço');
    $this->actingAs($other)->get(route('closure.status_map'))->assertForbidden();

    Livewire::actingAs($other)->test(StatusUserMap::class)->assertForbidden();
});

it('saves the map from the config screen and reports a conflict instead of stealing a status', function () {
    $manager = mapActor();
    app(ClosureStatusUserRouteService::class)->sync(ClosureArea::SUPERVISION, ['AAA BBB'], $manager);

    Livewire::actingAs($manager)->test(StatusUserMap::class)
        ->set('selected.MEASUREMENT', ['CCC DDD'])
        ->call('save', 'MEASUREMENT')
        ->assertDispatchedBrowserEvent('notification:toast');

    expect(app(ClosureStatusUserRouteService::class)->prefixesFor(ClosureArea::MEASUREMENT))->toBe(['CCC DDD']);

    Livewire::actingAs($manager)->test(StatusUserMap::class)
        ->set('selected.MEASUREMENT', ['CCC DDD', 'AAA BBB'])
        ->call('save', 'MEASUREMENT')
        ->assertDispatchedBrowserEvent('notification:toast', ['title' => 'Não foi possível salvar', 'text' => 'O status "AAA BBB" já pertence a Fiscalização. Libere-o lá antes.', 'icon' => 'error']);

    expect(app(ClosureStatusUserRouteService::class)->prefixesFor(ClosureArea::MEASUREMENT))->toBe(['CCC DDD']);
});

it('opens the inbox inside the activity only for users linked to it', function () {
    $linked = mapActor(['closure_manager' => false]);
    $pay    = mapService('pagamento', $linked);
    $fisc   = Service::query()->firstOrCreate(['folder' => 'fiscalizacao'], ['service' => 'Fiscalizacao', 'project' => true, 'construction' => true, 'icon' => 'ri-folder-line']);

    $this->actingAs($linked)->get(route('services.closure_inbox', ['service' => $pay->uuid]))
        ->assertOk()->assertSee('Caixa do Encerramento — Medição');

    // sem vínculo com a Fiscalização: o middleware do serviço já recusa
    $this->actingAs($linked)->get(route('services.closure_inbox', ['service' => $fisc->uuid]))->assertForbidden();

    // serviço sem caixa (Desenho) => 404
    $drafter = mapActor(['closure_manager' => false]);
    $desenho = mapService('desenho', $drafter);
    $this->actingAs($drafter)->get(route('services.closure_inbox', ['service' => $desenho->uuid]))->assertNotFound();

    // despachante do serviço usa a rota de despacho
    $dispatcher = mapActor(['closure_manager' => false]);
    $fiscDisp   = mapService('fiscalizacao', $dispatcher, ['service' => false, 'dispatch' => true]);
    $this->actingAs($dispatcher)->get(route('dispatch.closure_inbox', ['service' => $fiscDisp->uuid]))
        ->assertOk()->assertSee('Caixa do Encerramento — Fiscalização');
});

it('renders the accompanying list with undispatched and dispatched orders, status and since when', function () {
    $free   = inboxOrder('JAND INVE BLOQ');
    $taken  = inboxOrder('JAND INVE LIBE', '2026-09-10');
    $other  = inboxOrder('FORA DAQUI');
    $admin  = mapActor();
    $holder = allUserForInbox();

    app(\App\Services\Closure\ClosureDispatchService::class)->dispatchOrders([$taken->id], $holder->Company, $holder, $admin);
    \App\Models\Closure\ClosureOrderState::query()->where('order_id', $taken->id)->update(['status_user_seen' => 'JAND INVE', 'status_user_changed_at' => now()->subDays(3), 'status_user_estimated' => false]);
    app(ClosureStatusUserRouteService::class)->sync(ClosureArea::MEASUREMENT, ['JAND INVE'], $admin);

    Livewire::actingAs($admin)->test(InboxIndex::class, ['area' => ClosureArea::MEASUREMENT])
        ->assertSee($free->ordem)
        ->assertSee($taken->ordem)
        ->assertDontSee($other->ordem)
        ->assertSee('Não despachada')
        ->assertSee('há 3 dias')
        ->assertSee('sem registro')
        ->assertSee('Só visão')
        ->call('togglePrefix', 'JAND INVE')
        ->assertSee($free->ordem)
        ->set('search', $taken->ordem)
        ->assertSee($taken->ordem)
        ->assertDontSee($free->ordem);
});

function allUserForInbox(): User
{
    $company = Company::query()->create(['name' => 'Pilha ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()]);

    return User::factory()->create(['closure_operator' => true, 'closure_manager' => false, 'admin' => false, 'superadm' => false, 'company_id' => $company->id]);
}

it('explains an empty inbox and offers the configuration only to managers', function () {
    $manager = mapActor();
    $linked  = mapActor(['closure_manager' => false]);
    mapService('pagamento', $linked);

    Livewire::actingAs($manager)->test(InboxIndex::class, ['area' => ClosureArea::MEASUREMENT])
        ->assertSee('ainda não tem status configurados')
        ->assertSee('Configurar agora');

    Livewire::actingAs($linked)->test(InboxIndex::class, ['area' => ClosureArea::MEASUREMENT])
        ->assertSee('ainda não tem status configurados')
        ->assertDontSee('Configurar agora')
        ->assertSee('Peça à gestão');
});

it('refuses the inbox component without the area gate or for an area without inbox', function () {
    $outsider = mapActor(['closure_manager' => false]);

    Livewire::actingAs($outsider)->test(InboxIndex::class, ['area' => ClosureArea::MEASUREMENT])->assertForbidden();
    Livewire::actingAs(mapActor())->test(InboxIndex::class, ['area' => ClosureArea::PARTNER])->assertNotFound();
});

it('shows the inbox shortcut in the menus of Medição and Fiscalização only to linked users', function () {
    $linked = mapActor(['closure_manager' => false]);
    $pay    = mapService('pagamento', $linked);

    $this->actingAs($linked);
    $html = view('closure.partials.inbox-menu-item', ['service' => $pay, 'inboxRoute' => 'services.closure_inbox'])->render();

    expect($html)->toContain('EM ACOMPANHAMENTO')->toContain('DESPACHADAS PARA RESOLVER')
        ->toContain(route('services.closure_inbox', ['service' => $pay->uuid]))
        ->toContain(route('services.closure_inbox', ['service' => $pay->uuid, 'tab' => 'dispatched']));

    // vínculo só com Desenho: o serviço não tem caixa => nada no menu
    $desenhista = mapActor(['closure_manager' => false]);
    $desenho    = mapService('desenho', $desenhista);
    $this->actingAs($desenhista);

    expect(view('closure.partials.inbox-menu-item', ['service' => $desenho, 'inboxRoute' => 'services.closure_inbox'])->render())->not->toContain('EM ACOMPANHAMENTO');

    // vínculo com outra área não abre a caixa desta
    $fisc = Service::query()->firstOrCreate(['folder' => 'fiscalizacao'], ['service' => 'Fiscalizacao', 'project' => true, 'construction' => true, 'icon' => 'x']);
    $this->actingAs($linked);

    expect(view('closure.partials.inbox-menu-item', ['service' => $fisc, 'inboxRoute' => 'services.closure_inbox'])->render())->not->toContain('EM ACOMPANHAMENTO');
});

it('filters the accompanying inbox by responsible and by status', function () {
    $mine   = inboxOrder('JAND INVE BLOQ');
    $free   = inboxOrder('JAND PPAG X');
    $admin  = mapActor();
    $holder = allUserForInbox();

    app(\App\Services\Closure\ClosureDispatchService::class)->dispatchOrders([$mine->id], $holder->Company, $holder, $admin);
    app(ClosureStatusUserRouteService::class)->sync(ClosureArea::MEASUREMENT, ['JAND INVE', 'JAND PPAG'], $admin);

    expect(inboxIds(ClosureArea::MEASUREMENT))->toEqualCanonicalizing([$mine->id, $free->id])
        ->and(inboxIds(ClosureArea::MEASUREMENT, ['holderUserId' => (string) $holder->id]))->toBe([$mine->id])
        ->and(inboxIds(ClosureArea::MEASUREMENT, ['prefixes' => ['jand ppag']]))->toBe([$free->id])
        // um status que não é da caixa nunca amplia o recorte
        ->and(inboxIds(ClosureArea::MEASUREMENT, ['prefixes' => ['FORA DAQUI']]))->toEqualCanonicalizing([$mine->id, $free->id]);

    $options = app(ClosureInboxQuery::class)->filterOptions(ClosureArea::MEASUREMENT);

    expect($options['users']->pluck('id')->all())->toBe([$holder->id]);
});

it('shows the inbox filters on the screen', function () {
    $f = closureScreenFixture();
    app(ClosureStatusUserRouteService::class)->sync(ClosureArea::MEASUREMENT, ['JAND INVE'], $f['dispatcher']);

    Livewire::actingAs($f['dispatcher'])->test(InboxIndex::class, ['area' => ClosureArea::MEASUREMENT])
        ->assertSee('Status usuário')->assertSee('Responsável (Encerramento)')->assertSee('Empresa (informe)')
        ->set('holderUserId', 'x')->assertSee('Limpar filtros')
        ->call('clearFilters')->assertSet('holderUserId', null);
});

it('counts the two menu badges: orders in accompaniment (blue) and dispatched issues to resolve (red)', function () {
    $order = inboxOrder('JAND INVE BLOQ');
    inboxOrder('JAND INVE LIBE', '2026-09-10');
    $admin = mapActor();
    app(ClosureStatusUserRouteService::class)->sync(ClosureArea::MEASUREMENT, ['JAND INVE'], $admin);

    $f        = closureScreenFixture();
    $taxonomy = closureIssueTaxonomy();

    foreach ([ClosureIssue::STATUS_REQUESTED, ClosureIssue::STATUS_RESOLVED] as $status) {
        ClosureIssue::query()->create([
            'order_id'                  => $f['order']->id, 'closure_order_state_id' => $f['state']->id, 'origin_area' => ClosureArea::CLOSURE, 'destination_area' => ClosureArea::MEASUREMENT,
            'closure_issue_category_id' => $taxonomy['category']->id, 'closure_issue_subcategory_id' => $taxonomy['subcategory']->id,
            'status'                    => $status, 'opened_by' => $admin->id, 'description' => 'x', 'opened_at' => now(),
        ]);
    }

    Livewire::actingAs($admin)->test(\App\Http\Livewire\Components\Count\ClosureInboxCount::class, ['area' => ClosureArea::MEASUREMENT, 'kind' => 'accompanying'])
        ->assertSeeHtml('bg-primary')->assertSee('2');

    Livewire::actingAs($admin)->test(\App\Http\Livewire\Components\Count\ClosureInboxCount::class, ['area' => ClosureArea::MEASUREMENT, 'kind' => 'dispatched'])
        ->assertSeeHtml('bg-danger')->assertSee('1');

    // sem acesso à caixa, nenhum selo
    Livewire::actingAs(mapActor(['closure_manager' => false]))->test(\App\Http\Livewire\Components\Count\ClosureInboxCount::class, ['area' => ClosureArea::MEASUREMENT, 'kind' => 'dispatched'])
        ->assertDontSeeHtml('badge');

    // o link do menu abre direto na aba das despachadas
    Livewire::actingAs($admin)->test(InboxIndex::class, ['area' => ClosureArea::MEASUREMENT, 'tab' => 'dispatched'])->assertSet('tab', 'dispatched')->assertSee('Pendências despachadas');
    Livewire::actingAs($admin)->test(InboxIndex::class, ['area' => ClosureArea::MEASUREMENT, 'tab' => 'qualquer'])->assertSet('tab', 'accompanying');
});
