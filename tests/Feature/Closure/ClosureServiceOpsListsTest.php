<?php

use App\Http\Livewire\Closure\Work\{All, Available, History, Index as WorkIndex};
use App\Models\Operation;
use App\Services\Closure\ClosureSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

require_once __DIR__ . '/ClosureScreenFixture.php';

it('keeps the status cards of "Minha atividade" as toggles and explains each status', function () {
    $f = closureCollabFixture();
    $f['state']->update(['operational_status' => 'IN_PROGRESS']);

    Livewire::actingAs($f['internal'])->test(WorkIndex::class)
        ->assertSee($f['order']->ordem)
        ->assertSee('O responsável está trabalhando nela.')
        ->assertSee('Pausadas / aguardando')
        ->call('toggleCard', 'blocked')
        ->assertSet('filters', ['status' => ['PAUSED', 'WAITING_PARTNER', 'DEPOSIT_BLOCKED']])
        ->assertDontSee($f['order']->ordem)
        ->call('toggleCard', 'blocked')
        ->assertSet('filters', [])
        ->call('toggleCard', 'IN_PROGRESS')->assertSet('filters', ['status' => ['IN_PROGRESS']])->assertSee($f['order']->ordem)
        ->call('toggleCard', 'all')->assertSet('filters', []);
});

it('offers the start button for an assigned Order and a modal-based bulk action in "Minha atividade"', function () {
    $f = closureCollabFixture();
    $f['state']->update(['operational_status' => 'ASSIGNED']);

    Livewire::actingAs($f['internal'])->test(WorkIndex::class)
        ->assertSee('Iniciar')
        ->call('toggleState', $f['state']->id)
        ->assertSee('Ações em massa')
        ->call('openBatch', 'start')
        ->assertSet('showBatch', true)
        ->call('prepareBatchPreview')
        ->assertSee('Prontas (1)')
        ->call('confirmBatchAction')
        ->assertSee('Lote concluído');

    expect($f['state']->fresh()->operational_status)->toBe('IN_PROGRESS');
});

it('filters the service lists by rubric and marks Orders reached by a blocked deposit in "Minha atividade"', function () {
    $f = closureCollabFixture();
    $f['state']->update(['operational_status' => 'IN_PROGRESS']);
    $f['order']->Note->update(['rubrica' => 'Mercado']);
    Operation::query()->create(['order_id' => $f['order']->id, 'operacao' => '0010', 'status' => 'ABER', 'cenTrab' => 'CT-A']);
    ctlBlockForService('5503', ['CT-A']);

    Livewire::actingAs($f['internal'])->test(WorkIndex::class)
        ->assertSee('No alcance do depósito 5503')
        ->call('applyListFilters', ['rubric' => ['Mercado']])
        ->assertSee($f['order']->ordem)
        ->call('applyListFilters', ['rubric' => ['Outra']])
        ->assertSet('filters', []);
});

function ctlBlockForService(string $code, array $centers): void
{
    \App\Models\Closure\ClosurePartnerSignal::query()->create([
        'type'   => 'DEPOSIT_BLOCKED', 'status' => 'ACTIVE', 'origin' => 'PARTNER', 'deposit_code' => $code, 'work_centers' => $centers,
        'reason' => 'Inventário', 'created_by' => \App\Models\User::factory()->create()->id,
    ]);
}

it('lets the All list switch scope by card, use the shared filters and show the assume receipt in a modal', function () {
    $f = closureCollabFixture();
    app(ClosureSettings::class)->setSelfAssignEnabled(true, $f['internal']);
    $order = \App\Models\Order::query()->whereKey($f['order']->id)->first();

    Livewire::actingAs($f['internal'])->test(All::class)
        ->assertSee('Lista Encerramento')
        ->call('toggleCard', 'free')->assertSet('scope', 'free')
        ->call('toggleCard', 'free')->assertSet('scope', 'all')
        ->call('applyListFilters', ['rubric' => ['Nada']])->assertSet('filters', [])
        ->call('clearFilters')->assertSet('scope', 'all');

    expect($order)->not->toBeNull();
});

it('applies the ops filters to Available and History and keeps the status filter inside the history statuses', function () {
    $f = closureCollabFixture();
    app(ClosureSettings::class)->setSelfAssignEnabled(true, $f['internal']);
    $f['state']->update(['operational_status' => 'FINISHED', 'assigned_user_id' => $f['internal']->id]);

    Livewire::actingAs($f['internal'])->test(History::class)
        ->assertSee($f['order']->ordem)
        ->assertSee('Finalizada pelo responsável')
        ->call('applyListFilters', ['status' => ['CLOSED']])->assertDontSee($f['order']->ordem)
        ->call('applyListFilters', ['status' => ['IN_PROGRESS']])->assertSet('filters', [])->assertSee($f['order']->ordem)
        ->call('clearFilters')->assertSet('filters', []);

    Livewire::actingAs($f['internal'])->test(Available::class)
        ->call('toggleCard', 'priority')->assertSet('onlyPriority', true)
        ->call('removeAnyFilter', 'priority')->assertSet('onlyPriority', false);
});
