<?php

use App\Models\{Adsform, Holiday, Note, Partial, Production};
use App\Services\D5\ProductionFiveNoteResolver;
use App\Services\Payment\PaymentAccompanyRowPresenter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Prazo de medição do Acompanhamento de Pagamento: 8 dias úteis. Parcial = da aprovação do engenheiro (decision_at);
// Final = da entrega da ADS do informe (tacit_delivered_at na ADS tácita, senão created_at da ADS).

beforeEach(function () {
    Carbon::setTestNow('2026-11-04 09:00:00');
    config()->set('sicode.ruleset', 'es');   // ES usa 20 dias corridos em produção; estes testes fixam 8 úteis para exercitar a contagem
    config()->set('sicode.rules.es.payment.accompany_deadline', ['partial' => ['days' => 8, 'business_days' => true], 'final' => ['days' => 8, 'business_days' => true]]);

    $this->mock(ProductionFiveNoteResolver::class, fn ($mock) => $mock->shouldReceive('resolve')->andReturn(null));
});

afterEach(fn () => Carbon::setTestNow());

function accompanyRow(?Partial $partial = null, ?Adsform $ads = null): array
{
    $note = new Note(['note' => '4001922955', 'lexp' => 'VITORIA']);
    $note->setRelation('Files', collect());
    $note->setRelation('WorkForm', null);
    $note->setRelation('Adsform', $ads);
    $note->setRelation('Partials', $partial ? collect([$partial]) : collect());

    $production = new Production(['status' => 2, 'priority' => false, 'partial' => (bool) $partial]);
    $production->setRelation('Note', $note);
    $production->setRelation('WorkReportFlowProductions', collect());

    return app(PaymentAccompanyRowPresenter::class)->present($production);
}

function accompanyPartial(string $decisionAt): Partial
{
    $partial = new Partial(['decision_at' => $decisionAt, 'supervision_at' => '2026-10-01 10:00:00']);
    $partial->setRelation('Orders', collect());
    $partial->setRelation('Company', null);

    return $partial;
}

it('a Parcial vence 8 dias úteis depois da aprovação do engenheiro, e não da fiscalização', function () {
    $row = accompanyRow(accompanyPartial('2026-10-23 15:00:00'));   // sexta

    expect($row['dueAt']->toDateString())->toBe('2026-11-04')       // 26, 27, 28, 29, 30, 2, 3, 4
        ->and($row['dueDays'])->toBe(0)
        ->and($row['dueTone'])->toBe('amber')
        ->and($row['dueLabel'])->toBe('0 d úteis');
});

it('o Final vence 8 dias úteis depois da entrega da ADS', function () {
    $ads             = new Adsform(['tacit' => false, 'amount' => 100]);
    $ads->created_at = '2026-10-30 08:00:00';                       // sexta
    $row             = accompanyRow(null, $ads);

    expect($row['dueAt']->toDateString())->toBe('2026-11-11')
        ->and($row['dueDays'])->toBe(5)
        ->and($row['dueTone'])->toBe('green');
});

it('na ADS tácita vale a data de entrega, e sem entrega o prazo ainda não começou', function () {
    $ads             = new Adsform(['tacit' => true]);
    $ads->created_at = '2026-10-01 08:00:00';
    $semEntrega      = accompanyRow(null, $ads);

    $ads->tacit_delivered_at = '2026-10-30 08:00:00';
    $entregue                = accompanyRow(null, $ads);

    expect($semEntrega['dueAt'])->toBeNull()
        ->and($semEntrega['dueTone'])->toBe('muted')
        ->and($semEntrega['dueLabel'])->toBeNull()
        ->and($entregue['dueAt']->toDateString())->toBe('2026-11-11');
});

it('vencido fica vermelho e conta os dias úteis em atraso', function () {
    $row = accompanyRow(accompanyPartial('2026-10-01 10:00:00'));   // vence 13/10

    expect($row['dueAt']->toDateString())->toBe('2026-10-13')
        ->and($row['dueDays'])->toBeLessThan(0)
        ->and($row['dueTone'])->toBe('red')
        ->and($row['dueLabel'])->toContain('dias vencido');
});

it('feriado do estado não conta como dia útil', function () {
    Holiday::create(['state' => 'ES', 'year' => 2026, 'date' => '2026-10-28', 'name' => 'Feriado', 'source' => 'feriados_api']);

    $row = accompanyRow(accompanyPartial('2026-10-23 15:00:00'));

    expect($row['dueAt']->toDateString())->toBe('2026-11-05');
});

it('sem aprovação do engenheiro a Parcial não tem prazo', function () {
    $partial              = accompanyPartial('2026-10-23');
    $partial->decision_at = null;

    expect(accompanyRow($partial)['dueAt'])->toBeNull();
});

it('a tela é fina: filtros centralizados, sem CSS, JS nem cor de Bootstrap na view', function () {
    $view  = file_get_contents(resource_path('views/livewire/services/payment/accompany/main.blade.php'));
    $class = file_get_contents(app_path('Http/Livewire/Services/Payment/Accompany/Main.php'));

    expect($view)->not->toContain('<style')
        ->and($view)->not->toContain('<script')
        ->and($view)->not->toContain("@livewire('components.filter.filter'")
        ->and($view)->not->toMatch('/text-bg-|table-(primary|danger|dark)/')
        ->and($view)->toContain('x-ops.filters')
        ->and($class)->not->toContain('$_SESSION[\'filter\']')
        ->and($class)->not->toContain('Storage::');
});

it('os atalhos Vencidos e Vencendo apontam para os tons vermelho e âmbar do selo de prazo', function () {
    $component = new App\Http\Livewire\Services\Payment\Accompany\Main();

    expect(App\Http\Livewire\Services\Payment\Accompany\Main::PRAZOS)->toBe(['vencido' => 'red', 'vencendo' => 'amber']);

    $component->toggleDeadline('vencido');
    expect($component->prazo)->toBe('vencido');

    $component->toggleDeadline('vencido');
    expect($component->prazo)->toBe('');

    $component->toggleDeadline('qualquer');
    expect($component->prazo)->toBe('');
});

it('a região define a quantidade e o tipo de dia: ES pode ter regra própria sem mexer no código', function () {
    config()->set('sicode.rules.es.payment.accompany_deadline.final', ['days' => 5, 'business_days' => false]);

    $ads             = new Adsform(['tacit' => false]);
    $ads->created_at = '2026-10-30 08:00:00';
    $es              = accompanyRow(null, $ads);

    config()->set('sicode.ruleset', 'sp');
    $sp = accompanyRow(null, $ads);

    expect($es['dueAt']->toDateString())->toBe('2026-11-04')   // 5 dias corridos
        ->and($es['dueLabel'])->toBe('0 d')
        ->and($es['dueHint'])->toContain('5 dias corridos')
        ->and($sp['dueAt']->toDateString())->toBe('2026-11-11'); // SP segue com 8 úteis
});

it('a hora do evento não muda o vencimento: só conta o dia', function () {
    $cedo              = new Adsform(['tacit' => false]);
    $cedo->created_at  = '2026-10-30 00:01:00';
    $tarde             = new Adsform(['tacit' => false]);
    $tarde->created_at = '2026-10-30 23:59:00';

    expect(accompanyRow(null, $cedo)['dueAt']->toDateTimeString())->toBe(accompanyRow(null, $tarde)['dueAt']->toDateTimeString())
        ->and(accompanyRow(null, $cedo)['dueAt']->format('H:i'))->toBe('00:00');
});

it('o ES usa 20 dias corridos: Parcial da aprovação do engenheiro e Final da entrega da ADS', function () {
    $rules = require base_path('config/sicode.php');

    expect($rules['rules']['es']['payment']['accompany_deadline'])->toBe([
        'partial' => ['days' => 20, 'business_days' => false],
        'final'   => ['days' => 20, 'business_days' => false],
    ]);
});
