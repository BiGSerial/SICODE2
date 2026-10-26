<?php

use App\Http\Livewire\Closure\Dispatch\{Control, Index};
use App\Http\Livewire\Closure\Work\{Available, History};
use App\Models\{Note, Operation, Order, User};
use App\Services\Closure\{ClosureBulkSearch, ClosureEligibleOrders};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

// Busca em massa: colar uma lista de Notas/Ordens e filtrar de uma vez (correspondência exata), com o resumo do que não foi encontrado.

uses(RefreshDatabase::class);

beforeEach(fn () => ClosureEligibleOrders::forget());

function bulkOrder(string $ordem, string $note): Order
{
    $n     = Note::query()->create(['note' => $note, 'type_note' => 1, 'dt_status' => now()]);
    $order = Order::query()->create(['note_id' => $n->id, 'ordem' => $ordem, 'statusSist' => 'LIB CNPA', 'canceled' => false]);
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => '2026-09-10']);
    ClosureEligibleOrders::forget();

    return $order;
}

function bulkManager(): User
{
    return User::factory()->create(['closure_manager' => true, 'closure_operator' => false, 'admin' => false, 'superadm' => false]);
}

// ---------------------------------------------------------------- interpretação

it('parses pasted lists separated by lines, commas, semicolons or spaces, without repeats, keeping the order', function () {
    expect(ClosureBulkSearch::parse("12558185\n150000000283, 150000000558;  15784343\t\"150000000283\" '16768191'\r\n\n"))
        ->toBe(['12558185', '150000000283', '150000000558', '15784343', '16768191'])
        ->and(ClosureBulkSearch::parse(''))->toBe([])
        ->and(ClosureBulkSearch::parse(null))->toBe([])
        ->and(ClosureBulkSearch::parse(" ,;\n "))->toBe([]);

    $many = implode(' ', range(1, ClosureBulkSearch::MAX_TERMS + 50));

    expect(ClosureBulkSearch::parse($many))->toHaveCount(ClosureBulkSearch::MAX_TERMS);
});

it('summarizes found and missing terms', function () {
    $summary = ClosureBulkSearch::summary(['1', '2', '3', '4'], ['1', ' 3 ', 99]);

    expect($summary)->toBe(['total' => 4, 'found' => 2, 'missing' => ['2', '4']]);
});

// ---------------------------------------------------------------- Despacho

it('filters the dispatch list by the pasted Notes and Orders, exact match only', function () {
    $a = bulkOrder('150000000283', '12558185');
    $b = bulkOrder('150000000558', '15784343');
    $c = bulkOrder('150000003895', '15259393');

    $component = Livewire::actingAs(bulkManager())->test(Index::class)
        ->assertSeeHtml('data-ordem="' . $a->ordem . '"')->assertSeeHtml('data-ordem="' . $b->ordem . '"')->assertSeeHtml('data-ordem="' . $c->ordem . '"')
        ->set('bulkText', "150000000283\n15784343\n999999999\n1500000002")   // Ordem de A, Nota de B, inexistente, parcial (não casa)
        ->call('applyBulkSearch')
        ->assertSet('bulkOpen', false)
        ->assertSeeHtml('data-ordem="' . $a->ordem . '"')->assertSeeHtml('data-ordem="' . $b->ordem . '"')->assertDontSeeHtml('data-ordem="' . $c->ordem . '"');

    expect($component->get('bulkTerms'))->toBe(['150000000283', '15784343', '999999999', '1500000002']);

    $report = $component->instance()->bulkReport();

    expect($report)->toBe(['total' => 4, 'found' => 2, 'missing' => ['999999999', '1500000002']]);

    $component->assertSee('Busca em massa')->assertSee('2 de 4 encontrada(s)')->assertSee('2 não encontrada(s)')->assertSee('999999999')
        ->assertSee('Copiar não encontradas')
        ->assertSee('Busca em massa: 4 termo(s)');
});

it('combines the bulk search with the other filters and clears it with the filters', function () {
    $a = bulkOrder('150000000283', '12558185');
    $b = bulkOrder('150000000558', '15784343');

    $component = Livewire::actingAs(bulkManager())->test(Index::class)
        ->set('bulkText', "150000000283 150000000558")->call('applyBulkSearch')
        ->set('search', '12558185')
        ->assertSeeHtml('data-ordem="' . $a->ordem . '"')->assertDontSeeHtml('data-ordem="' . $b->ordem . '"');

    // a explicação do resumo é relativa aos filtros atuais: a 2ª Ordem some por causa do filtro "Busca"
    expect($component->instance()->bulkReport())->toBe(['total' => 2, 'found' => 1, 'missing' => ['150000000558']]);

    $component->call('clearFilters')->assertSet('bulkTerms', [])->assertSeeHtml('data-ordem="' . $a->ordem . '"')->assertSeeHtml('data-ordem="' . $b->ordem . '"')->assertDontSee('encontrada(s)');
});

it('removes the bulk search chip and the selection only reaches the pasted Orders', function () {
    $a = bulkOrder('150000000283', '12558185');
    bulkOrder('150000000558', '15784343');

    $component = Livewire::actingAs(bulkManager())->test(Index::class)
        ->set('bulkText', '12558185')->call('applyBulkSearch')
        ->call('selectAllFiltered');

    expect(array_map('intval', $component->get('selectedOrderIds')))->toBe([$a->id]);

    $component->call('removeFilter', 'bulk')->assertSet('bulkTerms', []);
});

it('renders the button, the modal with the live counter and no inline style or script', function () {
    $component = Livewire::actingAs(bulkManager())->test(Index::class)
        ->assertSee('Buscar em massa')
        ->assertDontSee('Cole as Notas ou Ordens')
        ->call('openBulkSearch')
        ->assertSet('bulkOpen', true)
        ->assertSee('Cole as Notas ou Ordens que você quer encontrar de uma vez.')
        ->assertSee('termo(s)')
        ->assertSeeHtml('wire:click="applyBulkSearch"');

    $component->call('closeBulkSearch')->assertSet('bulkOpen', false);

    foreach (['bulk-search', 'bulk-search-result'] as $name) {
        $source = file_get_contents(resource_path("views/components/closure/{$name}.blade.php"));
        expect($source)->not->toContain('style="')->and($source)->not->toContain('<script');
    }
});

// ---------------------------------------------------------------- listas de trabalho

it('filters the work lists (Control, Available, History) by the pasted Notes and Orders too', function () {
    $f      = closureCollabFixture();
    $second = closureCollabExtraOrder($f);
    $user   = $f['dispatcher'];

    // o Controle não lista a pilha "Disponível": as duas Ordens precisam estar em andamento
    $f['state']->update(['operational_status' => 'IN_PROGRESS']);
    $second->update(['operational_status' => 'IN_PROGRESS']);

    // Controle: pela Ordem (mais um termo inexistente)
    Livewire::actingAs($user)->test(Control::class)
        ->set('bulkText', (string) $f['order']->ordem . ' 000000')->call('applyBulkSearch')
        ->assertSeeHtml('closure-work-state-' . $f['state']->id)->assertDontSeeHtml('closure-work-state-' . $second->id)
        ->assertSee('1 de 2 encontrada(s)')->assertSee('000000');

    // pela NOTA: as duas Ordens da obra
    Livewire::actingAs($user)->test(Control::class)
        ->set('bulkText', (string) $f['note']->note)->call('applyBulkSearch')
        ->assertSeeHtml('closure-work-state-' . $f['state']->id)->assertSeeHtml('closure-work-state-' . $second->id);

    // "Atividades disponíveis" só abre com o auto atribuir ligado nas configurações
    app(\App\Services\Closure\ClosureSettings::class)->setSelfAssignEnabled(true, $user);

    foreach ([Available::class, History::class] as $class) {
        $c = Livewire::actingAs($user)->test($class)->assertSee('Buscar em massa')->set('bulkText', '123 456')->call('applyBulkSearch');

        expect($c->get('bulkTerms'))->toBe(['123', '456']);
        $c->call('clearBulkSearch')->assertSet('bulkTerms', []);
    }
});

it('sends the bulk search to the Excel export of the lists', function () {
    \Illuminate\Support\Facades\Bus::fake();

    Livewire::actingAs(bulkManager())->test(Index::class)->set('bulkText', '12558185 150000000283')->call('applyBulkSearch')->call('exportList');

    \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\Closure\ExportClosureListJob::class, fn ($job) => $job->filters['bulk'] === ['12558185', '150000000283']);
});

// ---------------------------------------------------------------- visual

it('lets the filter menus overflow the card and keeps colored rows distinguishable', function () {
    $filters = file_get_contents(resource_path('sass/closure/_filters.scss'));
    $signals = file_get_contents(resource_path('sass/closure/_signals.scss'));

    expect($filters)->toMatch('/\.closure-dash \.filter-bar\s*\{\s*overflow:\s*visible;/');

    // linha colorida: borda lateral, divisória mais escura e zebra dentro da mesma cor
    expect($signals)->toContain('tr.signal-row > td:first-child')->toContain('border-left: 5px solid var(--sig)')
        ->and($signals)->toContain('border-bottom: 1px solid color-mix')
        ->and($signals)->toContain('tr.signal-row:nth-child(even) > td');

    foreach (['dispatch/_control-top', 'work/available', 'work/history', 'dispatch/index'] as $view) {
        expect(file_get_contents(resource_path("views/livewire/closure/{$view}.blade.php")))->toContain('filter-bar');
    }
});
