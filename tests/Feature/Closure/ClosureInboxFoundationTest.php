<?php

use App\Models\Closure\{ClosureInternalReturn, ClosureIssue, ClosureStatusUserRoute};
use App\Models\{Service, ServiceUser, User};
use App\Services\Closure\{ClosureArea, ClosureStatusUserTracker};
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Gate};

require_once __DIR__ . '/ClosureScreenFixture.php';

// Base das caixas de pendência (Fase 1): áreas, gates por vínculo de Service e rastreio da data do status usuário.

uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow('2026-10-23 10:00:00'));
afterEach(fn () => Carbon::setTestNow());

function inboxUser(array $flags = []): User
{
    return User::factory()->create(array_merge(['superadm' => false, 'admin' => false, 'closure_manager' => false, 'closure_operator' => false], $flags));
}

function inboxLink(User $user, string $folder, array $columns = ['service' => true, 'dispatch' => false]): void
{
    $service = Service::query()->firstOrCreate(['folder' => $folder], ['service' => ucfirst($folder), 'project' => true, 'construction' => true, 'icon' => 'ri-folder-line']);

    ServiceUser::query()->create(array_merge(['user_id' => $user->id, 'service_id' => $service->uuid], $columns));
}

it('knows which areas have an inbox and their service folders', function () {
    expect(ClosureArea::inboxAreas())->toBe([ClosureArea::MEASUREMENT, ClosureArea::SUPERVISION])
        ->and(ClosureArea::hasInbox(ClosureArea::MEASUREMENT))->toBeTrue()
        ->and(ClosureArea::hasInbox(ClosureArea::PARTNER))->toBeFalse()
        ->and(ClosureArea::hasInbox(ClosureArea::CLOSURE))->toBeFalse()
        ->and(ClosureArea::serviceFolder(ClosureArea::MEASUREMENT))->toBe('pagamento')
        ->and(ClosureArea::serviceFolder(ClosureArea::SUPERVISION))->toBe('fiscalizacao')
        ->and(ClosureArea::serviceFolder('DESIGN'))->toBeNull()
        ->and(ClosureArea::label(ClosureArea::MEASUREMENT))->toBe('Medição')
        ->and(ClosureArea::inboxOptions())->toBe(['MEASUREMENT' => 'Medição', 'SUPERVISION' => 'Fiscalização']);
});

it('grants each inbox only to users linked to that area Service (dispatch or service flag)', function () {
    $measurement = inboxUser();
    inboxLink($measurement, 'pagamento');

    $supervisionDispatcher = inboxUser();
    inboxLink($supervisionDispatcher, 'fiscalizacao', ['service' => false, 'dispatch' => true]);

    $outsider = inboxUser();
    inboxLink($outsider, 'desenho');

    $unlinked = inboxUser();

    expect($measurement->can('closure.inbox.measurement'))->toBeTrue()
        ->and($measurement->can('closure.inbox.measurement.respond'))->toBeTrue()
        ->and($measurement->can('closure.inbox.supervision'))->toBeFalse()
        ->and($supervisionDispatcher->can('closure.inbox.supervision'))->toBeTrue()
        ->and($supervisionDispatcher->can('closure.inbox.supervision.respond'))->toBeTrue()
        ->and($supervisionDispatcher->can('closure.inbox.measurement'))->toBeFalse()
        // Desenho não tem caixa; sem vínculo não vê nada.
        ->and($outsider->can('closure.inbox.measurement'))->toBeFalse()
        ->and($outsider->can('closure.inbox.supervision'))->toBeFalse()
        ->and($unlinked->can('closure.inbox.measurement'))->toBeFalse();
});

it('lets closure managers, admins and superadmins see every inbox', function () {
    foreach (['closure_manager', 'admin', 'superadm'] as $flag) {
        $user = inboxUser([$flag => true]);

        expect($user->can('closure.inbox.measurement'))->toBeTrue($flag)
            ->and($user->can('closure.inbox.supervision'))->toBeTrue($flag);
    }

    // operador do Encerramento, sozinho, não ganha caixa de outra área
    expect(inboxUser(['closure_operator' => true])->can('closure.inbox.measurement'))->toBeFalse();
});

it('keeps the new gates registered', function () {
    foreach (['closure.inbox.measurement', 'closure.inbox.measurement.respond', 'closure.inbox.supervision', 'closure.inbox.supervision.respond'] as $ability) {
        expect(Gate::has($ability))->toBeTrue($ability);
    }
});

it('defaults existing and new issues to a partner issue from the closure team', function () {
    $f = closureWorkflowFixture();
    $c = \App\Models\Closure\ClosureIssueCategory::query()->create(['name' => 'Cat', 'slug' => 'cat-' . fake()->unique()->numerify('###'), 'active' => true]);
    $s = \App\Models\Closure\ClosureIssueSubcategory::query()->create(['closure_issue_category_id' => $c->id, 'name' => 'Sub', 'slug' => 'sub', 'active' => true]);

    $issue = ClosureIssue::query()->create([
        'order_id'                     => $f['order']->id,
        'closure_order_state_id'       => $f['state']->id,
        'closure_issue_category_id'    => $c->id,
        'closure_issue_subcategory_id' => $s->id,
        'destination_company_id'       => $f['company']->id,
        'status'                       => ClosureIssue::STATUS_OPEN,
        'opened_at'                    => now(),
    ])->fresh();

    expect($issue->origin_area)->toBe(ClosureArea::CLOSURE)
        ->and($issue->destination_area)->toBe(ClosureArea::PARTNER)
        ->and($issue->kind)->toBe(ClosureIssue::KIND_ISSUE)
        ->and($issue->source)->toBe(ClosureIssue::SOURCE_MANUAL);
});

it('records the status user date on first sight as an estimate and again only when the prefix changes', function () {
    $f = closureWorkflowFixture();
    $f['order']->update(['statusUser' => 'JAND INVE BLOQ']);

    $summary = app(ClosureStatusUserTracker::class)->sync();
    $state   = $f['state']->fresh();

    expect($summary)->toMatchArray(['initialized' => 1, 'changed' => 0])
        ->and($state->status_user_seen)->toBe('JAND INVE')
        ->and($state->status_user_estimated)->toBeTrue()
        ->and($state->status_user_changed_at->toDateTimeString())->toBe('2026-10-23 10:00:00');

    // idempotente: nada muda sem mudança de status
    Carbon::setTestNow('2026-10-23 12:00:00');
    expect(app(ClosureStatusUserTracker::class)->sync())->toMatchArray(['initialized' => 0, 'changed' => 0])
        ->and($f['state']->fresh()->status_user_changed_at->toDateTimeString())->toBe('2026-10-23 10:00:00');

    // só o 3º token mudou: o prefixo é o mesmo, então a data não se move
    $f['order']->update(['statusUser' => 'JAND INVE LIBE']);
    expect(app(ClosureStatusUserTracker::class)->sync())->toMatchArray(['changed' => 0]);

    // prefixo mudou: nova data, deixa de ser estimativa
    Carbon::setTestNow('2026-10-23 14:00:00');
    $f['order']->update(['statusUser' => 'ENC PEND']);

    expect(app(ClosureStatusUserTracker::class)->sync())->toMatchArray(['changed' => 1]);

    $state = $f['state']->fresh();

    expect($state->status_user_seen)->toBe('ENC PEND')
        ->and($state->status_user_estimated)->toBeFalse()
        ->and($state->status_user_changed_at->toDateTimeString())->toBe('2026-10-23 14:00:00');
});

it('tracks an empty status user and ignores closed or cancelled activities', function () {
    $open = closureWorkflowFixture();
    $open['order']->update(['statusUser' => null]);

    $closed = closureWorkflowFixture();
    $closed['order']->update(['statusUser' => 'ENC PEND']);
    $closed['state']->update(['operational_status' => 'CLOSED']);

    $summary = app(ClosureStatusUserTracker::class)->sync();

    expect($summary['scanned'])->toBe(1)
        ->and($open['state']->fresh()->status_user_seen)->toBe('')
        ->and($closed['state']->fresh()->status_user_seen)->toBeNull();
});

it('runs the tracker through the artisan command and schedules it right after the SAP order sync', function () {
    $f = closureWorkflowFixture();
    $f['order']->update(['statusUser' => 'JAND INVE']);

    $this->artisan('closure:sync-status-user')->expectsOutputToContain('1 registradas pela 1ª vez')->assertExitCode(0);

    expect($f['state']->fresh()->status_user_seen)->toBe('JAND INVE');

    $events = collect(app(Schedule::class)->events())->filter(fn ($e) => str_contains((string) $e->command, 'closure:sync-status-user'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('55 5,8,10,12,14,16,20 * * *');
});

it('enforces one status user prefix per area route and exposes the RI scopes', function () {
    ClosureStatusUserRoute::query()->create(['area' => ClosureArea::MEASUREMENT, 'status_user_prefix' => 'JAND INVE']);

    expect(fn () => ClosureStatusUserRoute::query()->create(['area' => ClosureArea::SUPERVISION, 'status_user_prefix' => 'JAND INVE']))
        ->toThrow(\Illuminate\Database\QueryException::class);

    $f   = closureWorkflowFixture();
    $svc = 'cccccccc-cccc-cccc-cccc-cccccccccccc';

    $sent = ClosureInternalReturn::query()->create(['note_id' => $f['order']->note_id, 'design_service_id' => $svc, 'status' => ClosureInternalReturn::STATUS_SENT, 'sent_at' => now()]);

    expect($sent->isActive())->toBeTrue()
        ->and(ClosureInternalReturn::query()->active()->count())->toBe(1);

    $sent->update(['status' => ClosureInternalReturn::STATUS_RETURNED, 'returned_at' => now(), 'closed_at' => now()]);

    expect($sent->fresh()->isActive())->toBeFalse()
        ->and(ClosureInternalReturn::query()->active()->count())->toBe(0);
});
