<?php

use App\Http\Livewire\Closure\Transfers\{Center, SendModal};
use App\Models\Closure\{ClosureInternalReturn, ClosureOrderEvent, ClosureTransferRequest};
use App\Models\{Company, User};
use App\Services\Closure\{ClosureOrderWorkflowService, ClosureTransferException, ClosureTransferService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

// Transferência de atividade entre usuários do Encerramento: o destinatário aceita/rejeita, quem enviou cancela; sem gestão no meio.

uses(RefreshDatabase::class);

beforeEach(fn () => Notification::fake());

function transferTarget(array $overrides = []): User
{
    $company = Company::query()->create(['name' => 'Destino ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()]);

    return User::factory()->create(array_merge(['superadm' => false, 'admin' => false, 'closure_manager' => false, 'closure_operator' => true, 'company_id' => $company->id], $overrides));
}

function transferService(): ClosureTransferService
{
    return app(ClosureTransferService::class);
}

it('sends a transfer request that waits for the recipient and keeps the order with the sender meanwhile', function () {
    $f      = closureScreenFixture();
    $target = transferTarget();

    $request = transferService()->send($f['internal'], $target, [$f['state']->id], 'Vou sair de férias');

    expect($request->status)->toBe('PENDING')
        ->and($request->Items)->toHaveCount(1)
        ->and($f['state']->fresh()->assigned_user_id)->toBe($f['internal']->id)
        ->and(transferService()->pendingCountFor($target))->toBe(1)
        ->and(transferService()->pendingCountFor($f['internal']))->toBe(0);
});

it('refuses invalid sends with a clear message', function () {
    $f      = closureScreenFixture();
    $target = transferTarget();
    $svc    = transferService();

    expect(fn () => $svc->send($f['internal'], $target, [], 'x'))->toThrow(ClosureTransferException::class, 'Selecione')
        ->and(fn () => $svc->send($f['internal'], $target, [$f['state']->id], '  '))->toThrow(ClosureTransferException::class, 'motivo')
        ->and(fn () => $svc->send($f['internal'], $f['internal'], [$f['state']->id], 'x'))->toThrow(ClosureTransferException::class, 'outro usuário')
        // destinatário sem permissão de Encerramento
        ->and(fn () => $svc->send($f['internal'], transferTarget(['closure_operator' => false]), [$f['state']->id], 'x'))->toThrow(ClosureTransferException::class, 'precisa ser do Encerramento')
        // destinatário sem empresa
        ->and(fn () => $svc->send($f['internal'], transferTarget(['company_id' => null]), [$f['state']->id], 'x'))->toThrow(ClosureTransferException::class, 'ter empresa')
        // Ordem que não é minha
        ->and(fn () => $svc->send($target, transferTarget(), [$f['state']->id], 'x'))->toThrow(ClosureTransferException::class, 'não está com você');

    // status que não permite transferir
    $f['state']->update(['operational_status' => ClosureOrderWorkflowService::STATUS_FINISHED]);

    expect(fn () => $svc->send($f['internal'], $target, [$f['state']->id], 'x'))->toThrow(ClosureTransferException::class, 'não permite transferir')
        ->and(ClosureTransferRequest::query()->count())->toBe(0);
});

it('refuses a second pending request for the same order and orders locked by an RI', function () {
    $f      = closureScreenFixture();
    $target = transferTarget();

    transferService()->send($f['internal'], $target, [$f['state']->id], 'primeira');

    expect(fn () => transferService()->send($f['internal'], transferTarget(), [$f['state']->id], 'segunda'))
        ->toThrow(ClosureTransferException::class, 'já tem uma transferência pendente');

    $g      = closureScreenFixture();
    $design = \App\Models\Service::query()->firstOrCreate(['folder' => 'desenho'], ['service' => 'Desenho', 'project' => true, 'construction' => false, 'icon' => 'ri-pencil-ruler-line']);
    ClosureInternalReturn::query()->create(['note_id' => $g['note']->id, 'design_service_id' => $design->uuid, 'reclaim_id' => 1, 'status' => 'SENT', 'cycle' => 1, 'sent_by' => $g['dispatcher']->id, 'sent_at' => now()]);

    expect(fn () => transferService()->send($g['internal'], $target, [$g['state']->id], 'x'))->toThrow(ClosureTransferException::class, 'travada por um RI');
});

it('moves the orders to the recipient on accept, with a TRANSFERRED event, and only the recipient can accept', function () {
    $f      = closureScreenFixture();
    $target = transferTarget();

    $request = transferService()->send($f['internal'], $target, [$f['state']->id], 'Mudei de carteira');

    expect(fn () => transferService()->accept($request, $f['internal']))->toThrow(ClosureTransferException::class, 'Somente o destinatário')
        ->and(fn () => transferService()->accept($request, $f['dispatcher']))->toThrow(ClosureTransferException::class, 'Somente o destinatário');

    $done  = transferService()->accept($request, $target);
    $state = $f['state']->fresh();

    expect($done->status)->toBe('ACCEPTED')
        ->and($done->Items->first()->result)->toBe('MOVED')
        ->and($state->assigned_user_id)->toBe($target->id)
        ->and((string) $state->current_holder_company_id)->toBe((string) $target->company_id)
        ->and($state->operational_status)->toBe(ClosureOrderWorkflowService::STATUS_ASSIGNED)
        ->and(ClosureOrderEvent::query()->where('closure_order_state_id', $state->id)->where('type', 'TRANSFERRED')->exists())->toBeTrue();

    expect(fn () => transferService()->accept($request->fresh(), $target))->toThrow(ClosureTransferException::class, 'já foi decidida');
});

it('skips an order that left the sender before the accept and records why', function () {
    $f      = closureScreenFixture();
    $target = transferTarget();

    $request = transferService()->send($f['internal'], $target, [$f['state']->id], 'x');

    // enquanto isso, a gestão devolveu a Ordem à pilha
    $f['state']->update(['assigned_user_id' => null, 'operational_status' => ClosureOrderWorkflowService::STATUS_AVAILABLE]);

    $done = transferService()->accept($request, $target);

    expect($done->status)->toBe('ACCEPTED')
        ->and($done->Items->first()->result)->toBe('SKIPPED')
        ->and($done->Items->first()->result_reason)->toContain('já não está com quem enviou')
        ->and($f['state']->fresh()->assigned_user_id)->toBeNull();
});

it('lets the recipient reject and the sender cancel, never the other way around', function () {
    $f      = closureScreenFixture();
    $target = transferTarget();

    $rejected = transferService()->send($f['internal'], $target, [$f['state']->id], 'a');

    expect(fn () => transferService()->reject($rejected, $f['internal']))->toThrow(ClosureTransferException::class, 'Somente o destinatário');

    $rejected = transferService()->reject($rejected, $target, ' não consigo ');

    expect($rejected->status)->toBe('REJECTED')->and($rejected->decision_note)->toBe('não consigo')
        ->and($f['state']->fresh()->assigned_user_id)->toBe($f['internal']->id);

    // pode enviar de novo depois de rejeitada; só quem enviou cancela
    $second = transferService()->send($f['internal'], $target, [$f['state']->id], 'b');

    expect(fn () => transferService()->cancel($second, $target))->toThrow(ClosureTransferException::class, 'Somente quem enviou');

    $second = transferService()->cancel($second, $f['internal']);

    expect($second->status)->toBe('CANCELLED')
        ->and(fn () => transferService()->accept($second->fresh(), $target))->toThrow(ClosureTransferException::class, 'já foi decidida')
        ->and(transferService()->pendingCountFor($target))->toBe(0);
});

it('drives the whole flow from the screens', function () {
    $f      = closureScreenFixture();
    $target = transferTarget();

    Livewire::actingAs($f['internal'])->test(SendModal::class)
        ->call('open', [$f['state']->id])
        ->assertSee($f['order']->ordem)
        ->call('send')->assertHasErrors(['toUserId'])
        ->set('toUserId', (string) $target->id)->call('send')->assertHasErrors(['reason'])
        ->set('reason', 'Passando a obra')->call('send')
        ->assertDispatchedBrowserEvent('notification:toast');

    $request = ClosureTransferRequest::query()->firstOrFail();

    // quem enviou vê em Enviadas e pode cancelar; não vê botões de aceitar
    Livewire::actingAs($f['internal'])->test(Center::class)
        ->call('setTab', 'sent')->assertSee('Cancelar transferência')->assertDontSee('Aceitar');

    Livewire::actingAs($target)->test(Center::class)
        ->assertSee($f['order']->ordem)->assertSee('Passando a obra')->assertSee('Aceitar')->assertSee('Rejeitar')
        ->call('askReject', $request->id)->set('rejectNote', 'sem tempo')->call('reject')
        ->assertDispatchedBrowserEvent('notification:toast', ['title' => 'Transferência rejeitada', 'text' => 'As Ordens continuam com quem enviou.', 'icon' => 'success']);

    expect($request->fresh()->status)->toBe('REJECTED');

    Livewire::actingAs($f['internal'])->test(SendModal::class)
        ->call('open', [$f['state']->id])->set('toUserId', (string) $target->id)->set('reason', 'de novo')->call('send');

    Livewire::actingAs($target)->test(Center::class)
        ->call('accept', ClosureTransferRequest::query()->where('status', 'PENDING')->value('id'))
        ->assertDispatchedBrowserEvent('notification:toast', ['title' => 'Transferência aceita', 'text' => '1 Ordem(ns) agora estão com você.', 'icon' => 'success']);

    expect($f['state']->fresh()->assigned_user_id)->toBe($target->id);
});

it('shows the destination list only with Encerramento users that have a company, never the sender', function () {
    $f      = closureScreenFixture();
    $target = transferTarget();
    $nobody = transferTarget(['closure_operator' => false, 'name' => 'Sem Acesso Encerramento']);

    Livewire::actingAs($f['internal'])->test(SendModal::class)
        ->call('open', [$f['state']->id])
        ->assertSee($target->name)
        ->assertDontSee('Sem Acesso Encerramento')
        ->assertDontSeeHtml('value="' . $f['internal']->id . '"');
});

it('offers Transferir in Minha atividade only to the owner of a transferable order', function () {
    $f    = closureScreenFixture();
    $list = new \App\Http\Livewire\Closure\Work\Index();

    $this->actingAs($f['internal']);
    expect($list->canTransfer($f['state']))->toBeTrue();

    $f['state']->update(['operational_status' => ClosureOrderWorkflowService::STATUS_FINISHED]);
    expect($list->canTransfer($f['state']->fresh()))->toBeFalse();

    $this->actingAs($f['dispatcher']);
    $f['state']->update(['operational_status' => ClosureOrderWorkflowService::STATUS_IN_PROGRESS]);
    expect($list->canTransfer($f['state']->fresh()))->toBeFalse();
});

it('refuses the transfer center without Encerramento access', function () {
    Livewire::actingAs(transferTarget(['closure_operator' => false]))->test(Center::class)->assertForbidden();
});
