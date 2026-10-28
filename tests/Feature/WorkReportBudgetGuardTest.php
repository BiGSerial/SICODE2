<?php

use App\Http\Livewire\Partner\Forms\{Partialreport, Workreports};
use App\Models\{Company, Note, Order, Pep, User};
use App\Services\Partner\WorkReportBudgetGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Informe de obra (parcial e final) bloqueado quando o PEP de uma ordem aberta está sem saldo.

uses(RefreshDatabase::class);

// O bloqueio vale só no ES por enquanto; o ambiente de teste pode estar em outro ruleset.
beforeEach(fn () => config(['sicode.ruleset' => 'es']));

function budgetPartner(): User
{
    $company = Company::query()->create(['name' => 'Empreiteira Orçamento', 'email' => 'orcamento@example.test']);

    return User::factory()->create([
        'company_id' => $company->id, 'onlyparner' => true, 'superadm' => false, 'admin' => false, 'management' => false, 'operator' => false,
        'user'       => false, 'contract' => false, 'first_pass' => false, 'bypassprod' => false, 'engineer' => false, 'responsible' => false,
        'btzero'     => false, 'can_dispatch' => false, 'analyst' => false, 'legal_controller' => false, 'legal_field' => false, 'legal_manager' => false,
    ]);
}

function budgetNote(?string $pep, ?float $available, string $status = 'ABER', int $type = 1): Note
{
    static $n = 0;
    $note     = Note::query()->create(['note' => '70000' . ++$n, 'nstats' => 51, 'type_note' => $type]);

    if ($pep !== null && $available !== null) {
        Pep::query()->updateOrCreate(['objeto' => $pep], ['disponivel' => $available]);
    }

    Order::query()->create(['note_id' => $note->id, 'ordem' => '1700000' . $n, 'statusSist' => $status, 'pep' => $pep]);

    return $note->fresh();
}

it('bloqueia PEP zerado ou negativo e libera com saldo', function (?float $available, bool $blocked) {
    $note = budgetNote('PEP-X-2', $available);

    expect(app(WorkReportBudgetGuard::class)->blockedPeps($note->Orders))->toBe($blocked ? ['PEP-X-2'] : []);
})->with([
    'zerado'    => [0.0, true],
    'negativo'  => [-150.5, true],
    'com saldo' => [0.01, false],
    'sem dados' => [null, false],
]);

it('ignora ordem sem PEP, PEP fora da sincronização e ordem encerrada', function () {
    $semPep      = budgetNote(null, null);
    $naoSincroni = budgetNote('PEP-FANTASMA-2', null);
    $encerrada   = budgetNote('PEP-ENC-2', -10.0, 'ENC');

    $guard = app(WorkReportBudgetGuard::class);

    expect($guard->blockedPeps($semPep->Orders))->toBe([])
        ->and($guard->blockedPeps($naoSincroni->Orders))->toBe([])
        ->and($guard->blockedPeps($encerrada->Orders))->toBe([]);
});

it('nota tipo 2 nunca bloqueia, mesmo com PEP sem saldo', function () {
    $note = budgetNote('PEP-T2-2', -50.0, 'ABER', 2);

    expect(app(WorkReportBudgetGuard::class)->blockedPeps($note->Orders))->toBe([])
        ->and(app(WorkReportBudgetGuard::class)->exceededPeps($note->Orders, 99999.0))->toBe([]);
});

it('só o PEP com sufixo -2 controla o orçamento; os demais sufixos não bloqueiam', function (string $pep, bool $blocked) {
    $note = budgetNote($pep, -10.0);

    expect(app(WorkReportBudgetGuard::class)->blockedPeps($note->Orders))->toBe($blocked ? [$pep] : []);
})->with([
    '-2'           => ['X/005051-2', true],
    '-3'           => ['X/004782-3', false],
    '-1'           => ['D/449882-1', false],
    '-7'           => ['B/000207-7', false],
    '-22 não é -2' => ['X/005051-22', false],
]);

it('pode ser desligada pela regra do ruleset', function () {
    $note = budgetNote('PEP-OFF-2', 0.0);

    config(['sicode.rules.' . config('sicode.ruleset', 'es') . '.work_report.blocks_without_budget' => false]);

    expect(app(WorkReportBudgetGuard::class)->blockedPeps($note->Orders))->toBe([]);
});

it('por enquanto só o ES bloqueia: no SP o informe segue livre', function () {
    $note = budgetNote('PEP-SP-2', -5.0);

    config(['sicode.ruleset' => 'sp']);
    expect(app(WorkReportBudgetGuard::class)->blockedPeps($note->Orders))->toBe([])
        ->and(app(WorkReportBudgetGuard::class)->exceededPeps($note->Orders, 99999.0))->toBe([]);

    config(['sicode.ruleset' => 'es']);
    expect(app(WorkReportBudgetGuard::class)->blockedPeps($note->Orders))->toBe(['PEP-SP-2']);
});

it('monta o aviso de bloqueio sem revelar o PEP', function () {
    $swal = app(WorkReportBudgetGuard::class)->swal('123');

    expect($swal['icon'])->toBe('error')
        ->and($swal['title'])->toBe('OBRA BLOQUEADA')
        ->and($swal['html'])->toContain('123')
        ->and($swal['html'])->toContain('Sem orçamento disponível')
        ->and($swal['html'])->toContain('engenheiro ou especialista responsável pela sua região')
        ->and($swal['html'])->not->toContain('PEP');
});

it('no informe final, mostra o bloqueio ao selecionar a obra sem saldo e deixa seguir com saldo', function () {
    $user = budgetPartner();

    Livewire::actingAs($user)->test(Workreports::class)
        ->call('toConfirmWork', budgetNote('PEP-F1-2', -1.0))
        ->assertDispatchedBrowserEvent('swal')
        ->assertNotDispatchedBrowserEvent('alertar');

    Livewire::actingAs($user)->test(Workreports::class)
        ->call('toConfirmWork', budgetNote('PEP-F2-2', 500.0))
        ->assertDispatchedBrowserEvent('alertar')
        ->assertNotDispatchedBrowserEvent('swal');
});

it('no informe final, não grava quando o saldo zera depois da seleção', function () {
    $user = budgetPartner();
    $note = budgetNote('PEP-F3-2', 0.0);

    Livewire::actingAs($user)->test(Workreports::class)
        ->set('note', $note)
        ->call('send_informe')
        ->assertDispatchedBrowserEvent('swal');

    expect(\App\Models\WorkReport::query()->count())->toBe(0);
});

it('no informe parcial, bloqueia ao carregar a obra sem saldo', function () {
    $user = budgetPartner();

    Livewire::actingAs($user)->test(Partialreport::class)
        ->call('getNote', budgetNote('PEP-P1-2', 0.0)->id)
        ->assertDispatchedBrowserEvent('swal')
        ->assertSet('note', null);

    $ok = budgetNote('PEP-P2-2', 900.0);

    Livewire::actingAs($user)->test(Partialreport::class)
        ->call('getNote', $ok->id)
        ->assertSet('note.id', $ok->id);
});

it('na parcial, bloqueia o valor da ADS acima do saldo sem revelar o saldo', function () {
    $user = budgetPartner();
    $note = budgetNote('PEP-V1-2', 7351.42);

    $test = Livewire::actingAs($user)->test(Partialreport::class)
        ->call('getNote', $note->id)
        ->set('responsible', 'Fulano')
        ->set('amount', '7.351,43')
        ->call('toSave')
        ->assertDispatchedBrowserEvent('swal')
        ->assertNotDispatchedBrowserEvent('alertar');

    // O saldo nunca vai para a tela do parceiro (nem no aviso, nem no estado do componente).
    expect(json_encode($test->payload))->not->toContain('7351.42')->not->toContain('7.351,42')->not->toContain('disponivel');
});

it('na parcial, deixa seguir quando o valor da ADS cabe no saldo (inclusive igual)', function (string $amount) {
    $user = budgetPartner();
    $note = budgetNote('PEP-V2-2', 1000.0);

    Livewire::actingAs($user)->test(Partialreport::class)
        ->call('getNote', $note->id)
        ->set('responsible', 'Fulano')
        ->set('amount', $amount)
        ->call('toSave')
        ->assertDispatchedBrowserEvent('alertar')
        ->assertNotDispatchedBrowserEvent('swal');
})->with(['abaixo' => ['999,99'], 'igual' => ['1.000,00']]);

it('na parcial, compara o valor com a soma do saldo dos PEPs das ordens abertas', function () {
    $note = budgetNote('PEP-S1-2', 400.0);
    Pep::query()->create(['objeto' => 'PEP-S2-2', 'disponivel' => 600.0]);
    Order::query()->create(['note_id' => $note->id, 'ordem' => '1799999', 'statusSist' => 'ABER', 'pep' => 'PEP-S2-2']);

    $guard  = app(WorkReportBudgetGuard::class);
    $orders = $note->fresh()->Orders;

    expect($guard->exceededPeps($orders, 1000.0))->toBe([])
        ->and($guard->exceededPeps($orders, 1000.01))->toEqualCanonicalizing(['PEP-S1-2', 'PEP-S2-2'])
        ->and($guard->exceededPeps(budgetNote(null, null)->Orders, 999999.0))->toBe([]);
});

it('na parcial, não grava quando o valor da ADS passa do saldo', function () {
    $user = budgetPartner();
    $note = budgetNote('PEP-V3-2', 50.0);

    Livewire::actingAs($user)->test(Partialreport::class)
        ->call('getNote', $note->id)
        ->set('responsible', 'Fulano')
        ->set('amount', '50.01')
        ->call('save')
        ->assertDispatchedBrowserEvent('swal');

    expect(\App\Models\Partial::query()->count())->toBe(0);
});
