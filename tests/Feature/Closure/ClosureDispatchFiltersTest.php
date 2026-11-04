<?php

use App\Http\Livewire\Closure\Dispatch\Index;
use App\Models\{City, Note, Operation, Order, Service, User};
use App\Services\Closure\{ClosureDispatchService, ClosureEligibleOrders};
use App\Services\Closure\Queries\ClosureDispatchListQuery;
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

function fltManager(): User
{
    return User::factory()->create(['closure_manager' => true, 'closure_operator' => false, 'admin' => false, 'superadm' => false]);
}

function fltCity(string $code, string $name, string $regiao, string $regional): City
{
    return City::query()->create(['rdMunicipio' => $code, 'municipio' => $name, 'cidade' => $name, 'regiao' => $regiao, 'regional' => $regional]);
}

function fltOrder(?string $nexp, ?string $statusUser = null, string $fimReal = '2026-09-10'): Order
{
    $note = Note::query()->create(['note' => (string) fake()->unique()->numberBetween(1000000, 9999999), 'type_note' => 1, 'dt_status' => now(), 'nexp' => $nexp]);

    $order = Order::query()->create([
        'note_id'    => $note->id,
        'ordem'      => (string) fake()->unique()->numberBetween(100000000, 999999999),
        'statusSist' => 'LIB CNPA',
        'statusUser' => $statusUser,
        'canceled'   => false,
    ]);
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => $fimReal]);

    return $order;
}

function fltGeo(): array
{
    fltCity('1001', 'Aracruz', 'Norte', 'Linhares');
    fltCity('1002', 'Sooretama', 'Norte', 'Linhares');
    fltCity('1003', 'Itarana', 'Norte', 'Itarana');
    fltCity('2001', 'Guarapari', 'Sul', 'Guarapari');
    fltCity('2002', 'Anchieta', 'Sul', 'Guarapari');

    return [
        'ara' => fltOrder('1001'), 'soo' => fltOrder('1002'), 'ita' => fltOrder('1003'),
        'gua' => fltOrder('2001'), 'anc' => fltOrder('2002'), 'none' => fltOrder(null),
    ];
}

it('filters by several municipalities (rdMunicipio); empty means everything', function () {
    $o = fltGeo();

    Livewire::actingAs(fltManager())->test(Index::class)
        ->assertSee($o['ara']->ordem)->assertSee($o['none']->ordem)
        ->call('applyListFilters', ['city' => ['1001', '2002']])
        ->assertSee($o['ara']->ordem)->assertSee($o['anc']->ordem)
        ->assertDontSee($o['soo']->ordem)->assertDontSee($o['gua']->ordem)->assertDontSee($o['none']->ordem)
        ->call('clearListFilters')
        ->assertSee($o['soo']->ordem)->assertSee($o['none']->ordem);
});

it('restricts results to the regions/regionals when no municipality is chosen, and municipality narrows further', function () {
    $o = fltGeo();

    Livewire::actingAs(fltManager())->test(Index::class)
        ->call('applyListFilters', ['region' => ['Norte']])
        ->assertSee($o['ara']->ordem)->assertSee($o['soo']->ordem)->assertSee($o['ita']->ordem)
        ->assertDontSee($o['gua']->ordem)->assertDontSee($o['none']->ordem)
        ->call('applyListFilters', ['region' => ['Norte'], 'regional' => ['Linhares']])
        ->assertSee($o['soo']->ordem)->assertDontSee($o['ita']->ordem)
        ->call('applyListFilters', ['region' => ['Norte'], 'regional' => ['Linhares'], 'city' => ['1002']])
        ->assertSee($o['soo']->ordem)->assertDontSee($o['ara']->ordem)
        ->call('applyListFilters', ['region' => ['Norte', 'Sul']])
        ->assertSee($o['gua']->ordem)->assertSee($o['ita']->ordem);
});

it('offers region/regional/municipality options only from the eligible set, with order counts', function () {
    fltGeo();
    fltCity('9999', 'Fora do conjunto', 'Centro', 'Centro');
    fltOrder('1001'); // 2a ordem em Aracruz

    $options = app(ClosureDispatchListQuery::class)->filterOptions();

    expect(collect($options['regions'])->pluck('n', 'v')->all())->toBe(['Norte' => 4, 'Sul' => 2])
        ->and(collect($options['regionals'])->pluck('n', 'v')->all())->toBe(['Guarapari' => 2, 'Itarana' => 1, 'Linhares' => 3])
        ->and(collect($options['cities'])->pluck('n', 'l')->all())->toMatchArray(['Aracruz' => 2, 'Anchieta' => 1])
        ->and(collect($options['cities'])->pluck('l')->contains('Fora do conjunto'))->toBeFalse();
});

it('shows only the first two status tokens, distinct and counted, and filters token by token', function () {
    $a = fltOrder('1001', 'JAND INVE BLOQ');
    $b = fltOrder('1001', 'JAND INVE');
    $c = fltOrder('1001', 'JAND INVEX');
    $d = fltOrder('1001', 'JAND PLAR BLOR');
    $e = fltOrder('1001', 'CANC PLAR');

    expect(collect(app(ClosureDispatchListQuery::class)->filterOptions()['statusUsers'])->pluck('n', 'v')->all())
        ->toBe(['CANC PLAR' => 1, 'JAND INVE' => 2, 'JAND INVEX' => 1, 'JAND PLAR' => 1]);

    Livewire::actingAs(fltManager())->test(Index::class)
        ->call('applyListFilters', ['statusUser' => ['JAND INVE']])
        ->assertSee($a->ordem)->assertSee($b->ordem)
        ->assertDontSee($c->ordem)->assertDontSee($d->ordem)->assertDontSee($e->ordem)
        ->call('applyListFilters', ['statusUser' => ['JAND INVE', 'CANC PLAR']])
        ->assertSee($a->ordem)->assertSee($e->ordem)->assertDontSee($d->ordem);
});

it('combines region, status user, type and search; the server prunes out-of-scope city and regional (cascade)', function () {
    fltGeo();
    $hit   = fltOrder('1001', 'JAND INVE BLOQ');
    $other = fltOrder('1001', 'JAND PRES');
    $sul   = fltOrder('2001', 'JAND INVE');

    Livewire::actingAs(fltManager())->test(Index::class)
        ->call('applyListFilters', ['region' => ['Norte'], 'regional' => ['Linhares', 'Guarapari'], 'city' => ['1001', '2001'], 'statusUser' => ['JAND INVE']])
        ->assertSet('filters', ['region' => ['Norte'], 'regional' => ['Linhares'], 'city' => ['1001'], 'statusUser' => ['JAND INVE']])
        ->assertSee($hit->ordem)->assertDontSee($other->ordem)->assertDontSee($sul->ordem)
        ->call('toggleCard', 'P')
        ->assertDontSee($hit->ordem)
        ->call('toggleCard', 'P')
        ->call('toggleCard', 'M')
        ->set('search', $hit->ordem)
        ->assertSee($hit->ordem)
        ->call('removeAnyFilter', 'statusUser')
        ->assertSet('filters', ['region' => ['Norte'], 'regional' => ['Linhares'], 'city' => ['1001']])
        ->call('clearFilters')
        ->assertSet('filters', [])->assertSet('types', [])->assertSet('search', '');
});

it('persists the filters in the query string and renders chips', function () {
    fltGeo();

    Livewire::withQueryParams(['f' => ['region' => ['Norte'], 'city' => ['1001'], 'statusUser' => ['JAND INVE']]])
        ->actingAs(fltManager())->test(Index::class)
        ->assertSet('filters', ['region' => ['Norte'], 'city' => ['1001'], 'statusUser' => ['JAND INVE']])
        ->assertSee('Região')->assertSee('Município')->assertSee('Aracruz')->assertSee('Status usuário');
});

it('removes a municipality chip and shows the full list again', function () {
    $o = fltGeo();

    Livewire::actingAs(fltManager())->test(Index::class)
        ->call('applyListFilters', ['city' => ['1001']])
        ->assertSee($o['ara']->ordem)->assertDontSee($o['gua']->ordem)
        ->call('removeAnyFilter', 'city')
        ->assertSet('filters', [])
        ->assertSee($o['gua']->ordem);
});

it('treats the type cards as one exclusive choice: a click switches, a second click undoes, Total clears', function () {
    fltGeo();

    Livewire::actingAs(fltManager())->test(Index::class)
        ->call('toggleCard', 'M')->assertSet('types', ['M'])
        ->call('toggleCard', 'P')->assertSet('types', ['P'])
        ->call('toggleCard', 'P')->assertSet('types', [])
        ->call('toggleCard', 'F')->call('toggleCard', 'undispatched')
        ->assertSet('types', ['F'])->assertSet('onlyUndispatched', true)
        ->call('toggleCard', 'all')
        ->assertSet('types', [])->assertSet('onlyUndispatched', false)->assertSet('onlyBlocked', false);
});
