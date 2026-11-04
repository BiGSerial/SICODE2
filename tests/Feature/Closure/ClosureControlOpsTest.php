<?php

use App\Http\Livewire\Closure\Dispatch\Control;
use App\Models\Closure\{ClosureOrderState, ClosurePartnerSignal, ClosurePartnerSignalState};
use App\Models\{Operation, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

require_once __DIR__ . '/ClosureScreenFixture.php';

function ctlBlock(string $code, array $centers, ?ClosureOrderState $locked = null): ClosurePartnerSignal
{
    $signal = ClosurePartnerSignal::query()->create([
        'type'         => ClosurePartnerSignal::TYPE_DEPOSIT_BLOCKED, 'status' => ClosurePartnerSignal::STATUS_ACTIVE, 'origin' => ClosurePartnerSignal::ORIGIN_PARTNER,
        'deposit_code' => $code, 'work_centers' => $centers, 'reason' => 'Inventário', 'created_by' => User::factory()->create()->id,
    ]);

    if ($locked) {
        ClosurePartnerSignalState::query()->create(['closure_partner_signal_id' => $signal->id, 'closure_order_state_id' => $locked->id, 'previous_status' => 'IN_PROGRESS']);
    }

    return $signal;
}

it('lists the pile without responsible (Disponível) in the Control and counts it in the cards', function () {
    $f = closureCollabFixture(); // estado AVAILABLE, posse da empresa, sem responsável fixo

    $f['state']->update(['assigned_user_id' => null, 'operational_status' => 'AVAILABLE']);

    Livewire::actingAs($f['dispatcher'])->test(Control::class)
        ->assertSee($f['order']->ordem)
        ->assertSee('Na pilha da empresa, sem responsável.')
        ->call('toggleCard', 'AVAILABLE')
        ->assertSet('filters', ['status' => ['AVAILABLE']])
        ->assertSee($f['order']->ordem)
        ->call('toggleCard', 'AVAILABLE')
        ->assertSet('filters', [])
        ->call('toggleCard', 'priority')->assertSet('onlyPriority', true)
        ->call('toggleCard', 'all')->assertSet('onlyPriority', false);
});

it('filters the Control by rubric and company with the server validating the options', function () {
    $f = closureCollabFixture();
    $f['order']->Note->update(['rubrica' => 'Mercado']);

    Livewire::actingAs($f['dispatcher'])->test(Control::class)
        ->call('applyListFilters', ['rubric' => ['Mercado']])
        ->assertSet('filters', ['rubric' => ['Mercado']])
        ->assertSee($f['order']->ordem)
        ->call('applyListFilters', ['rubric' => ['Outra']])
        ->assertSet('filters', [])
        ->call('applyListFilters', ['company' => [(string) $f['company']->id]])
        ->assertSee($f['order']->ordem);
});

it('says on each Control row whether the deposit block locked the Order or only reaches it', function () {
    $f = closureCollabFixture();
    $f['state']->update(['operational_status' => 'DEPOSIT_BLOCKED']);
    Operation::query()->create(['order_id' => $f['order']->id, 'operacao' => '0010', 'status' => 'ABER', 'cenTrab' => 'CT-A']);
    ctlBlock('5503', ['CT-A'], $f['state']);

    $second = closureCollabExtraOrder($f, 'FINISHED');
    Operation::query()->create(['order_id' => $second->order_id, 'operacao' => '0010', 'status' => 'ABER', 'cenTrab' => 'CT-A']);

    $html = Livewire::actingAs($f['dispatcher'])->test(Control::class)->assertSee('Depósito bloqueado')->lastRenderedDom;
    $row  = fn ($id) => preg_match('/<tr[^>]*closure-work-state-' . $id . '.*?<\/tr>/s', $html, $m) ? $m[0] : '';

    expect($row($f['state']->id))->toContain('Depósito 5503 bloqueado')->toContain('Travada porque o depósito')
        ->and($row($second->id))->toContain('No alcance do depósito 5503')->toContain('está em &quot;Finalizado&quot;');
});

it('runs a bulk action through the modal: open, preview, confirm and receipt', function () {
    $f = closureCollabFixture();
    $f['state']->update(['operational_status' => 'IN_PROGRESS']);

    $component = Livewire::actingAs($f['dispatcher'])->test(Control::class)
        ->call('toggleState', $f['state']->id)
        ->assertSee('Ações em massa')
        ->call('openBatch', 'set_priority')
        ->assertSet('showBatch', true)
        ->assertSee('Ajustar prioridade')
        ->set('managerPriority', '1')
        ->call('prepareBatchPreview')
        ->assertSee('Prontas (1)')
        ->call('confirmBatchAction')
        ->assertSee('Lote concluído')
        ->assertSee('Processadas (1)');

    expect($f['state']->fresh()->priority)->toBeTruthy();

    $component->call('closeBatch')->assertSet('showBatch', false)->assertSet('batchAction', '');
});

it('only offers bulk actions the user may run and refuses opening one without selection', function () {
    $f = closureCollabFixture();

    Livewire::actingAs($f['dispatcher'])->test(Control::class)
        ->call('openBatch', 'assign')->assertSet('showBatch', false)
        ->call('openBatch', 'hack')->assertSet('showBatch', false)
        ->call('selectAllFilteredStates')
        ->assertSet('selectedStateIds', [$f['state']->id]);
});
