<?php

use App\Http\Livewire\Closure\Works\Show;
use App\Models\Closure\{ClosureIssue, ClosureOrderState, ClosureWorkMessage};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Notification, Storage};
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

uses(RefreshDatabase::class);

function closureScreenTaxonomy(bool $requiresDescription = false): array
{
    $category    = \App\Models\Closure\ClosureIssueCategory::query()->create(['name' => 'Documento ' . fake()->unique()->numerify('###')]);
    $subcategory = \App\Models\Closure\ClosureIssueSubcategory::query()->create([
        'closure_issue_category_id' => $category->id,
        'name'                      => 'Comprovante ' . fake()->unique()->numerify('###'),
        'requires_description'      => $requiresDescription,
    ]);

    return compact('category', 'subcategory');
}

it('denies the work screen to users without access to the note', function () {
    $f = closureScreenFixture();

    $this->actingAs($f['outsider'])->get(route('closure.work.show', $f['note']->id))->assertForbidden();
    Livewire::actingAs($f['outsider'])->test(Show::class, ['noteId' => $f['note']->id])->assertForbidden();
});

it('authorizes the dispatcher and the assigned user on the route (auth only, access by note) and renders the component', function () {
    $f = closureScreenFixture();

    // A página completa depende de dados do layout (rodapé/bancos) ausentes no banco de teste; o que importa aqui é não ser 403/404/redirect.
    expect($this->actingAs($f['dispatcher'])->get(route('closure.work.show', $f['note']->id))->status())->not->toBeIn([302, 403, 404])
        ->and($this->actingAs($f['internal'])->get(route('closure.work.show', $f['note']->id))->status())->not->toBeIn([302, 403, 404]);

    Livewire::actingAs($f['dispatcher'])->test(Show::class, ['noteId' => $f['note']->id])->assertSee($f['note']->note)->assertSee($f['order']->ordem);
    Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])->assertSee($f['order']->ordem);
});

it('posts and lists chat messages with visibility chosen by the internal author', function () {
    Notification::fake();
    Storage::fake('public');
    $f = closureScreenFixture();

    Livewire::actingAs($f['dispatcher'])->test(Show::class, ['noteId' => $f['note']->id])
        ->set('chatBody', 'Conversa interna')
        ->set('chatVisibility', 'INTERNAL_ONLY')
        ->call('postMessage')
        ->set('chatBody', 'Aviso à parceira')
        ->set('chatVisibility', 'WITH_PARTNER')
        ->call('postMessage')
        ->assertSee('Conversa interna')
        ->assertSee('Aviso à parceira')
        ->assertDispatchedBrowserEvent('notification:toast');

    expect(ClosureWorkMessage::query()->orderBy('id')->pluck('visibility')->all())->toBe(['INTERNAL_ONLY', 'WITH_PARTNER']);
});

it('stores chat attachments through the evidence uploader', function () {
    Notification::fake();
    Storage::fake('public');
    Storage::fake('local');
    $f = closureScreenFixture();

    Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])
        ->set('chatBody', 'Segue anexo')
        ->set('chatAttachments', [UploadedFile::fake()->create('doc.pdf', 20, 'application/pdf')])
        ->call('postMessage')
        ->assertHasNoErrors();

    expect(ClosureWorkMessage::query()->firstOrFail()->EvidenceFiles()->count())->toBe(1);
});

it('requires category, subcategory and orders when sending to the partner', function () {
    Notification::fake();
    $f = closureScreenFixture();

    Livewire::actingAs($f['dispatcher'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('sendToPartner')
        ->assertHasErrors(['issueCategoryId', 'issueSubcategoryId', 'issueOrderIds', 'issueCompanyId']);

    expect(ClosureIssue::query()->count())->toBe(0);
});

it('requires a description when the subcategory demands it and then sends the issue', function () {
    Notification::fake();
    $f                                                       = closureScreenFixture();
    ['category' => $category, 'subcategory' => $subcategory] = closureScreenTaxonomy(true);

    $component = Livewire::actingAs($f['dispatcher'])->test(Show::class, ['noteId' => $f['note']->id])
        ->set('issueCategoryId', (string) $category->id)
        ->set('issueSubcategoryId', (string) $subcategory->id)
        ->set('issueOrderIds', [(string) $f['state']->id])
        ->set('issueCompanyId', (string) $f['company']->id)
        ->call('sendToPartner')
        ->assertHasErrors(['issueDescription']);

    expect(ClosureIssue::query()->count())->toBe(0);

    $component->set('issueDescription', 'Falta MIGO.')->call('sendToPartner')->assertHasNoErrors();

    $issue = ClosureIssue::query()->firstOrFail();
    expect($issue->status)->toBe('REQUESTED')
        ->and($issue->blocking)->toBeTrue()
        ->and($f['state']->fresh()->operational_status)->toBe('WAITING_PARTNER');
});

it('lets the dispatcher validate or return a responded issue', function () {
    Notification::fake();
    $f                                                       = closureScreenFixture();
    ['category' => $category, 'subcategory' => $subcategory] = closureScreenTaxonomy();
    $workflow                                                = app(\App\Services\Closure\ClosureIssueWorkflowService::class);
    $issue                                                   = $workflow->requestToPartner($f['state'], $category, $subcategory, $f['company'], $f['dispatcher'], 'Falta MIGO.');
    $workflow->respond($issue, $f['company'], $f['partnerUser'], 'Feito.');

    $component = Livewire::actingAs($f['dispatcher'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('startIssueAction', $issue->id, 'return')
        ->set('actingMessage', '')
        ->call('confirmIssueAction')
        ->assertHasErrors(['actingMessage'])
        ->set('actingMessage', 'Ainda divergente.')
        ->call('confirmIssueAction');

    expect($issue->fresh()->status)->toBe('RETURNED');

    $workflow->respond($issue->fresh(), $f['company'], $f['partnerUser'], 'Corrigido.');
    $component->call('startIssueAction', $issue->id, 'validate')->call('confirmIssueAction');

    expect($issue->fresh()->status)->toBe('VALIDATING');
});

it('refuses to finish a future-goal order and shows the refusal in the receipt', function () {
    Notification::fake();
    $f = closureScreenFixture();
    $f['order']->Operations()->create(['operacao' => '0020', 'status' => 'CONF', 'fimReal' => now()->toDateString()]);

    Livewire::actingAs($f['dispatcher'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('askFinish', 'all')
        ->call('confirmFinish')
        ->assertSet('finishReceipt.0.ok', false)
        ->assertSee('meta futura');

    expect($f['state']->fresh()->operational_status)->toBe('IN_PROGRESS');
});

it('finishes a finalizable order and records the receipt', function () {
    Notification::fake();
    $f = closureScreenFixture();

    Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('askFinish', (string) $f['state']->id)
        ->call('confirmFinish')
        ->assertSet('finishReceipt.0.ok', true);

    expect($f['state']->fresh()->operational_status)->toBe('FINISHED');
});

it('changes the status with confirmation and demands a reason when required', function () {
    Notification::fake();
    $f = closureScreenFixture();

    $component = Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('selectStateForStatus', $f['state']->id)
        ->set('statusTarget', 'PAUSED')
        ->call('prepareStatusChange')
        ->assertHasErrors(['statusReason'])
        ->assertSet('statusConfirming', false);

    expect($f['state']->fresh()->operational_status)->toBe('IN_PROGRESS');

    $component->set('statusReason', 'Aguardando material')->call('prepareStatusChange')->assertSet('statusConfirming', true);
    expect($f['state']->fresh()->operational_status)->toBe('IN_PROGRESS');

    $component->call('confirmStatusChange');
    expect($f['state']->fresh()->operational_status)->toBe('PAUSED');
});

it('does not let an internal user without ownership change status or finish', function () {
    Notification::fake();
    $f = closureScreenFixture();
    // outro usuário com acesso à Nota (atribuído a outra Ordem), mas não dono desta
    $order2 = \App\Models\Order::query()->create(['note_id' => $f['note']->id, 'ordem' => fake()->unique()->numerify('401#########'), 'statusSist' => 'LIB CNPA']);
    $other  = \App\Models\User::factory()->create(['superadm' => false, 'admin' => false, 'closure_manager' => false, 'closure_operator' => true]);
    ClosureOrderState::query()->create(['order_id' => $order2->id, 'production_id' => $f['state']->production_id, 'operational_status' => 'IN_PROGRESS', 'assigned_user_id' => $other->id, 'current_holder_user_id' => $other->id, 'current_holder_type' => 'INTERNAL_USER', 'entered_current_status_at' => now()]);

    Livewire::actingAs($other)->test(Show::class, ['noteId' => $f['note']->id])
        ->call('selectStateForStatus', $f['state']->id)
        ->set('statusTarget', 'PAUSED')->set('statusReason', 'x')
        ->call('prepareStatusChange')->call('confirmStatusChange')
        ->assertSee('Somente o responsável');

    expect($f['state']->fresh()->operational_status)->toBe('IN_PROGRESS');
});

it('requests priority and lets only the dispatcher decide', function () {
    Notification::fake();
    $f = closureScreenFixture();

    Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])
        ->set('priorityReason', 'Cliente cobrando')
        ->call('requestPriority');

    $request = \App\Models\Closure\ClosurePriorityRequest::query()->firstOrFail();
    expect($request->status)->toBe('PENDING');

    Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('decidePriority', $request->id, 'accept')->assertForbidden();

    Livewire::actingAs($f['dispatcher'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('decidePriority', $request->id, 'accept');

    expect($request->fresh()->status)->toBe('ACCEPTED')->and($f['state']->fresh()->priority)->toBeTrue();
});

it('rejects chat attachments outside the extension whitelist', function () {
    Notification::fake();
    Storage::fake('public');
    Storage::fake('local');
    $f = closureScreenFixture();

    Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])
        ->set('chatBody', 'Segue anexo')
        ->set('chatAttachments', [UploadedFile::fake()->create('x.html', 2, 'text/html'), UploadedFile::fake()->create('y.svg', 2, 'image/svg+xml')])
        ->call('postMessage')
        ->assertHasErrors(['chatAttachments.0', 'chatAttachments.1']);

    expect(ClosureWorkMessage::query()->count())->toBe(0);
});

it('re-checks access before deciding priority, even if the component level is stale', function () {
    Notification::fake();
    $f = closureScreenFixture();

    $component = Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])
        ->set('priorityReason', 'Cliente cobrando')
        ->call('requestPriority');
    $request = \App\Models\Closure\ClosurePriorityRequest::query()->firstOrFail();

    $dispatcherComponent = Livewire::actingAs($f['dispatcher'])->test(Show::class, ['noteId' => $f['note']->id]);
    $f['dispatcher']->forceFill(['closure_manager' => false])->save();

    $dispatcherComponent->call('decidePriority', $request->id, 'accept')->assertForbidden();

    expect($request->fresh()->status)->toBe('PENDING');
});
