<?php

use App\Models\Closure\ClosureOrderState;
use App\Models\{ClosureCycle, ClosureTarget, Note, Operation, Order};
use App\Services\Closure\ClosureMetaClassifier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function metaClassifierOrder(?string $fimReal, array $attributes = [], string $opStatus = 'CONF'): Order
{
    $note = Note::query()->create([
        'note'      => (string) fake()->unique()->numberBetween(1000000, 9999999),
        'type_note' => 1,
    ]);
    $order = Order::query()->create(array_merge([
        'note_id'    => $note->id,
        'ordem'      => (string) fake()->unique()->numberBetween(100000000, 999999999),
        'statusSist' => 'LIB CNPA',
        'canceled'   => false,
    ], $attributes));

    if ($fimReal !== null) {
        Operation::query()->create([
            'order_id' => $order->id,
            'operacao' => '0020',
            'status'   => $opStatus,
            'fimReal'  => $fimReal,
        ]);
    }

    return $order;
}

beforeEach(fn () => Carbon::setTestNow('2026-10-16 10:00:00'));
afterEach(fn () => Carbon::setTestNow());

it('classifies P, M and F from the 0020 fimReal against the current month', function (string $fimReal, ?string $expected) {
    $order = metaClassifierOrder($fimReal);

    expect(app(ClosureMetaClassifier::class)->typeFor($order))->toBe($expected);
})->with([
    'mês anterior (M)'           => ['2026-09-30', 'M'],
    'início do mês anterior (M)' => ['2026-09-01', 'M'],
    'antes do mês anterior (P)'  => ['2026-08-31', 'P'],
    'ano passado (P)'            => ['2025-01-15', 'P'],
    'mês corrente (F)'           => ['2026-10-01', 'F'],
    'depois do mês corrente'     => ['2026-11-01', null],
]);

it('does not classify an order without a confirmed 0020 and ignores other statuses', function () {
    $semOperacao = metaClassifierOrder(null);
    $naoConf     = metaClassifierOrder('2026-09-10', [], 'ABERTA');

    $types = app(ClosureMetaClassifier::class)->typesFor(collect([$semOperacao, $naoConf]));

    expect($types->get($semOperacao->id))->toBeNull()
        ->and($types->get($naoConf->id))->toBeNull();
});

it('moves F to M and M to P when the month turns', function () {
    $f          = metaClassifierOrder('2026-10-05');
    $m          = metaClassifierOrder('2026-09-05');
    $classifier = app(ClosureMetaClassifier::class);

    expect($classifier->typeFor($f))->toBe('F')->and($classifier->typeFor($m))->toBe('M');

    Carbon::setTestNow('2026-11-01 00:05:00');

    expect($classifier->typeFor($f))->toBe('M')->and($classifier->typeFor($m))->toBe('P');

    $ids = fn (string $type) => $classifier->whereMetaType(Order::query(), $type)->pluck('id')->all();

    expect($ids('M'))->toBe([$f->id])->and($ids('P'))->toBe([$m->id])->and($ids('F'))->toBe([]);
});

it('lets the closure target cycle prevail over the date', function () {
    $order = metaClassifierOrder('2026-03-10');
    $cycle = ClosureCycle::query()->create(['year' => 2026, 'month' => 10, 'label' => '2026-10', 'status' => 'FROZEN']);
    ClosureTarget::query()->create([
        'closure_cycle_id' => $cycle->id,
        'order_id'         => $order->id,
        'note_id'          => $order->note_id,
        'entry_rule'       => 'test',
        'frozen_at'        => now(),
    ]);
    $classifier = app(ClosureMetaClassifier::class);

    expect($classifier->typeFor($order))->toBe('M')
        ->and($classifier->whereMetaType(Order::query(), 'M')->pluck('id')->all())->toBe([$order->id]);

    Carbon::setTestNow('2026-11-02');

    expect($classifier->typeFor($order))->toBe('P')
        ->and($classifier->whereMetaType(Order::query(), ['P'])->pluck('id')->all())->toBe([$order->id]);
});

it('returns the frozen type after closing, even when the month changes', function () {
    $order      = metaClassifierOrder('2026-09-10', ['statusSist' => 'ENTE']);
    $state      = frozenMetaState($order, 'M', '2026-10');
    $classifier = app(ClosureMetaClassifier::class);

    Carbon::setTestNow('2027-02-01');

    expect($classifier->typeFor($order))->toBe('M')
        ->and($classifier->whereMetaType(Order::query(), 'M')->pluck('id')->all())->toBe([$order->id])
        ->and($classifier->whereMetaType(Order::query(), 'P')->pluck('id')->all())->toBe([])
        ->and($state->closed_meta_period)->toBe('2026-10');
});

it('classifies a collection without N+1 queries', function () {
    $orders = collect(range(1, 6))->map(fn () => metaClassifierOrder('2026-09-10'));

    DB::enableQueryLog();
    $types = app(ClosureMetaClassifier::class)->typesFor($orders);
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($types)->toHaveCount(6)->and($count)->toBeLessThanOrEqual(3);
});

it('lists only eligible orders', function () {
    $ok     = metaClassifierOrder('2026-09-10');
    $future = metaClassifierOrder('2026-10-20');
    metaClassifierOrder('2026-09-10', ['canceled' => true]);
    metaClassifierOrder('2026-09-10', ['statusSist' => 'ENTE']);
    metaClassifierOrder('2026-09-10', ['statusSist' => 'ENCE']);
    metaClassifierOrder('2026-09-10', ['statusSist' => 'CNPA']);
    metaClassifierOrder('2026-09-10', [], 'ABERTA');
    metaClassifierOrder('2026-11-10');
    metaClassifierOrder(null);

    $ids = app(ClosureMetaClassifier::class)->scopeEligible(Order::query())->orderBy('id')->pluck('id')->all();

    expect($ids)->toBe([$ok->id, $future->id]);
});

it('exposes label, badge class and letter helpers', function () {
    expect(ClosureMetaClassifier::label('P'))->toBe('Passivo')
        ->and(ClosureMetaClassifier::label('M'))->toBe('Meta atual')
        ->and(ClosureMetaClassifier::label('F'))->toBe('Meta futura')
        ->and(ClosureMetaClassifier::badgeClass('P'))->toBe('bg-danger')
        ->and(ClosureMetaClassifier::badgeClass('M'))->toBe('bg-success')
        ->and(ClosureMetaClassifier::badgeClass('F'))->toBe('bg-primary')
        ->and(ClosureMetaClassifier::letter('m'))->toBe('M')
        ->and(ClosureMetaClassifier::letter('X'))->toBeNull()
        ->and(ClosureMetaClassifier::badge('F'))->toContain('bg-primary');
});

function frozenMetaState(Order $order, string $type, string $period): ClosureOrderState
{
    $service = App\Models\Service::query()->firstOrCreate(
        ['folder' => 'encerramento'],
        ['service' => 'Encerramento', 'project' => true, 'construction' => true, 'icon' => 'ri-checkbox-circle-line']
    );
    $production = App\Models\Production::query()->create([
        'note_id'    => $order->note_id,
        'service_id' => $service->uuid,
        'company_id' => App\Models\Company::query()->create(['name' => 'Parceira', 'email' => fake()->unique()->safeEmail()])->id,
    ]);

    return ClosureOrderState::query()->create([
        'order_id'           => $order->id,
        'production_id'      => $production->id,
        'operational_status' => 'CLOSED',
        'closed_at'          => now(),
        'closed_meta_type'   => $type,
        'closed_meta_period' => $period,
    ]);
}
