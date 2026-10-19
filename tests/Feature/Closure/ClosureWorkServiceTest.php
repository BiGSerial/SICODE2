<?php

use App\Models\Closure\ClosureWorkMessage;
use App\Services\Closure\{ClosureIssueWorkflowService, ClosureWorkException, ClosureWorkService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

require_once __DIR__ . '/ClosureScreenFixture.php';

uses(RefreshDatabase::class);

it('resolves access levels per user and note', function () {
    $f    = closureCollabFixture();
    $work = app(ClosureWorkService::class);

    expect($work->accessLevel($f['note'], $f['dispatcher']))->toBe(ClosureWorkService::LEVEL_DISPATCHER)
        ->and($work->accessLevel($f['note'], $f['internal']))->toBe(ClosureWorkService::LEVEL_INTERNAL)
        ->and($work->accessLevel($f['note'], $f['partnerUser']))->toBe(ClosureWorkService::LEVEL_PARTNER)
        ->and($work->canAccess($f['note'], $f['outsider']))->toBeFalse();

    $otherNote = \App\Models\Note::query()->create(['note' => 'OUTRA-' . fake()->unique()->numerify('#####'), 'type_note' => 2]);
    expect($work->canAccess($otherNote, $f['internal']))->toBeFalse()
        ->and($work->canAccess($otherNote, $f['partnerUser']))->toBeFalse();
});

it('builds the work overview with orders, open issues and last message', function () {
    Notification::fake();
    $f                                                       = closureCollabFixture();
    $second                                                  = closureCollabExtraOrder($f, 'IN_PROGRESS');
    ['category' => $category, 'subcategory' => $subcategory] = closureIssueTaxonomy();
    $work                                                    = app(ClosureWorkService::class);

    app(ClosureIssueWorkflowService::class)->requestToPartner($f['state'], $category, $subcategory, $f['company'], $f['dispatcher'], 'Falta MIGO.');
    $work->postMessage($f['note'], $f['internal'], 'Nota interna.', ClosureWorkMessage::VISIBILITY_INTERNAL_ONLY);

    $overview = $work->overview($f['note'], $f['dispatcher']);

    expect($overview['note']['number'])->toBe($f['note']->note)
        ->and($overview['note']['type'])->toBe('EP')
        ->and($overview['company']['name'])->toBe($f['company']->name)
        ->and($overview['orders'])->toHaveCount(2)
        ->and(collect($overview['orders'])->firstWhere('order_id', $f['order']->id)['open_issues'])->toBe(1)
        ->and(collect($overview['orders'])->firstWhere('order_id', $f['order']->id)['assignee']['id'])->toBe($f['internal']->id)
        ->and(collect($overview['orders'])->firstWhere('order_id', $second->order_id)['status_sist'])->toBe('LIB CNPA')
        ->and($overview['open_issues'])->toHaveCount(1)
        ->and($overview['open_issues'][0]['destination'])->toBe($f['company']->name)
        ->and($overview['last_message']->body)->toBe('Nota interna.');
});

it('restricts the partner overview to its own items and visible messages', function () {
    Notification::fake();
    $f                                                       = closureCollabFixture();
    ['category' => $category, 'subcategory' => $subcategory] = closureIssueTaxonomy();
    $work                                                    = app(ClosureWorkService::class);
    app(ClosureIssueWorkflowService::class)->requestToPartner($f['state'], $category, $subcategory, $f['company'], $f['dispatcher'], 'Falta MIGO.');
    $other = closureCollabExtraOrder($f);
    $other->update(['current_holder_company_id' => \App\Models\Company::query()->create(['name' => 'Outra', 'email' => fake()->unique()->safeEmail()])->id]);
    $work->postMessage($f['note'], $f['internal'], 'Sigilo interno.', ClosureWorkMessage::VISIBILITY_INTERNAL_ONLY);

    $overview = $work->overview($f['note'], $f['partnerUser']);

    expect($overview['level'])->toBe(ClosureWorkService::LEVEL_PARTNER)
        ->and(collect($overview['orders'])->pluck('order_id')->all())->toBe([$f['order']->id])
        ->and($overview['priority_pending'])->toBeNull()
        ->and($overview['last_message']->body)->not->toBe('Sigilo interno.')
        ->and(fn () => $work->overview($f['note'], $f['outsider']))->toThrow(ClosureWorkException::class, 'acesso');
});

it('lists every order of the note, including undispatched ones, with type and competence keys', function () {
    $f        = closureScreenFixture();
    $undisp   = \App\Models\Order::query()->create(['note_id' => $f['note']->id, 'ordem' => '4099' . fake()->unique()->numerify('#######'), 'statusSist' => 'LIB', 'descricao' => 'Sem estado']);
    $canceled = \App\Models\Order::query()->create(['note_id' => $f['note']->id, 'ordem' => '4098' . fake()->unique()->numerify('#######'), 'canceled' => true]);
    $work     = app(ClosureWorkService::class);

    $rows = collect($work->overview($f['note'], $f['dispatcher'])['orders']);

    expect($rows)->toHaveCount(3)
        ->and($rows->firstWhere('order_id', $undisp->id))->toMatchArray(['has_state' => false, 'state_id' => null, 'operational_status' => null, 'description' => 'Sem estado', 'ops_total' => 0])
        ->and($rows->firstWhere('order_id', $undisp->id))->toHaveKeys(['type', 'competence', 'ops_confirmed'])
        ->and($rows->firstWhere('order_id', $f['order']->id)['has_state'])->toBeTrue()
        ->and($rows->pluck('order_id'))->not->toContain($canceled->id)
        ->and(collect($work->overview($f['note'], $f['dispatcher'], true)['orders']))->toHaveCount(4)
        ->and(collect($work->overview($f['note'], $f['internal'])['orders']))->toHaveCount(3);
});

it('lets the dispatcher open a note with no states and keeps the partner on its own orders', function () {
    $f    = closureScreenFixture();
    $work = app(ClosureWorkService::class);
    $bare = \App\Models\Note::query()->create(['note' => 'SEM-' . fake()->unique()->numerify('#####'), 'type_note' => 1]);
    \App\Models\Order::query()->create(['note_id' => $bare->id, 'ordem' => '4097' . fake()->unique()->numerify('#######')]);

    expect($work->accessLevel($bare, $f['dispatcher']))->toBe(ClosureWorkService::LEVEL_DISPATCHER)
        ->and($work->overview($bare, $f['dispatcher'])['orders'])->toHaveCount(1)
        ->and($work->canAccess($bare, $f['outsider']))->toBeFalse();
});

it('shows the partner only the orders with its own states', function () {
    $f     = closureScreenFixture();
    $other = \App\Models\Order::query()->create(['note_id' => $f['note']->id, 'ordem' => '4096' . fake()->unique()->numerify('#######')]);
    $rows  = collect(app(ClosureWorkService::class)->overview($f['note'], $f['partnerUser'])['orders']);

    expect($rows->pluck('order_id')->all())->toBe([$f['partnerState']->order_id])
        ->and($rows->pluck('order_id'))->not->toContain($other->id);
});
