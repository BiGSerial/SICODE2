<?php

use App\Http\Livewire\Closure\Dispatch\{Control, Index};
use App\Http\Livewire\Closure\Work\{Available, History};
use App\Models\{Company, Note, Operation, Order, User};
use App\Services\Closure\{ClosureEligibleOrders, ClosureInformValue, ClosureListExporter};
use App\Services\Closure\Queries\ClosureDispatchListQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

// Valor da obra = "Valor ADS" do informe a que a Ordem pertence (mesma regra de vínculo da empresa do informe).

uses(RefreshDatabase::class);

beforeEach(fn () => ClosureEligibleOrders::forget());

function valCompany(): Company
{
    return Company::query()->create(['name' => 'Empreiteira ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()]);
}

function valOrder(?Note $note = null): Order
{
    $note ??= Note::query()->create(['note' => (string) fake()->unique()->numberBetween(1000000, 9999999), 'type_note' => 1, 'dt_status' => now()]);

    $order = Order::query()->create(['note_id' => $note->id, 'ordem' => (string) fake()->unique()->numberBetween(100000000, 999999999), 'statusSist' => 'LIB CNPA', 'canceled' => false]);
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => '2026-09-10']);
    ClosureEligibleOrders::forget();

    return $order;
}

function valInform(Note $note, ?Company $company = null, bool $canceled = false): int
{
    return DB::table('work_reports')->insertGetId(['note_id' => $note->id, 'company_id' => ($company ?? valCompany())->id, 'canceled' => $canceled, 'created_at' => now(), 'updated_at' => now()]);
}

function valAds(int $workReportId, int $noteId, float $amount, ?int $forWorkReport = null): void
{
    DB::table('adsforms')->insert([
        'work_report_id' => $forWorkReport ?? $workReportId, 'note_id' => $noteId, 'user_id' => User::factory()->create()->id,
        'amount'         => $amount, 'created_at' => now(), 'updated_at' => now(),
    ]);
}

function valLink(Order $order, int $workReportId): void
{
    DB::table('order_work_report')->insert(['order_id' => $order->id, 'work_report_id' => $workReportId]);
}

// ---------------------------------------------------------------- regra

it('takes the ADS value of the informe linked to the Order', function () {
    $order = valOrder();
    $w     = valInform($order->Note);
    valLink($order, $w);
    valAds($w, $order->note_id, 12345.67);

    expect(app(ClosureInformValue::class)->forOrders([$order->id]))->toBe([$order->id => 12345.67]);
});

it('falls back to the Note ADS when the informe has no ADS of its own, and prefers the informe ADS when it has', function () {
    $a  = valOrder();
    $wa = valInform($a->Note);
    valLink($a, $wa);
    // ADS da Nota (sem vínculo com o informe, work_report_id de OUTRO informe da Nota)
    valAds(0, $a->note_id, 800.00, valInform($a->Note));

    $b  = valOrder();
    $wb = valInform($b->Note);
    valLink($b, $wb);
    valAds($wb, $b->note_id, 5000.50);

    $values = app(ClosureInformValue::class)->forOrders([$a->id, $b->id]);

    expect($values[$a->id])->toBe(800.0)->and($values[$b->id])->toBe(5000.5);
});

it('uses the partial value when the Order only has a partial, and the Note informe when it has no explicit link', function () {
    $partialOrder = valOrder();
    $company      = valCompany();
    $partialId    = DB::table('partials')->insertGetId(['note_id' => $partialOrder->note_id, 'company_id' => $company->id, 'value' => 1500.25, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('order_partial')->insert(['order_id' => $partialOrder->id, 'partial_id' => $partialId]);

    $noteOrder = valOrder();                                  // sem vínculo explícito: vale o informe da Nota
    $w         = valInform($noteOrder->Note);
    valAds($w, $noteOrder->note_id, 9999.99);

    $values = app(ClosureInformValue::class)->forOrders([$partialOrder->id, $noteOrder->id]);

    expect($values[$partialOrder->id])->toBe(1500.25)->and($values[$noteOrder->id])->toBe(9999.99);
});

it('follows the same priority and ties as the informe company: Order informe, then partial, then Note informe; newest wins', function () {
    $order = valOrder();
    $older = valInform($order->Note);
    $newer = valInform($order->Note);
    valLink($order, $older);
    valLink($order, $newer);
    valAds($older, $order->note_id, 100.00);
    valAds($newer, $order->note_id, 200.00);

    // a parcial NÃO vence o informe ligado à Ordem
    $partialId = DB::table('partials')->insertGetId(['note_id' => $order->note_id, 'company_id' => valCompany()->id, 'value' => 777.00, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('order_partial')->insert(['order_id' => $order->id, 'partial_id' => $partialId]);

    expect(app(ClosureInformValue::class)->forOrders([$order->id])[$order->id])->toBe(200.0);
});

it('ignores a canceled informe and returns null without informe, partial or ADS amount', function () {
    $canceled = valOrder();
    $w        = valInform($canceled->Note, null, true);
    valLink($canceled, $w);
    valAds($w, $canceled->note_id, 4000.00);

    $none     = valOrder();
    $noAmount = valOrder();
    $wn       = valInform($noAmount->Note);
    valLink($noAmount, $wn);

    $values = app(ClosureInformValue::class)->forOrders([$canceled->id, $none->id, $noAmount->id, null, 0]);

    expect($values)->toEqual([$canceled->id => null, $none->id => null, $noAmount->id => null]);
});

it('formats the value in reais and shows a dash when there is none', function () {
    expect(ClosureInformValue::format(1234567.8))->toBe('R$ 1.234.567,80')
        ->and(ClosureInformValue::format(0.0))->toBe('R$ 0,00')
        ->and(ClosureInformValue::format(null))->toBe('—');
});

// ---------------------------------------------------------------- telas

function valManager(): User
{
    return User::factory()->create(['closure_manager' => true, 'closure_operator' => false, 'admin' => false, 'superadm' => false]);
}

it('shows the work value column on the dispatch list, only the status in the status column, and the signal as row color', function () {
    $order = valOrder();
    $w     = valInform($order->Note);
    valLink($order, $w);
    valAds($w, $order->note_id, 45678.9);

    $assignee = User::factory()->create();
    $f        = closureWorkflowFixture('IN_PROGRESS');
    $f['order']->update(['statusSist' => 'LIB CNPA']);
    Operation::query()->create(['order_id' => $f['order']->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => '2026-09-10']);
    $f['state']->update(['assigned_user_id' => $assignee->id]);
    ClosureEligibleOrders::forget();

    $html = Livewire::actingAs(valManager())->test(Index::class)
        ->assertSee('Valor da obra (ADS)')
        ->assertSee('R$ 45.678,90')
        ->lastRenderedDom;

    $row = preg_match('/<tr[^>]*data-order-row="' . $f['order']->id . '".*?<\/tr>/s', $html, $m) ? $m[0] : '';

    // o status da atividade aparece uma vez, sem o selo repetido; a cor está na linha (com a explicação no tooltip)
    expect($row)->toContain('signal-row signal-assigned')->and($row)->toContain('data-signal="assigned"')->and($row)->toContain('title="Atribuída:')
        ->and($row)->not->toContain('class="signal signal-')
        ->and(substr_count($row, 'Atribuída'))->toBeLessThanOrEqual(3);

    // Ordem sem informe: traço
    $other = Livewire::actingAs(valManager())->test(Index::class)->lastRenderedDom;
    $plain = preg_match('/<tr[^>]*data-order-row="' . $f['order']->id . '".*?<\/tr>/s', $other, $mm) ? $mm[0] : '';

    expect($plain)->toContain('—');
});

it('shows the work value on the service lists (Minha atividade/Controle, Disponíveis and Histórico)', function () {
    $f = closureCollabFixture();
    $f['state']->update(['operational_status' => 'IN_PROGRESS']);

    $w = valInform($f['note']);
    valLink($f['order'], $w);
    valAds($w, $f['note']->id, 31415.92);

    app(\App\Services\Closure\ClosureSettings::class)->setSelfAssignEnabled(true, $f['dispatcher']);

    Livewire::actingAs($f['dispatcher'])->test(Control::class)->assertSee('Valor da obra (ADS)')->assertSee('R$ 31.415,92');

    foreach ([Available::class, History::class] as $class) {
        Livewire::actingAs($f['dispatcher'])->test($class)->assertSee('Valor da obra (ADS)');
    }

    expect(Livewire::actingAs($f['dispatcher'])->test(Control::class)->lastRenderedDom)->not->toContain('class="signal signal-');
});

it('keeps only the activity status in the Control status column', function () {
    $f = closureCollabFixture();
    $f['state']->update(['operational_status' => 'FINISHED', 'entered_current_status_at' => now()->subDays(3)]);

    $html = Livewire::actingAs($f['dispatcher'])->test(Control::class)->lastRenderedDom;
    $row  = preg_match('/<tr[^>]*closure-work-state-' . $f['state']->id . '.*?<\/tr>/s', $html, $m) ? $m[0] : '';

    // status único "Finalizado"; o vermelho (+24h sem baixa no SAP) vem da cor da linha
    expect(substr_count($row, 'Finalizado'))->toBe(1)
        ->and($row)->toContain('signal-row signal-overdue')->and($row)->not->toContain('Sem baixa no SAP</span>');
});

// ---------------------------------------------------------------- exportação

it('puts the work value in the Excel export of the lists', function () {
    expect(ClosureListExporter::HEADINGS)->toContain('Valor da obra (ADS) R$');

    $order = valOrder();
    $w     = valInform($order->Note);
    valLink($order, $w);
    valAds($w, $order->note_id, 2500.0);

    $records = app(ClosureDispatchListQuery::class)->exportRecords([]);
    $built   = app(ClosureListExporter::class)->dispatchRows($records, collect());
    $index   = array_search('Valor da obra (ADS) R$', ClosureListExporter::HEADINGS, true);

    expect($built['rows'][0])->toHaveCount(count(ClosureListExporter::HEADINGS))
        ->and($built['rows'][0][$index])->toBe(2500.0);

    $f = closureCollabFixture();
    $f['state']->update(['operational_status' => 'IN_PROGRESS']);
    $w2 = valInform($f['note']);
    valLink($f['order'], $w2);
    valAds($w2, $f['note']->id, 777.5);

    $states = \App\Models\Closure\ClosureOrderState::query()->with(['Order.Note', 'Order.Operations', 'CurrentHolderUser', 'CurrentHolderCompany'])->whereKey($f['state']->id)->get();
    $rows   = app(ClosureListExporter::class)->stateRows($states, collect());

    expect($rows['rows'][0])->toHaveCount(count(ClosureListExporter::HEADINGS))->and($rows['rows'][0][$index])->toBe(777.5);
});
