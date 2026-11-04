<?php

use App\Http\Livewire\Closure\Inbox\Index;
use App\Models\Closure\{ClosureIssue, ClosureIssueEvent};
use App\Services\Closure\{ClosureArea, ClosureIssueWorkflowException, ClosureIssueWorkflowService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

require_once __DIR__ . '/ClosureStatusMapInboxTest.php';

// Caixa autônoma: não leva ao Encerramento e a área resolve o despacho com fluxo próprio (retorno + validação do Encerramento).

uses(RefreshDatabase::class);

function areaIssue(array $f, string $area = ClosureArea::MEASUREMENT, string $status = ClosureIssue::STATUS_REQUESTED): ClosureIssue
{
    $taxonomy = closureIssueTaxonomy();

    return ClosureIssue::query()->create([
        'order_id'                     => $f['order']->id,
        'closure_order_state_id'       => $f['state']->id,
        'origin_area'                  => ClosureArea::CLOSURE,
        'closure_issue_category_id'    => $taxonomy['category']->id,
        'closure_issue_subcategory_id' => $taxonomy['subcategory']->id,
        'destination_area'             => $area,
        'status'                       => $status,
        'opened_by'                    => $f['dispatcher']->id,
        'description'                  => 'Conferir a medição',
        'opened_at'                    => now(),
        'requested_at'                 => now(),
    ]);
}

it('finalizes an area dispatch with the area return and leaves validation to the Encerramento', function () {
    Notification::fake();
    $f     = closureScreenFixture();
    $issue = areaIssue($f);
    $user  = mapActor(['closure_manager' => false]);
    mapService('pagamento', $user);

    Livewire::actingAs($user)->test(Index::class, ['area' => ClosureArea::MEASUREMENT, 'tab' => 'dispatched'])
        ->call('openIssue', $issue->id)
        ->assertSee('Finalizar solicitação')
        ->call('finalizeIssue')
        ->assertHasErrors('issueReply')
        ->set('issueReply', 'Medição conferida e lançada.')
        ->call('finalizeIssue')
        ->assertHasNoErrors()
        ->assertSet('issueId', null);

    $issue->refresh();
    $event = ClosureIssueEvent::query()->where('closure_issue_id', $issue->id)->where('type', 'RESPONDED')->first();

    expect($issue->status)->toBe(ClosureIssue::STATUS_RESPONDED)
        ->and($issue->responded_at)->not->toBeNull()
        ->and($event->origin)->toBe(ClosureIssueWorkflowService::ORIGIN_AREA)
        ->and($event->meta['message'])->toBe('Medição conferida e lançada.');
});

it('refuses to finalize issues of another area, partner issues and already answered ones', function () {
    $f    = closureScreenFixture();
    $user = mapActor(['closure_manager' => false]);
    mapService('pagamento', $user);

    $other = areaIssue($f, ClosureArea::SUPERVISION);

    Livewire::actingAs($user)->test(Index::class, ['area' => ClosureArea::MEASUREMENT, 'tab' => 'dispatched'])
        ->call('openIssue', $other->id)
        ->assertSet('issueId', null);

    $service = app(ClosureIssueWorkflowService::class);
    $partner = areaIssue($f, ClosureArea::PARTNER);
    $done    = areaIssue($f, ClosureArea::MEASUREMENT, ClosureIssue::STATUS_RESPONDED);

    expect(fn () => $service->respondByArea($partner, $user, 'x'))->toThrow(ClosureIssueWorkflowException::class)
        ->and(fn () => $service->respondByArea($done, $user, 'x'))->toThrow(ClosureIssueWorkflowException::class)
        ->and($other->fresh()->status)->toBe(ClosureIssue::STATUS_REQUESTED);
});

it('does not send the user to the Encerramento nor offer the status map configuration', function () {
    $user = mapActor(['closure_manager' => true]);
    mapService('pagamento', $user);

    Livewire::actingAs($user)->test(Index::class, ['area' => ClosureArea::MEASUREMENT])
        ->assertDontSee('Configurar status desta caixa')
        ->assertDontSee('Configurar agora')
        ->assertDontSee(route('closure.status_map'))
        ->assertDontSee('Abrir obra');
});

it('devolve a Ordem à Medição sem empresa parceira, pelo modal da obra aberto pelo atalho do menu', function () {
    Notification::fake();
    $f        = closureScreenFixture();
    $taxonomy = closureIssueTaxonomy();

    Livewire::withQueryParams(['devolver' => ClosureArea::MEASUREMENT, 'estado' => $f['state']->id])
        ->actingAs($f['dispatcher'])
        ->test(\App\Http\Livewire\Closure\Works\Show::class, ['noteId' => $f['note']->id])
        ->assertSet('issueArea', ClosureArea::MEASUREMENT)
        ->assertSet('partnerModal', true)
        ->assertSee('Devolver à Medição')
        ->set('issueCategoryId', (string) $taxonomy['category']->id)
        ->set('issueSubcategoryId', (string) $taxonomy['subcategory']->id)
        ->call('sendToPartner')
        ->assertHasNoErrors()
        ->assertSet('partnerModal', false);

    $issue = ClosureIssue::query()->latest('id')->first();

    expect($issue->destination_area)->toBe(ClosureArea::MEASUREMENT)
        ->and($issue->destination_company_id)->toBeNull()
        ->and($issue->status)->toBe(ClosureIssue::STATUS_REQUESTED);
});

it('recusa devolver para uma área sem caixa', function () {
    $f        = closureScreenFixture();
    $taxonomy = closureIssueTaxonomy();

    expect(fn () => app(ClosureIssueWorkflowService::class)->requestToArea($f['state'], ClosureArea::PARTNER, $taxonomy['category'], $taxonomy['subcategory'], $f['dispatcher'], 'x'))
        ->toThrow(ClosureIssueWorkflowException::class);
});
