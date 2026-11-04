<?php

use App\Http\Livewire\Closure\Dispatch\DepositCalendar;
use App\Models\{Centerjob, Company, User};
use App\Models\Closure\ClosureDepositCalendarEntry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow('2026-11-03 10:00:00'));
afterEach(fn () => Carbon::setTestNow());

function calManager(): User
{
    return User::factory()->create(['closure_manager' => true, 'closure_operator' => false, 'admin' => false, 'superadm' => false]);
}

function calEntry(string $deposit, string $date, string $status = 'PENDING'): ClosureDepositCalendarEntry
{
    return ClosureDepositCalendarEntry::query()->create([
        'year'         => 2026, 'city' => 'Serra', 'center' => '5800', 'deposit' => $deposit, 'denomination' => 'Depósito ' . $deposit,
        'service_type' => 'Automação', 'month' => (int) substr($date, 5, 2), 'scheduled_date' => $date, 'status' => $status,
    ]);
}

it('opens on the pending dates and the cards switch the deadline filter on and off', function () {
    $today = calEntry('5801', '2026-11-03');
    $later = calEntry('5401', '2026-12-20');
    $done  = calEntry('5101', '2026-11-03', 'SKIPPED');

    Livewire::actingAs(calManager())->test(DepositCalendar::class)
        ->assertSet('filters', ['status' => ['PENDING']])
        ->assertSee('5801')->assertSee('5401')->assertDontSee('5101')
        ->call('setDue', 'today')->assertSet('due', 'today')->assertSee('5801')->assertDontSee('5401')
        ->call('setDue', 'today')->assertSet('due', '')->assertSee('5401')
        ->call('clearFilters')->assertSet('filters', [])->assertSee('5101');
});

it('explains why a deposit without registered work center cannot be blocked, even on the due date', function () {
    $entry = calEntry('5801', '2026-11-03');

    Livewire::actingAs(calManager())->test(DepositCalendar::class)
        ->call('startConfirm', $entry->id)
        ->assertSee('Por que não dá para confirmar')
        ->assertSee('Nenhum centro de trabalho cadastrado para o depósito 5801')
        ->assertSeeHtml('title="Cadastre o centro de trabalho do depósito antes de bloquear."');

    expect($entry->fresh()->status)->toBe('PENDING');
});

it('blocks the deposit on the due date once the work center is registered', function () {
    $company = Company::query()->create(['name' => 'Eletro', 'email' => fake()->unique()->safeEmail()]);
    Centerjob::query()->create(['company_id' => $company->id, 'center' => '5800', 'deposit' => '5801', 'centerjob' => 'ELETRO']);
    $entry   = calEntry('5801', '2026-11-03');
    $manager = calManager();

    Livewire::actingAs($manager)->test(DepositCalendar::class)
        ->call('startConfirm', $entry->id)
        ->assertDontSee('Por que não dá para confirmar')
        ->call('confirmBlock')
        ->assertDispatchedBrowserEvent('notification:toast');

    expect($entry->fresh()->status)->toBe('BLOCKED');
});
