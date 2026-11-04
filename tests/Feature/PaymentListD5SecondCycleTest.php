<?php

use App\Models\{Adsform, FiveNote, Note, WorkReport};
use App\Services\Payment\PaymentListRowPresenter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// D5 de 2º ciclo (retorno para liberar a carta) nas listas de Despacho e Serviços: datas em cinza (secondary), "D5" no lugar da
// contagem de dias e fora dos atalhos de prazo. Só o 1º ciclo é cobrado.

beforeEach(function () {
    Carbon::setTestNow('2026-11-04 09:00:00');
    config()->set('sicode.ruleset', 'es');
    config()->set('sicode.rules.es.payment.accompany_deadline', ['partial' => ['days' => 8, 'business_days' => true], 'final' => ['days' => 8, 'business_days' => true]]);
});

afterEach(fn () => Carbon::setTestNow());

function listRowFor(?FiveNote $five, string $profile): array
{
    $ads             = new Adsform(['tacit' => false, 'amount' => 100]);
    $ads->created_at = '2026-10-20 08:00:00';

    $report     = new WorkReport(['note_id' => 1]);
    $report->id = 7;
    $report->setRelation('Adsform', $ads);
    $report->setRelation('Orders', collect());
    $report->setRelation('Company', null);

    $note     = new Note(['note' => '4001922955', 'lexp' => 'VITORIA', 'type_note' => 1]);
    $note->id = 1;
    $note->setRelation('WorkForm', $report);
    $note->setRelation('WorkForms', collect([$report]));
    $note->setRelation('Partials', collect());
    $note->setRelation('FiveNote', $five);
    $note->setAttribute('payment_work_report_id', 7);

    return app(PaymentListRowPresenter::class)->present($note, ['eval' => ['block' => 0, 'reason' => null, 'command' => false], 'stack' => null, 'canDispatch' => true], $profile);
}

it('mostra a D5 de 2º ciclo em cinza, com D5 no lugar dos dias, nas listas de Despacho e Serviços', function (string $profile) {
    $five = new FiveNote(['is_completed' => true, 'is_supervisioned' => true, 'is_archived' => false, 'returned' => false]);
    $row  = listRowFor($five, $profile);

    expect($row['cycleTwo'])->toBeTrue()
        ->and($row['adsTone'])->toBe('secondary')->and($row['adsLabel'])->toBe('D5')
        ->and($row['dueTone'])->toBe('secondary')->and($row['dueLabel'])->toBe('D5');
})->with(['dispatch', 'service']);

it('a D5 de 1º ciclo e o informe sem D5 continuam com prazo e contagem de dias', function (?FiveNote $five) {
    $row = listRowFor($five, 'service');

    expect($row['cycleTwo'])->toBeFalse()
        ->and($row['dueTone'])->not->toBe('secondary')
        ->and($row['dueLabel'])->not->toBe('D5')
        ->and($row['adsLabel'])->toContain('dias');
})->with([
    'sem D5'   => [null],
    'D5 do 1º' => [fn () => new FiveNote(['is_completed' => false, 'is_supervisioned' => false, 'is_archived' => false, 'returned' => false])],
]);
