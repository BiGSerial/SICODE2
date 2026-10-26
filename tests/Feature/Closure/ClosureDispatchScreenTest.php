<?php

use App\Http\Livewire\Closure\Dispatch\Index;
use App\Models\Closure\ClosureOrderState;
use App\Models\{Company, Note, Operation, Order, Partial, Service, User, WorkReport};
use App\Services\Closure\{ClosureDispatchService, ClosureEligibleOrders, ClosureMetaClassifier, ClosureSettings};
use App\Services\Closure\Queries\ClosureDispatchListQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache};
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
        ->assertSeeHtml('type-dot badge bg-danger')
        ->assertSeeHtml('type-dot badge bg-success')
        ->assertSeeHtml('type-dot badge bg-primary')
        ->assertSee('Passivo')
        ->assertSee('Meta futura');
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

it('has no floating bulk bar and puts the batch button in the Orders card header', function () {
    $order = screenOrder('2026-09-10');

    $component = Livewire::actingAs(screenManager())->test(Index::class)
        ->assertDontSee('Ver prévia do despacho')
        ->assertDontSeeHtml('bulk-bar')
        ->assertSeeHtml('id="closure-dispatch-open"')
        ->assertSeeHtml('Despachar selecionadas (')
        ->assertSeeHtml('x-show="selected.length > 0"')
        ->assertSeeHtml(':disabled="selected.length === 0"');

    $html = $component->lastRenderedDom;

    // O botão de lote fica no cabeçalho do card "Ordens", depois de "Selecionar todas filtradas".
    expect(strpos($html, 'Selecionar todas filtradas'))->toBeLessThan(strpos($html, 'Despachar selecionadas ('))
        ->and(strpos($html, 'Despachar selecionadas ('))->toBeLessThan(strpos($html, '<table'))
        ->and(substr_count($html, 'id="closure-dispatch-open"'))->toBe(1);
});

it('exposes per-row data attributes for the client-side modal and opens it without a server round-trip', function () {
    $order = screenOrder('2026-09-10');

    Livewire::actingAs(screenManager())->test(Index::class)
        ->assertSeeHtml('data-order-row="' . $order->id . '"')
        ->assertSeeHtml('data-ordem="' . $order->ordem . '"')
        ->assertSeeHtml('data-type="M"')
        ->assertSeeHtml('data-comp="10/2026"')
        ->assertSeeHtml('closure-dispatch-open')
        ->assertDontSeeHtml("emitTo('closure.dispatch.dispatch-modal'")
        ->assertDontSeeHtml('openForOrders');
});

it('keeps only refused orders selected after the modal dispatch and counts by type', function () {
    $a = screenOrder('2026-09-10');
    $b = screenOrder('2026-07-10');

    $component = Livewire::actingAs(screenManager())->test(Index::class)
        ->call('toggleOrder', $a->id)
        ->call('toggleOrder', $b->id)
        ->emit('closureOrdersDispatched', [$b->id])
        ->assertSet('selectedOrderIds', [$b->id])
        ->call('setCard', 'P')
        ->assertSet('types', ['P'])
        ->call('setCard', 'undispatched')
        ->assertSet('onlyUndispatched', true)
        ->assertSet('types', [])
        ->call('selectAllFiltered');

    expect($component->get('selectedOrderIds'))->toHaveCount(2);
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
    $this->actingAs($user)->get(route('dispatch.main', ['service' => \App\Services\Closure\ClosureServiceLocator::uuid()]))->assertForbidden();
});

it('keeps the eligible-set cache per month and respects frozen types', function () {
    $company = Company::query()->create(['name' => 'Parceira', 'email' => fake()->unique()->safeEmail()]);
    $manager = screenManager();
    $meta    = screenOrder('2026-09-10');
    $frozen  = screenOrder('2026-09-11');

    app(ClosureDispatchService::class)->dispatchOrders([$frozen->id], $company, null, $manager);
    ClosureOrderState::query()->where('order_id', $frozen->id)->update(['closed_meta_type' => 'M']);

    $set = (new ClosureEligibleOrders())->all();

    expect($set[$meta->id]['type'])->toBe('M')
        ->and($set[$frozen->id]['type'])->toBe('M')
        ->and(Cache::has('closure:eligible-orders:v3:2026-10'))->toBeTrue();

    // Virada de mês: outra chave de cache; a ativa vira Passivo, a congelada continua M.
    Carbon::setTestNow('2026-11-03 10:00:00');
    $next = (new ClosureEligibleOrders())->all();

    expect(Cache::has('closure:eligible-orders:v3:2026-11'))->toBeTrue()
        ->and($next[$meta->id]['type'])->toBe('P')
        ->and($next[$frozen->id]['type'])->toBe('M');
});

it('keeps state live after a dispatch without dropping the cached eligible set, and forget clears it', function () {
    $company = Company::query()->create(['name' => 'Parceira', 'email' => fake()->unique()->safeEmail()]);
    $manager = screenManager();
    $a       = screenOrder('2026-09-10');
    $b       = screenOrder('2026-09-11');
    $query   = app(ClosureDispatchListQuery::class);

    expect($query->counts()['undispatched'])->toBe(2);

    app(ClosureDispatchService::class)->dispatchOrders([$a->id], $company, null, $manager);

    $fresh = app(ClosureDispatchListQuery::class);

    expect(Cache::has(ClosureEligibleOrders::cacheKey()))->toBeTrue()
        ->and($fresh->counts()['undispatched'])->toBe(1)
        ->and($fresh->filteredIds(['onlyUndispatched' => true]))->toBe([$b->id])
        ->and($fresh->filteredIds(['status' => 'AVAILABLE']))->toBe([$a->id]);

    ClosureEligibleOrders::forget();

    expect(Cache::has(ClosureEligibleOrders::cacheKey()))->toBeFalse();
});

it('keeps counts and page equivalent to the classifier on a controlled dataset', function () {
    $classifier = new ClosureMetaClassifier();
    $orders     = collect([
        screenOrder('2026-07-10'),
        screenOrder('2026-08-20'),
        screenOrder('2026-09-10'),
        screenOrder('2026-09-25'),
        screenOrder('2026-10-05'),
    ]);
    screenOrder('2026-11-05');                                   // além do mês corrente: fora
    screenOrder('2026-09-10', null, ['statusSist' => 'ENTE X']); // encerrada no SAP: fora
    screenOrder('2026-09-10', null, ['canceled' => true]);       // cancelada: fora

    $expected = $classifier->typesFor($orders)->countBy()->all();
    $query    = app(ClosureDispatchListQuery::class);
    $counts   = $query->counts();

    expect($counts['total'])->toBe(5)
        ->and($counts['P'])->toBe($expected['P'] ?? 0)
        ->and($counts['M'])->toBe($expected['M'] ?? 0)
        ->and($counts['F'])->toBe($expected['F'] ?? 0)
        ->and($query->counts(['search' => $orders[0]->ordem])['total'])->toBe(1);

    $page = $query->paginate(['types' => ['M']], 10, 1);

    expect($page->total())->toBe($expected['M'])
        ->and($page->pluck('id')->all())->toBe($orders->filter(fn ($o) => $classifier->typeFor($o) === 'M')->sortBy('ordem')->pluck('id')->values()->all())
        ->and($page->first()->meta_type)->toBe('M');
});

it('renders client-side selection hooks and serves filtered briefs without touching server selection', function () {
    $a = screenOrder('2026-09-10');
    $b = screenOrder('2026-07-10');

    $component = Livewire::actingAs(screenManager())->test(Index::class)
        ->assertSeeHtml('closureDispatchSelection')
        ->assertSeeHtml('data-order-check="' . $a->id . '"')
        ->assertDontSeeHtml('wire:click="toggleOrder');

    $ids = $component->set('types', ['P'])->call('filteredOrderBriefs')->get('selectedOrderIds');

    expect($ids)->toBe([]);

    $briefs = app(ClosureDispatchListQuery::class)->briefs([$b->id]);

    expect($briefs)->toBe([['id' => $b->id, 'ordem' => $b->ordem, 'note' => $b->Note->note, 'type' => 'P', 'comp' => '08/2026', 'company' => null]]);
    expect(app(ClosureDispatchListQuery::class)->filteredIds(['types' => ['P']]))->toBe([$b->id]);
});

it('shows the competence column (MM/AAAA) for M, P and F orders with a pinned clock', function () {
    // Hoje 16/10/2026: F (fimReal no mês corrente) -> 11/2026; M (mês anterior) -> 10/2026; P -> mês seguinte ao do fimReal.
    $future  = screenOrder('2026-10-05');
    $meta    = screenOrder('2026-09-10');
    $passive = screenOrder('2026-07-10');

    $set = (new ClosureEligibleOrders())->all();

    expect($set[$future->id]['competence'])->toBe('2026-11')
        ->and($set[$meta->id]['competence'])->toBe('2026-10')
        ->and($set[$passive->id]['competence'])->toBe('2026-08');

    Livewire::actingAs(screenManager())->test(Index::class)
        ->assertSee('Competência')
        ->assertSeeHtml('data-comp="11/2026"')
        ->assertSeeHtml('data-comp="10/2026"')
        ->assertSeeHtml('data-comp="08/2026"');

    expect(ClosureMetaClassifier::competenceLabel('2026-09'))->toBe('09/2026')->and(ClosureMetaClassifier::competenceLabel(null))->toBe('—');
});

it('uses the target cycle as competence over fimReal, and closed_meta_period for closed orders', function () {
    $company  = Company::query()->create(['name' => 'Parceira', 'email' => fake()->unique()->safeEmail()]);
    $manager  = screenManager();
    $byTarget = screenOrder('2026-09-10'); // sem target seria 10/2026
    $closed   = screenOrder('2026-07-10');

    $cycle = \App\Models\ClosureCycle::query()->create(['year' => 2027, 'month' => 2, 'label' => '02/2027', 'status' => 'OPEN']);
    \App\Models\ClosureTarget::query()->create(['closure_cycle_id' => $cycle->id, 'order_id' => $byTarget->id, 'note_id' => $byTarget->note_id, 'entry_rule' => 'test', 'frozen_at' => now()]);

    app(ClosureDispatchService::class)->dispatchOrders([$closed->id], $company, null, $manager);
    ClosureOrderState::query()->where('order_id', $closed->id)->update(['closed_meta_type' => 'M', 'closed_meta_period' => '2026-09']);

    $set = (new ClosureEligibleOrders())->all();

    expect($set[$byTarget->id]['competence'])->toBe('2027-02')
        ->and($set[$closed->id]['competence'])->toBe('2026-09');

    // A consulta SQL das listas do Controle/Disponíveis segue a mesma regra.
    $sql = (new ClosureMetaClassifier())->withCompetence(\App\Models\Order::query()->select('orders.id'))->get()->pluck('competence', 'id');

    expect($sql[$byTarget->id])->toBe('2027-02')->and($sql[$closed->id])->toBe('2026-09');
});

it('filters and sorts by competence and offers the present competences with counts', function () {
    $a = screenOrder('2026-09-10'); // 10/2026
    $b = screenOrder('2026-09-20'); // 10/2026
    $c = screenOrder('2026-07-10'); // 08/2026
    $d = screenOrder('2026-10-05'); // 11/2026

    $query    = app(ClosureDispatchListQuery::class);
    $overview = $query->overview([], 25, 1);

    expect($overview['competences'])->toBe(['2026-08' => 1, '2026-10' => 2, '2026-11' => 1])
        ->and($query->filteredIds(['competence' => '2026-10']))->toBe(collect([$a, $b])->sortBy('ordem')->pluck('id')->values()->all())
        ->and($query->filteredIds(['sort' => 'competence', 'dir' => 'asc']))->toBe(array_merge([$c->id], collect([$a, $b])->sortBy('ordem')->pluck('id')->all(), [$d->id]))
        ->and($query->filteredIds(['sort' => 'competence', 'dir' => 'desc'])[0])->toBe($d->id);

    Livewire::actingAs(screenManager())->test(Index::class)
        ->assertSee('Competência')
        ->assertSee('08/2026 (1)')
        ->assertSee('10/2026 (2)')
        ->set('competence', '2026-08')
        ->assertSee($c->ordem)
        ->assertDontSee($a->ordem)
        ->call('sortBy', 'competence')
        ->assertSet('sortField', 'competence')
        ->assertSet('sortDir', 'asc')
        ->call('sortBy', 'competence')
        ->assertSet('sortDir', 'desc')
        ->call('clearFilters')
        ->assertSet('competence', null);
});

function informCompany(string $name): Company
{
    return Company::query()->create(['name' => $name, 'email' => fake()->unique()->safeEmail()]);
}

function informFor(Order $order, Company $company, bool $canceled = false): WorkReport
{
    $report = WorkReport::query()->create(['note_id' => $order->note_id, 'company_id' => $company->id, 'canceled' => $canceled]);
    $report->Orders()->attach($order->id);

    return $report;
}

it('renders the list without an inner scroll window and with the per-page selector in header and footer', function () {
    screenOrder('2026-09-10');

    $component = Livewire::actingAs(screenManager())->test(Index::class)
        ->assertSeeHtml('id="closure-dispatch-per-page-top"')
        ->assertSeeHtml('id="closure-dispatch-per-page"')
        ->assertSee('Itens por página')
        ->assertSee('Exibindo 1–1 de 1');

    $html = $component->lastRenderedDom;

    expect(substr_count($html, 'wire:model="perPage"'))->toBe(2)
        ->and(strpos($html, 'closure-dispatch-per-page-top'))->toBeLessThan(strpos($html, '<table'))
        ->and(strpos($html, '</table>'))->toBeLessThan(strpos($html, 'id="closure-dispatch-per-page"'));

    // O CSS do módulo vive em resources/sass/closure/ (o _styles.blade.php é só um stub depreciado).
    $styles = file_get_contents(resource_path('sass/closure/_layout.scss'));

    expect($styles)->not->toContain('max-height: 68vh')
        ->and($styles)->toContain('.closure-dash .dash-scroll')
        ->and($styles)->toContain('overflow: visible');
});

it('paginates by the chosen page size and keeps the page selection hooks on every page', function () {
    foreach (range(1, 12) as $i) {
        screenOrder('2026-09-10');
    }

    Livewire::actingAs(screenManager())->test(Index::class)
        ->set('perPage', 10)
        ->assertSee('Exibindo 1–10 de 12')
        ->call('gotoPage', 2)
        ->assertSee('Exibindo 11–12 de 12')
        ->assertSeeHtml('data-order-check=')
        ->set('perPage', 999)
        ->assertSet('perPage', 25);
});

it('uses the company of the order inform (work report) in the column and filter, not the holder', function () {
    $informer = informCompany('Empreiteira do Informe');
    $holder   = informCompany('Empresa com a Ordem');
    $a        = screenOrder('2026-09-10');
    $b        = screenOrder('2026-09-11');
    informFor($a, $informer);
    ClosureEligibleOrders::forget();

    app(ClosureDispatchService::class)->dispatchOrders([$b->id], $holder, null, screenManager());

    Livewire::actingAs(screenManager())->test(Index::class)
        ->assertSee('Empreiteira do Informe')
        ->assertSeeHtml('data-company="' . $informer->id . '"')
        ->assertSeeHtml('Com a Ordem agora: ' . $holder->name)
        ->set('filterCompanyId', $informer->id)
        ->assertSee($a->ordem)
        ->assertDontSee($b->ordem)
        ->set('filterCompanyId', $holder->id)
        ->assertDontSee($a->ordem)
        ->assertDontSee($b->ordem);

    expect(app(ClosureDispatchListQuery::class)->companies()->pluck('name')->all())->toBe(['Empreiteira do Informe']);
});

it('picks the most recent non-canceled inform, then falls back to the partial', function () {
    $old     = informCompany('Informe Antigo');
    $new     = informCompany('Informe Recente');
    $gone    = informCompany('Informe Cancelado');
    $partner = informCompany('Parcial Empresa');
    $a       = screenOrder('2026-09-10');
    $b       = screenOrder('2026-09-11');

    informFor($a, $old);
    informFor($a, $new);
    informFor($a, $gone, true);

    $partial = Partial::query()->create(['note_id' => $b->note_id, 'company_id' => $partner->id]);
    $b->Partials()->attach($partial->id);
    ClosureEligibleOrders::forget();

    $map = app(ClosureEligibleOrders::class)->informCompanies();

    expect($map[$a->id])->toBe($new->id)
        ->and($map[$b->id])->toBe($partner->id);
});

it('exposes the inform company in the briefs for the modal suggestion', function () {
    $informer = informCompany('Empreiteira Brief');
    $a        = screenOrder('2026-09-10');
    informFor($a, $informer);
    ClosureEligibleOrders::forget();

    expect(app(ClosureDispatchListQuery::class)->briefs([$a->id])[0]['company'])->toBe($informer->id);

    $html = file_get_contents(resource_path('views/livewire/closure/dispatch/dispatch-modal.blade.php'));

    expect($html)->toContain('suggestCompany()')->toContain('closure-modal-company-hint')->toContain('empresas de informe diferentes');
});

it('keeps the list fast with a warm cache', function () {
    foreach (range(1, 30) as $i) {
        informFor(screenOrder('2026-09-10'), informCompany('Emp ' . $i));
    }

    $manager = screenManager();
    $test    = Livewire::actingAs($manager)->test(Index::class);
    $start   = microtime(true);
    $test->call('$refresh');

    expect((microtime(true) - $start) * 1000)->toBeLessThan(1500);
});

it('gives the Control list the same per-page selector and footer without an inner scroll', function () {
    $component = Livewire::actingAs(screenManager())->test(\App\Http\Livewire\Closure\Dispatch\Control::class)
        ->assertSeeHtml('id="closure-work-per-page-top"')
        ->assertSeeHtml('id="closure-work-per-page"')
        ->assertSee('Exibindo');

    expect($component->lastRenderedDom)->toContain('dash-scroll')
        ->and(view('livewire.closure._styles')->render())->not->toContain('max-height: 68vh');
});

it('shows the Control list with a single actions dropdown, compact SAP status and on-demand operations', function () {
    $manager = screenManager();
    $company = informCompany('Parceira Controle');
    $user    = User::factory()->create(['company_id' => $company->id, 'closure_operator' => true]);
    $order   = screenOrder('2026-09-10', null, ['statusSist' => 'LIB CNPA CAPC DMNV']);
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0010', 'status' => 'ABER']);

    app(ClosureDispatchService::class)->dispatchOrders([$order->id], $company, $user, $manager);
    $state = ClosureOrderState::query()->where('order_id', $order->id)->firstOrFail();

    $component = Livewire::actingAs($manager)->test(\App\Http\Livewire\Closure\Dispatch\Control::class)
        ->assertSeeHtml('>LIB CNPA</span>')
        ->assertDontSeeHtml('>LIB CNPA CAPC DMNV<')
        ->assertSee('1/2')
        ->assertSee('Trocar responsável')
        ->assertSee('Desatribuir')
        ->assertDontSee('Alterar status')
        ->assertDontSee('Finalizar')
        ->assertDontSeeHtml('title="Abrir a gestão da obra"');

    // Operações só carregam ao abrir o modal.
    $component->assertDontSee('Operações da Ordem')
        ->call('showOperations', $state->id)
        ->assertSee('Operações da Ordem ' . $order->ordem)
        ->assertSee('0010')
        ->call('closeOperations')
        ->assertDontSee('Operações da Ordem');
});

it('lets the dispatcher unassign, reassign and remove an Order from the Control list', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $manager = screenManager();
    $company = informCompany('Parceira Gestão');
    $first   = User::factory()->create(['company_id' => $company->id, 'closure_operator' => true]);
    $second  = User::factory()->create(['company_id' => $company->id, 'closure_operator' => true]);
    $order   = screenOrder('2026-09-10');

    app(ClosureDispatchService::class)->dispatchOrders([$order->id], $company, $first, $manager);
    $state = ClosureOrderState::query()->where('order_id', $order->id)->firstOrFail();

    Livewire::actingAs($manager)->test(\App\Http\Livewire\Closure\Dispatch\Control::class)
        ->call('openManage', $state->id, 'reassign')
        ->set('managerAssigneeId', $second->id)
        ->call('confirmManage')
        ->assertSet('manageStateId', $state->id) // motivo obrigatório
        ->set('manageReason', 'Rebalanceamento')
        ->call('confirmManage')
        ->assertSet('manageStateId', null);

    expect($state->fresh()->assigned_user_id)->toBe($second->id);

    Livewire::actingAs($manager)->test(\App\Http\Livewire\Closure\Dispatch\Control::class)
        ->call('openManage', $state->id, 'unassign')
        ->set('manageReason', 'Sem disponibilidade')
        ->call('confirmManage');

    $fresh = $state->fresh();
    expect($fresh->assigned_user_id)->toBeNull()->and($fresh->operational_status)->toBe('AVAILABLE');
});

it('sends an Order to the partner from the status menu instead of asking for a plain reason', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $manager = screenManager();
    $company = informCompany('Empreiteira Pendência');
    $order   = screenOrder('2026-09-10');

    $worker = User::factory()->create(['company_id' => $company->id, 'closure_operator' => true]);
    app(ClosureDispatchService::class)->dispatchOrders([$order->id], $company, $worker, $manager);
    $state = ClosureOrderState::query()->where('order_id', $order->id)->firstOrFail();
    $state->update(['operational_status' => 'IN_PROGRESS']);

    $category = \App\Models\Closure\ClosureIssueCategory::query()->create(['name' => 'Documento pendente', 'active' => true]);
    $sub      = \App\Models\Closure\ClosureIssueSubcategory::query()->create(['closure_issue_category_id' => $category->id, 'name' => 'ART ausente', 'active' => true, 'requires_description' => false]);

    Livewire::actingAs($manager)->test(\App\Http\Livewire\Closure\Work\Index::class)
        ->call('changeStatus', $state->id, 'WAITING_PARTNER')
        ->assertSet('partnerStateId', $state->id)
        ->assertSet('pendingAction', null)
        ->assertSee('Enviar à parceira')
        ->call('sendToPartner')
        ->assertHasErrors(['issueCategoryId', 'issueSubcategoryId'])
        ->set('issueCategoryId', (string) $category->id)
        ->set('issueSubcategoryId', (string) $sub->id)
        ->set('issueCompanyId', (string) $company->id)
        ->call('sendToPartner')
        ->assertSet('partnerStateId', null);

    expect($state->fresh()->operational_status)->toBe('WAITING_PARTNER');
});
