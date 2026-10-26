<?php

use App\Models\Closure\{ClosureWorkMessage};
use App\Notifications\SystemNotification;
use App\Services\Closure\{ClosureIssueWorkflowService, ClosureNotifier, ClosurePriorityService, ClosureWorkService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, DB, Notification};

require_once __DIR__ . '/ClosureScreenFixture.php';

uses(RefreshDatabase::class);

/** @return array<int, array<string, mixed>> payloads enviados ao usuário */
function closureSentPayloads($user): array
{
    return Notification::sent($user, SystemNotification::class)->map(fn ($n) => $n->toDatabase($user))->all();
}

it('notifies the partner when an issue is requested and returned, with toast extras, never the author', function () {
    Notification::fake();
    $f                                                       = closureCollabFixture();
    ['category' => $category, 'subcategory' => $subcategory] = closureIssueTaxonomy();
    $workflow                                                = app(ClosureIssueWorkflowService::class);

    $issue = $workflow->requestToPartner($f['state'], $category, $subcategory, $f['company'], $f['dispatcher'], 'Falta MIGO.');

    $payloads = closureSentPayloads($f['partnerUser']);
    expect($payloads)->toHaveCount(1)
        ->and($payloads[0]['extras']['toast'])->toBeTrue()
        ->and($payloads[0]['extras']['event'])->toBe('closure.issue_requested')
        ->and($payloads[0]['extras']['dedupe'])->toContain('closure.issue_requested')
        ->and($payloads[0]['status'])->toBe(7)
        ->and(closureSentPayloads($f['dispatcher']))->toBe([])
        ->and(closureSentPayloads($f['internal']))->toBe([])
        ->and(closureSentPayloads($f['outsider']))->toBe([]);

    $responded = $workflow->respond($issue, $f['company'], $f['partnerUser'], 'Feito.');
    $workflow->returnForCorrection($responded, $f['dispatcher'], 'Ainda divergente.');

    expect(collect(closureSentPayloads($f['partnerUser']))->pluck('extras.event')->all())->toBe(['closure.issue_requested', 'closure.issue_returned']);
});

it('notifies issue opener and assignee when the partner responds, but not the partner', function () {
    Notification::fake();
    $f                                                       = closureCollabFixture();
    ['category' => $category, 'subcategory' => $subcategory] = closureIssueTaxonomy();
    $workflow                                                = app(ClosureIssueWorkflowService::class);
    $issue                                                   = $workflow->requestToPartner($f['state'], $category, $subcategory, $f['company'], $f['dispatcher'], 'Falta MIGO.');
    $f['state']->update(['assigned_user_id' => $f['internal']->id, 'current_holder_user_id' => $f['internal']->id]);

    $workflow->respond($issue->fresh(), $f['company'], $f['partnerUser'], 'Feito.');

    expect(collect(closureSentPayloads($f['dispatcher']))->pluck('extras.event')->all())->toBe(['closure.partner_responded'])
        ->and(collect(closureSentPayloads($f['internal']))->pluck('extras.event')->all())->toBe(['closure.partner_responded'])
        ->and(collect(closureSentPayloads($f['partnerUser']))->pluck('extras.event')->all())->toBe(['closure.issue_requested']);
});

it('deduplicates the same event for the same entity and recipient', function () {
    Notification::fake();
    $f                                                       = closureCollabFixture();
    ['category' => $category, 'subcategory' => $subcategory] = closureIssueTaxonomy();
    $issue                                                   = app(ClosureIssueWorkflowService::class)->requestToPartner($f['state'], $category, $subcategory, $f['company'], $f['dispatcher'], 'Falta MIGO.');
    $notifier                                                = app(ClosureNotifier::class);

    $notifier->issueRequestedToPartner($issue, $f['dispatcher']);
    $notifier->issueRequestedToPartner($issue, $f['dispatcher']);

    expect(closureSentPayloads($f['partnerUser']))->toHaveCount(1);

    Cache::flush();
    $notifier->issueRequestedToPartner($issue, $f['dispatcher']);
    expect(closureSentPayloads($f['partnerUser']))->toHaveCount(2);
});

it('does not notify when the surrounding transaction rolls back', function () {
    Notification::fake();
    $f                                                       = closureCollabFixture();
    ['category' => $category, 'subcategory' => $subcategory] = closureIssueTaxonomy();
    $workflow                                                = app(ClosureIssueWorkflowService::class);

    try {
        DB::transaction(function () use ($workflow, $f, $category, $subcategory) {
            $workflow->requestToPartner($f['state'], $category, $subcategory, $f['company'], $f['dispatcher'], 'Falta MIGO.');

            throw new RuntimeException('falha depois da transição');
        });
    } catch (RuntimeException) {
    }

    Notification::assertNothingSent();
});

it('routes chat notifications by visibility and never to the author', function () {
    Notification::fake();
    $f    = closureCollabFixture();
    $work = app(ClosureWorkService::class);

    $work->postMessage($f['note'], $f['dispatcher'], 'Só equipe.', ClosureWorkMessage::VISIBILITY_INTERNAL_ONLY);

    expect(closureSentPayloads($f['partnerUser']))->toBe([])
        ->and(closureSentPayloads($f['dispatcher']))->toBe([])
        ->and(closureSentPayloads($f['internal']))->toHaveCount(1);

    $work->postMessage($f['note'], $f['dispatcher'], 'Para todos.', ClosureWorkMessage::VISIBILITY_WITH_PARTNER);

    expect(closureSentPayloads($f['partnerUser']))->toHaveCount(1)
        ->and(closureSentPayloads($f['internal']))->toHaveCount(2);

    $work->postMessage($f['note'], $f['partnerUser'], 'Resposta da parceira.', ClosureWorkMessage::VISIBILITY_WITH_PARTNER);

    expect(closureSentPayloads($f['partnerUser']))->toHaveCount(1)
        ->and(closureSentPayloads($f['internal']))->toHaveCount(3)
        ->and(closureSentPayloads($f['outsider']))->toBe([]);
});

it('notifies dispatchers of priority requests and the requester of the decision', function () {
    Notification::fake();
    $f       = closureCollabFixture();
    $service = app(ClosurePriorityService::class);

    $request = $service->request($f['note'], 'Urgente.', $f['internal']);

    expect(collect(closureSentPayloads($f['dispatcher']))->pluck('extras.event')->all())->toBe(['closure.priority_requested'])
        ->and(closureSentPayloads($f['internal']))->toBe([])
        ->and(closureSentPayloads($f['partnerUser']))->toBe([]);

    $service->accept($request, $f['dispatcher']);

    $decided = closureSentPayloads($f['internal']);
    expect($decided)->toHaveCount(1)
        ->and($decided[0]['extras']['event'])->toBe('closure.priority_decided')
        ->and($decided[0]['title'])->toBe('Prioridade atendida')
        ->and(closureSentPayloads($f['dispatcher']))->toHaveCount(1);
});

it('notifies only the assignee on order assignment and falls back to the order detail link', function () {
    Notification::fake();
    $f = closureCollabFixture();

    app(ClosureNotifier::class)->orderAssigned($f['state']->fresh(), $f['dispatcher']);
    app(ClosureNotifier::class)->orderAssigned($f['state']->fresh(), $f['internal']);

    $payloads = closureSentPayloads($f['internal']);
    expect($payloads)->toHaveCount(1)
        ->and($payloads[0]['status'])->toBe(7)
        ->and($payloads[0]['link'])->toContain('/encerramento/obra/' . $f['order']->note_id);
});

it('notifies the new assignee when the workflow assigns or transfers an order', function () {
    Notification::fake();
    $f     = closureCollabFixture();
    $other = \App\Models\User::factory()->create(['superadm' => false, 'admin' => false, 'closure_operator' => true, 'company_id' => $f['company']->id]);

    app(\App\Services\Closure\ClosureOrderWorkflowService::class)
        ->transfer($f['state']->fresh(), $f['company'], $other, $f['dispatcher'], 'teste');

    $payloads = closureSentPayloads($other);
    expect($payloads)->toHaveCount(1)->and($payloads[0]['status'])->toBe(7);
});

it('builds notification links on the side each recipient works from', function () {
    $f       = closureScreenFixture();
    $service = new ReflectionMethod(\App\Services\Closure\ClosureNotifier::class, 'workLinkForNote');
    $service->setAccessible(true);
    $notifier = app(\App\Services\Closure\ClosureNotifier::class);

    $dispatch = $service->invoke($notifier, $f['note']->id, null, $f['dispatcher'], 'dispatch');
    $worker   = $service->invoke($notifier, $f['note']->id, null, $f['internal'], 'service');
    $fallback = $service->invoke($notifier, $f['note']->id, null, $f['internal'], 'dispatch'); // sem permissão de despacho → serviço

    expect($dispatch)->toContain('ctx=dispatch')->and($worker)->toContain('ctx=service')->and($fallback)->toContain('ctx=service');
});
