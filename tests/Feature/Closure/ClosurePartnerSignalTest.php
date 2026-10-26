<?php

use App\Http\Livewire\Partner\ClosureIssues\Index as PartnerIssues;
use App\Models\{Centerjob, Operation, Order, User};
use App\Models\Closure\{ClosureDepositCalendarEntry, ClosurePartnerSignal};
use App\Services\Closure\{ClosureDepositCalendarImporter, ClosurePartnerSignalService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Notification, Queue};
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

uses(RefreshDatabase::class);

function signalOp(Order $order, string $workCenter): void
{
    Operation::query()->create(['order_id' => $order->id, 'operacao' => '0010', 'cenTrab' => $workCenter, 'txtCenTrab' => $workCenter, 'status' => 'CONF']);
}

function signalCenterjob(\App\Models\Company $company, string $deposit, string $workCenter, string $center = '5500'): void
{
    Centerjob::query()->create(['company_id' => $company->id, 'center' => $center, 'deposit' => $deposit, 'centerjob' => $workCenter]);
}

it('blocks every in-progress Order of the work centers registered for the deposit and releases them back', function () {
    Queue::fake();
    Notification::fake();
    $f = closureScreenFixture('IN_PROGRESS');
    signalCenterjob($f['other'], '5503', 'ELETRONV'); // empresa executora != empresa da produção

    signalOp($f['order'], 'ELETRONV');
    $second = closureWorkflowFixtureOrderInProduction($f, 'PAUSED');
    signalOp($second['order'], 'ELETRONV');
    $other = closureWorkflowFixtureOrderInProduction($f, 'IN_PROGRESS');
    signalOp($other['order'], 'MANSERV'); // outro depósito

    $service = app(ClosurePartnerSignalService::class);
    $signal  = $service->blockDeposit('5503', $f['dispatcher'], 'Inventário', '5500');

    expect($signal->affected_count)->toBe(2)
        ->and($signal->work_centers)->toBe(['ELETRONV'])
        ->and($f['state']->fresh()->operational_status)->toBe('DEPOSIT_BLOCKED')
        ->and($second['state']->fresh()->operational_status)->toBe('DEPOSIT_BLOCKED')
        ->and($other['state']->fresh()->operational_status)->toBe('IN_PROGRESS');

    expect(fn () => $service->blockDeposit('5503', $f['dispatcher'], 'de novo'))->toThrow(\App\Services\Closure\ClosureOrderWorkflowException::class);

    $released = $service->releaseDeposit($signal, $f['dispatcher']);

    expect($released->released_count)->toBe(2)
        ->and($f['state']->fresh()->operational_status)->toBe('IN_PROGRESS')
        ->and($second['state']->fresh()->operational_status)->toBe('PAUSED');
});

it('refuses a deposit with no work center registered and ignores Orders blocked by hand', function () {
    Queue::fake();
    $f = closureScreenFixture('DEPOSIT_BLOCKED');
    signalOp($f['order'], 'ENGELCAC');

    $service = app(ClosurePartnerSignalService::class);

    expect(fn () => $service->blockDeposit('9999', $f['dispatcher'], 'x'))->toThrow(\App\Services\Closure\ClosureOrderWorkflowException::class);

    signalCenterjob($f['company'], '5703', 'ENGELCAC');
    $signal = $service->blockDeposit('5703', $f['dispatcher'], 'Motivo');
    expect($signal->affected_count)->toBe(0);

    $service->releaseDeposit($signal, $f['dispatcher']);
    expect($f['state']->fresh()->operational_status)->toBe('DEPOSIT_BLOCKED');
});

it('imports the inventory calendar spreadsheet, keeps confirmed rows on re-import, and confirms the block on the date', function () {
    Queue::fake();
    $f = closureScreenFixture('IN_PROGRESS');
    signalCenterjob($f['other'], '5503', 'ELETRONV');
    signalOp($f['order'], 'ELETRONV');

    $sheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $ws    = $sheet->getActiveSheet();
    $ws->fromArray([['Empresa da UND', 'Cidade', 'Centro', 'Depósito', 'Denominação', 'Nome / Tipo de Serviço', 'Mês', 'Responsável pela execução do inventário', 'Inventário realizado', 'Data inicial das contagens']], null, 'A1');
    $ws->fromArray([['EDP ES', 'Nova Venécia', '5500', '5503', 'C ELETROM NVE', 'Construção MT/BT', '4', 'Fulano', '', '4/6/2026']], null, 'A3'); // m/d/Y e linhas em branco acima
    $ws->fromArray([['EDP ES', 'Linhares', '5400', '5403', 'C COMPEL LIN', 'Construção MT/BT', '7', 'Beltrano', 'SIM', '7/1/2026'], ['EDP ES', 'Sem data', '5400', '5999', 'X', 'Y', '1', '', '', '']], null, 'A4');
    $path = tempnam(sys_get_temp_dir(), 'cal') . '.xlsx';
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sheet))->save($path);

    $importer = app(ClosureDepositCalendarImporter::class);
    $result   = $importer->import($path, $f['dispatcher']);

    expect($result['created'])->toBe(2)->and($result['errors'])->toHaveCount(1);
    $entry = ClosureDepositCalendarEntry::query()->where('deposit', '5503')->firstOrFail();
    expect($entry->scheduled_date->toDateString())->toBe('2026-04-06')->and($entry->month)->toBe(4)->and($entry->denomination)->toBe('C ELETROM NVE');
    expect(ClosureDepositCalendarEntry::query()->where('deposit', '5403')->first()->inventory_done)->toBeTrue();

    \Carbon\Carbon::setTestNow('2026-04-06 09:00:00');

    Livewire::actingAs($f['dispatcher'])->test(\App\Http\Livewire\Closure\Dispatch\DepositCalendar::class)
        ->set('status', '')
        ->assertSee('C ELETROM NVE')
        ->assertSee('ELETRONV')
        ->assertSee('Sem centro de trabalho cadastrado') // 5403 não tem vínculo
        ->call('startConfirm', $entry->id)
        ->assertSee('Confirmar bloqueio do depósito 5503')
        ->call('confirmBlock');

    expect($entry->fresh()->status)->toBe('BLOCKED')->and($f['state']->fresh()->operational_status)->toBe('DEPOSIT_BLOCKED');

    // Reimportar não mexe no que já foi bloqueado.
    $again = $importer->import($path, $f['dispatcher']);
    expect($again['kept'])->toBe(1)->and($again['created'])->toBe(0);

    Livewire::actingAs($f['dispatcher'])->test(\App\Http\Livewire\Closure\Dispatch\DepositCalendar::class)
        ->call('startRelease', $entry->id)
        ->call('confirmRelease');

    expect($entry->fresh()->status)->toBe('RELEASED')->and($f['state']->fresh()->operational_status)->toBe('IN_PROGRESS');

    \Carbon\Carbon::setTestNow();
});

it('lets the partner only release a deposit the dispatcher blocked, and only for its own deposits', function () {
    Queue::fake();
    $f = closureScreenFixture('IN_PROGRESS');
    signalCenterjob($f['company'], '5503', 'ELETRONV');
    signalOp($f['order'], 'ELETRONV');

    // O bloqueio é do despacho (calendário); a parceira não tem como bloquear.
    $signal = app(ClosurePartnerSignalService::class)->blockDeposit('5503', $f['dispatcher'], 'Inventário', '5500', null, ClosurePartnerSignal::ORIGIN_CALENDAR);

    $component = Livewire::actingAs($f['partnerUser'])->test(PartnerIssues::class)
        ->assertDontSee('Sinalizar depósito bloqueado')
        ->assertSee('Depósitos bloqueados ativos')
        ->assertSee('Depósito 5503')
        ->assertSee('ELETRONV')
        ->call('startRelease', $signal->id)
        ->assertSee('Confirme que o depósito foi desbloqueado')
        ->call('submitRelease')
        ->assertDontSee('Depósitos bloqueados ativos');

    expect($f['state']->fresh()->operational_status)->toBe('IN_PROGRESS')
        ->and($signal->fresh()->released_by)->toBe($f['partnerUser']->id);

    // Empresa sem esse depósito cadastrado: não enxerga nem libera o bloqueio dos outros.
    $again = app(ClosurePartnerSignalService::class)->blockDeposit('5503', $f['dispatcher'], 'Novo inventário', '5500', null, ClosurePartnerSignal::ORIGIN_CALENDAR);
    $other = User::factory()->create(['superadm' => false, 'admin' => false, 'company_id' => $f['other']->id]);
    $view  = Livewire::actingAs($other)->test(PartnerIssues::class)->assertDontSee('Depósitos bloqueados ativos');

    expect(fn () => $view->call('startRelease', $again->id))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('marks blocked Orders in the lists and on the Order detail, and warns the dispatcher about deposits due today', function () {
    Queue::fake();
    $f = closureScreenFixture('IN_PROGRESS');
    signalCenterjob($f['other'], '5503', 'ELETRONV');
    signalOp($f['order'], 'ELETRONV');

    ClosureDepositCalendarEntry::query()->create(['year' => 2026, 'center' => '5500', 'deposit' => '5503', 'denomination' => 'C ELETROM NVE', 'month' => 10, 'scheduled_date' => today(), 'status' => 'PENDING']);

    Livewire::actingAs($f['dispatcher'])->test(\App\Http\Livewire\Closure\Dispatch\Control::class)
        ->assertSee('depósito(s) em data de inventário')
        ->assertDontSee('Depósito 5503');

    app(ClosurePartnerSignalService::class)->blockDeposit('5503', $f['dispatcher'], 'Inventário', '5500', null, ClosurePartnerSignal::ORIGIN_CALENDAR);

    Livewire::actingAs($f['dispatcher'])->test(\App\Http\Livewire\Closure\Dispatch\Control::class)->set('status', 'all')->assertSee('Depósito 5503');
    Livewire::actingAs($f['dispatcher'])->test(\App\Http\Livewire\Closure\Orders\Detail::class, ['orderId' => $f['order']->id])->assertSee('Depósito 5503')->assertSee('bloqueado');
    Livewire::actingAs($f['dispatcher'])->test(\App\Http\Livewire\Closure\Works\Show::class, ['noteId' => $f['note']->id])->assertSee('Depósito 5503');
});

it('lists the deposit calendar under "Gestão do ciclo", not under "Acompanhamento"', function () {
    $f = closureScreenFixture();
    $this->actingAs($f['dispatcher']);

    $html = view('dispatchs.encerramento.menu')->render();

    expect($html)->toContain('CALENDÁRIO DE DEPÓSITOS')
        ->and(strpos($html, 'CALENDÁRIO DE DEPÓSITOS'))->toBeGreaterThan(strpos($html, 'GESTÃO DO CICLO'))
        ->and(substr_count($html, 'CALENDÁRIO DE DEPÓSITOS'))->toBe(1);
});

it('blocks, releases and skips calendar deposits in bulk, refusing the ones without a work center', function () {
    Queue::fake();
    Notification::fake();
    $f = closureScreenFixture('IN_PROGRESS');
    signalCenterjob($f['other'], '5503', 'ELETRONV');
    signalOp($f['order'], 'ELETRONV');

    $make = fn (string $deposit, string $status = 'PENDING') => ClosureDepositCalendarEntry::query()->create([
        'year' => 2026, 'center' => '5500', 'deposit' => $deposit, 'month' => 4, 'scheduled_date' => '2026-04-06', 'status' => $status,
    ]);
    $ok     = $make('5503');
    $nocent = $make('5999');
    $skip   = $make('5888');

    $component = Livewire::actingAs($f['dispatcher'])->test(\App\Http\Livewire\Closure\Dispatch\DepositCalendar::class)
        ->set('selected', [(string) $ok->id, (string) $nocent->id])
        ->call('startBulk', 'block')
        ->assertSee('Confirmar bloqueio em massa')
        ->call('confirmBulk');

    expect($ok->fresh()->status)->toBe('BLOCKED')
        ->and($nocent->fresh()->status)->toBe('PENDING')
        ->and($f['state']->fresh()->operational_status)->toBe('DEPOSIT_BLOCKED');

    $component->set('selected', [(string) $ok->id])->call('startBulk', 'release')->call('confirmBulk');
    expect($ok->fresh()->status)->toBe('RELEASED')->and($f['state']->fresh()->operational_status)->toBe('IN_PROGRESS');

    $component->set('selected', [(string) $skip->id])->call('startBulk', 'skip')->set('bulkReason', 'Adiado')->call('confirmBulk');
    expect($skip->fresh()->status)->toBe('SKIPPED');

    $component->set('selected', [(string) $skip->id])->call('startBulk', 'reopen')->call('confirmBulk');
    expect($skip->fresh()->status)->toBe('PENDING');
});
