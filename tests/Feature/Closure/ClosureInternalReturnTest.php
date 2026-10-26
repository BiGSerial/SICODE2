<?php

use App\Models\Closure\{ClosureInternalReturn, ClosureOrderEvent};
use App\Models\{Reclaim, Service, Viability};
use App\Notifications\SystemNotification;
use App\Services\Closure\{ClosureDispatchException, ClosureDispatchService, ClosureEventLabels, ClosureInternalReturnException, ClosureInternalReturnService, ClosureIssueWorkflowService, ClosureOrderWorkflowException, ClosureOrderWorkflowService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Notification};

require_once __DIR__ . '/ClosureScreenFixture.php';

// Contrato do RI (Retorno Interno com o Desenho): docs/encerramento/CONTRATO_RI_ENCERRAMENTO.md.

uses(RefreshDatabase::class);

// O notificador enfileira em `jobs` (inexistente no banco de teste): como nos demais testes do módulo, as notificações são simuladas.
beforeEach(fn () => Notification::fake());

/** Obra com 2 Ordens ativas, viabilidade e o serviço Desenho. */
function riFixture(): array
{
    $f = closureCollabFixture();

    $f['design']      = Service::query()->firstOrCreate(['folder' => 'desenho'], ['service' => 'Desenho', 'project' => true, 'construction' => false, 'icon' => 'ri-pencil-ruler-line']);
    $f['viability']   = Viability::query()->create(['note_id' => $f['note']->id, 'company_id' => $f['company']->id, 'user_id' => $f['internal']->id, 'status' => 6]);
    $f['secondState'] = closureCollabExtraOrder($f);
    $f['service']     = app(ClosureInternalReturnService::class);
    $f['workflow']    = app(ClosureOrderWorkflowService::class);

    return $f;
}

function riSend(array $f, string $message = 'Projeto divergente do executado.'): ClosureInternalReturn
{
    return $f['service']->send($f['note'], $f['dispatcher'], $message);
}

/** Conclui o Reclaim SEM disparar o observer (UPDATE em massa), como fazem alguns comandos. */
function riCompleteReclaimSilently(ClosureInternalReturn $ri): void
{
    DB::table('reclaims')->where('id', $ri->reclaim_id)->update(['completed' => true, 'completed_at' => '2026-10-23 09:00:00']);
}

/** Mensagem de recusa por trava (ou null quando a ação não foi barrada pela trava). */
function riLockMessage(callable $action): ?string
{
    try {
        $action();
    } catch (ClosureOrderWorkflowException $e) {
        return str_contains($e->getMessage(), 'travada') ? $e->getMessage() : null;
    }

    return null;
}

/** Cada ação que muda a Ordem, com argumentos válidos (a trava age antes das regras de status). */
function riActions(array $f): array
{
    $w = $f['workflow'];
    $s = $f['state'];

    return [
        'claim'         => fn () => $w->claim($s, $f['internal']),
        'assign'        => fn () => $w->assign($s, $f['company'], $f['internal'], $f['dispatcher'], 'x'),
        'transfer'      => fn () => $w->transfer($s, $f['company'], $f['internal'], $f['dispatcher'], 'motivo'),
        'returnToPool'  => fn () => $w->returnToPool($s, $f['internal'], 'motivo'),
        'start'         => fn () => $w->start($s, $f['internal']),
        'waitForReturn' => fn () => $w->waitForReturn($s, $f['internal'], 'motivo'),
        'setPriority'   => fn () => $w->setPriority($s, true, $f['dispatcher']),
        'changeStatus'  => fn () => $w->changeStatus($s, 'PAUSED', $f['internal'], 'motivo'),
        'finish'        => fn () => $w->finish($s, $f['internal']),
        'blockForIssue' => fn () => $w->blockForIssue($s, $f['dispatcher'], 1, 'motivo'),
        'blockingIssue' => function () use ($f) {
            ['category' => $c, 'subcategory' => $sub] = closureIssueTaxonomy();
            app(ClosureIssueWorkflowService::class)->open($f['state'], $c, $sub, $f['company'], $f['dispatcher'], 'x', true);
        },
    ];
}

// ---------------------------------------------------------------- enviar

it('sends an RI reproducing the Reclaim flow and records it on every active Order of the note', function () {
    $f  = riFixture();
    $ri = riSend($f);

    $reclaim = Reclaim::query()->find($ri->reclaim_id);

    expect($ri->status)->toBe(ClosureInternalReturn::STATUS_SENT)
        ->and($ri->cycle)->toBe(1)
        ->and($ri->closed_at)->toBeNull()
        ->and($ri->isActive())->toBeTrue()
        ->and($ri->design_service_id)->toBe($f['design']->uuid)
        ->and($reclaim->note_id)->toBe($f['note']->id)
        ->and($reclaim->service_id)->toBe($f['design']->uuid)
        ->and($reclaim->completed)->toBeFalse()
        ->and($reclaim->Comments()->count())->toBe(1)
        ->and($reclaim->Comments()->first()->message)->toBe('Projeto divergente do executado.')
        ->and($f['viability']->fresh()->status)->toBe(11)
        ->and($f['viability']->Reclaims()->pluck('reclaims.id')->all())->toBe([$reclaim->id]);

    $events = ClosureOrderEvent::query()->where('type', 'RI_SENT')->get();

    expect($events)->toHaveCount(2)
        ->and($events->pluck('batch_id')->unique())->toHaveCount(1)
        ->and($events->pluck('order_id')->sort()->values()->all())->toBe(collect([$f['order']->id, $f['secondState']->order_id])->sort()->values()->all())
        ->and($events->first()->meta['internal_return_id'])->toBe($ri->id)
        ->and(ClosureEventLabels::label('RI_SENT'))->toBe('RI enviado ao Desenho');

    // a trava é sobreposta: status e posse não mudaram
    expect($f['state']->fresh()->operational_status)->toBe('AVAILABLE')
        ->and($f['state']->fresh()->assigned_user_id)->toBe($f['internal']->id);
});

it('refuses to send an RI without permission, comment, active Orders or viability', function () {
    $f = riFixture();

    expect(fn () => $f['service']->send($f['note'], $f['outsider'], 'x'))->toThrow(ClosureInternalReturnException::class, 'permissão')
        ->and(fn () => $f['service']->send($f['note'], $f['dispatcher'], '   '))->toThrow(ClosureInternalReturnException::class, 'comentário');

    // um operador (closure.work) pode enviar
    expect($f['service']->send($f['note'], $f['internal'], 'ok')->isActive())->toBeTrue();
});

it('refuses when there are no active Orders, no viability, or a canceled note', function () {
    $f = riFixture();
    $f['state']->update(['operational_status' => 'CLOSED']);
    $f['secondState']->update(['operational_status' => 'CANCELLED']);

    expect(fn () => riSend($f))->toThrow(ClosureInternalReturnException::class, 'Ordens ativas');

    $g = riFixture();
    $g['viability']->delete();
    expect(fn () => riSend($g))->toThrow(ClosureInternalReturnException::class, 'viabilidade');

    $h = riFixture();
    $h['note']->update(['canceled' => true]);
    expect(fn () => riSend($h))->toThrow(ClosureInternalReturnException::class, 'cancelada');
});

it('allows one active RI per note and respects a Reclaim already running at the Desenho', function () {
    $f = riFixture();
    riSend($f);

    expect(fn () => riSend($f))->toThrow(ClosureInternalReturnException::class, 'RI ativo');

    // Reclaim do Desenho já em andamento (aberto por outro fluxo) também bloqueia
    $g = riFixture();
    Reclaim::create(['note_id' => $g['note']->id, 'service_id' => $g['design']->uuid]);

    expect(fn () => riSend($g))->toThrow(ClosureInternalReturnException::class, 'em andamento');
});

it('creates nothing when the send fails midway (atomic)', function () {
    $f = riFixture();
    $f['viability']->delete();

    expect(fn () => riSend($f))->toThrow(ClosureInternalReturnException::class);
    expect(Reclaim::query()->count())->toBe(0)
        ->and(ClosureInternalReturn::query()->count())->toBe(0)
        ->and(ClosureOrderEvent::query()->where('type', 'RI_SENT')->count())->toBe(0);
});

// ---------------------------------------------------------------- matriz ação × estado

it('refuses every Order action while the RI is active, on every Order of the note', function () {
    $f = riFixture();

    // livre: nenhuma ação é barrada pela trava
    foreach (riActions($f) as $name => $action) {
        expect(riLockMessage($action))->toBeNull($name . ' (livre)');
    }

    $f = riFixture();
    riSend($f);

    foreach (riActions($f) as $name => $action) {
        expect(riLockMessage($action))->not->toBeNull($name . ' (travada)');
    }

    expect(riLockMessage(riActions($f)['claim']))->toContain('RI (Retorno Interno)');

    // vale para a outra Ordem da Nota
    expect(riLockMessage(fn () => $f['workflow']->claim($f['secondState'], $f['internal'])))->toContain('travada');

    // e para Ordem que entra na Nota durante o RI
    $late = closureCollabExtraOrder($f);
    expect(riLockMessage(fn () => $f['workflow']->claim($late, $f['internal'])))->toContain('travada');
});

it('lets the exempt flows through the lock so it never traps an Order', function () {
    $f = riFixture();
    $f['state']->update(['operational_status' => 'WAITING_PARTNER', 'current_holder_type' => 'SYSTEM', 'current_holder_user_id' => null]);
    $f['secondState']->update(['operational_status' => 'DEPOSIT_BLOCKED']);
    riSend($f);

    // pendência resolvida durante o RI devolve a Ordem ao ponto anterior
    $restored = $f['workflow']->restoreAfterIssue($f['state'], $f['dispatcher'], 1, [
        'operational_status'     => 'IN_PROGRESS',
        'current_holder_type'    => 'INTERNAL_USER',
        'assigned_user_id'       => $f['internal']->id,
        'current_holder_user_id' => $f['internal']->id,
    ]);

    // liberação de depósito idem
    $released = $f['workflow']->releaseDepositByPartner($f['secondState'], $f['dispatcher'], 1, 'IN_PROGRESS');

    expect($restored->operational_status)->toBe('IN_PROGRESS')
        ->and($released->operational_status)->toBe('IN_PROGRESS');

    // seguem travadas para qualquer outra ação
    expect(riLockMessage(fn () => $f['workflow']->start($restored, $f['internal'])))->toContain('travada');
});

it('still lets the SAP sync close or cancel a locked Order', function () {
    $f = riFixture();
    riSend($f);

    $f['order']->update(['statusSist' => 'ENTE']);
    $f['secondState']->Order->update(['canceled' => true]);

    expect($f['workflow']->syncFromSap($f['state']))->toBe('CLOSED')
        ->and($f['workflow']->syncFromSap($f['secondState']))->toBe('CANCELLED');
});

it('allows a non-blocking issue during the RI but not a blocking one', function () {
    $f = riFixture();
    riSend($f);
    ['category' => $c, 'subcategory' => $s] = closureIssueTaxonomy();

    $issue = app(ClosureIssueWorkflowService::class)->open($f['state'], $c, $s, $f['company'], $f['dispatcher'], 'Informativa', false);

    expect($issue->blocking)->toBeFalse()
        ->and(riLockMessage(fn () => app(ClosureIssueWorkflowService::class)->open($f['secondState'], $c, $s, $f['company'], $f['dispatcher'], 'Bloqueante', true)))->toContain('travada');
});

it('refuses to dispatch Orders of a locked note', function () {
    $f = riFixture();
    riSend($f);

    expect(fn () => app(ClosureDispatchService::class)->dispatch($f['note'], [$f['order']->id], $f['company'], null, $f['dispatcher']))
        ->toThrow(ClosureDispatchException::class, 'travada');
});

// ---------------------------------------------------------------- retorno

it('returns the RI when the Reclaim is completed, once, and unlocks the Orders', function () {
    $f  = riFixture();
    $ri = riSend($f);

    // Reclaim ainda aberto: nada muda
    expect($f['service']->syncFromReclaim($ri))->toBeFalse()
        ->and($ri->fresh()->isActive())->toBeTrue();

    riCompleteReclaimSilently($ri);

    expect($f['service']->syncFromReclaim($ri))->toBeTrue();

    $ri = $ri->fresh();

    expect($ri->status)->toBe(ClosureInternalReturn::STATUS_RETURNED)
        ->and($ri->returned_at->toDateTimeString())->toBe('2026-10-23 09:00:00')
        ->and($ri->closed_at)->not->toBeNull()
        ->and($ri->isActive())->toBeFalse()
        ->and(ClosureOrderEvent::query()->where('type', 'RI_RETURNED')->count())->toBe(2)
        ->and(riLockMessage(fn () => $f['workflow']->claim($f['state'], $f['internal'])))->toBeNull();

    // idempotente
    expect($f['service']->syncFromReclaim($ri))->toBeFalse()
        ->and(ClosureOrderEvent::query()->where('type', 'RI_RETURNED')->count())->toBe(2);
});

it('returns the RI through the Reclaim observer when the Desenho saves it as completed', function () {
    $f  = riFixture();
    $ri = riSend($f);

    Reclaim::query()->find($ri->reclaim_id)->update(['completed' => true, 'completed_at' => now()]);

    expect($ri->fresh()->status)->toBe(ClosureInternalReturn::STATUS_RETURNED);
});

it('never breaks the Reclaim save of another module when the closure sync fails', function () {
    $f  = riFixture();
    $ri = riSend($f);

    // Serviço de sincronização que falha ao tratar o Reclaim salvo.
    app()->instance(ClosureInternalReturnService::class, new class () extends ClosureInternalReturnService {
        public function handleReclaimSaved(Reclaim $reclaim): void
        {
            throw new RuntimeException('falha simulada');
        }
    });

    $reclaim = Reclaim::query()->find($ri->reclaim_id);
    $reclaim->update(['completed' => true, 'completed_at' => now()]);

    expect($reclaim->fresh()->completed)->toBeTrue()
        ->and($ri->fresh()->isActive())->toBeTrue();

    // a varredura corrige depois
    expect((new ClosureInternalReturnService())->syncAll())->toMatchArray(['checked' => 1, 'returned' => 1]);
});

it('returns RIs through the safety sweep command and reports orphans', function () {
    $f  = riFixture();
    $ri = riSend($f);
    riCompleteReclaimSilently($ri);

    $this->artisan('closure:sync-internal-returns')->expectsOutputToContain('1 ativos verificados, 1 retornados')->assertExitCode(0);

    expect($ri->fresh()->status)->toBe(ClosureInternalReturn::STATUS_RETURNED);

    $g      = riFixture();
    $orphan = riSend($g);
    DB::table('reclaims')->where('id', $orphan->reclaim_id)->delete();

    $this->artisan('closure:sync-internal-returns')->expectsOutputToContain('sem Reclaim')->assertExitCode(0);

    expect($orphan->fresh()->isActive())->toBeTrue();
});

it('does not lock again when the Desenho reopens the Reclaim, and a new RI is a new cycle', function () {
    $f       = riFixture();
    $ri      = riSend($f);
    $reclaim = Reclaim::query()->find($ri->reclaim_id);

    $reclaim->update(['completed' => true, 'completed_at' => now()]);
    expect($ri->fresh()->status)->toBe(ClosureInternalReturn::STATUS_RETURNED);

    // Desenho reabre: avisa, mas NÃO re-trava
    $reclaim->update(['completed' => false, 'completed_at' => null]);

    expect($ri->fresh()->status)->toBe(ClosureInternalReturn::STATUS_RETURNED)
        ->and($f['service']->activeForNote($f['note']->id))->toBeNull()
        ->and(riLockMessage(fn () => $f['workflow']->claim($f['state'], $f['internal'])))->toBeNull()
        ->and(collect(Notification::sent($f['dispatcher'], SystemNotification::class))->map(fn ($n) => $n->toDatabase($f['dispatcher'])['extras']['event'])->all())
        ->toContain('closure.internal_return_reopened');

    // reaberto e ainda em andamento no Desenho: novo RI só depois de concluir; então é o ciclo 2
    expect(fn () => riSend($f))->toThrow(ClosureInternalReturnException::class, 'em andamento');

    $reclaim->update(['completed' => true, 'completed_at' => now()]);

    expect(riSend($f)->cycle)->toBe(2);
});

// ---------------------------------------------------------------- cancelar

it('lets only management cancel, which unlocks the Orders without touching the Reclaim or viability', function () {
    $f  = riFixture();
    $ri = riSend($f);

    expect(fn () => $f['service']->cancel($ri, $f['internal'], 'x'))->toThrow(ClosureInternalReturnException::class, 'gestão')
        ->and(fn () => $f['service']->cancel($ri, $f['dispatcher'], ' '))->toThrow(ClosureInternalReturnException::class, 'motivo');

    $cancelled = $f['service']->cancel($ri, $f['dispatcher'], 'Enviado por engano.');
    $reclaim   = Reclaim::query()->find($ri->reclaim_id);

    expect($cancelled->status)->toBe(ClosureInternalReturn::STATUS_CANCELLED)
        ->and($cancelled->cancel_reason)->toBe('Enviado por engano.')
        ->and($cancelled->cancelled_by)->toBe($f['dispatcher']->id)
        ->and($cancelled->closed_at)->not->toBeNull()
        ->and($cancelled->isActive())->toBeFalse()
        // nada de outra atividade foi alterado: Reclaim segue aberto e a viabilidade em "Criado RI"
        ->and($reclaim->completed)->toBeFalse()
        ->and($f['viability']->fresh()->status)->toBe(11)
        ->and($reclaim->Comments()->count())->toBe(2)
        ->and(ClosureOrderEvent::query()->where('type', 'RI_CANCELLED')->count())->toBe(2)
        ->and(riLockMessage(fn () => $f['workflow']->claim($f['state'], $f['internal'])))->toBeNull();

    // já encerrado: não cancela de novo, e o Reclaim em andamento impede um novo RI duplicado
    expect(fn () => $f['service']->cancel($ri, $f['dispatcher'], 'de novo'))->toThrow(ClosureInternalReturnException::class, 'já foi encerrado')
        ->and(fn () => riSend($f))->toThrow(ClosureInternalReturnException::class, 'em andamento');

    // o Desenho concluindo depois não reabre nada
    $reclaim->update(['completed' => true, 'completed_at' => now()]);
    expect($cancelled->fresh()->status)->toBe(ClosureInternalReturn::STATUS_CANCELLED);
});

// ---------------------------------------------------------------- consulta e avisos

it('exposes the lock for the UI and per-note lookups for lists', function () {
    $f = riFixture();

    expect($f['service']->lockFor($f['state']))->toBeNull()
        ->and($f['service']->activeForNotes([$f['note']->id]))->toHaveCount(0);

    $ri = riSend($f);

    $lock = $f['service']->lockFor($f['state']->fresh());

    expect($lock['reason'])->toBe('RI com o Desenho')
        ->and($lock['internal_return_id'])->toBe($ri->id)
        ->and($f['service']->lockFor($f['secondState']->fresh()))->not->toBeNull()
        ->and($f['service']->activeForNotes([$f['note']->id, 999999])->keys()->all())->toBe([$f['note']->id]);
});

it('notifies managers and assignees, never the author, on send, return and cancel', function () {
    $f = riFixture();

    $events = fn ($user) => collect(Notification::sent($user, SystemNotification::class))->map(fn ($n) => $n->toDatabase($user)['extras']['event'])->all();

    $ri = riSend($f);

    expect($events($f['internal']))->toBe(['closure.internal_return_sent'])
        ->and($events($f['dispatcher']))->toBe([])
        ->and($events($f['outsider']))->toBe([]);

    riCompleteReclaimSilently($ri);
    $f['service']->syncFromReclaim($ri);

    expect($events($f['internal']))->toBe(['closure.internal_return_sent', 'closure.internal_return_returned'])
        ->and($events($f['dispatcher']))->toBe(['closure.internal_return_returned']);
});
