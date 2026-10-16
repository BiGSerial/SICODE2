<?php

use App\Http\Livewire\Closure\Works\Show;
use App\Models\Closure\{ClosureIssue, ClosureWorkMessage};
use App\Services\Closure\{ClosureIssueWorkflowService, ClosureWorkService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Notification, Storage};
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

uses(RefreshDatabase::class);

function closurePartnerIssue(array $f, bool $second = false): ClosureIssue
{
    $category    = \App\Models\Closure\ClosureIssueCategory::query()->create(['name' => 'Doc ' . fake()->unique()->numerify('###')]);
    $subcategory = \App\Models\Closure\ClosureIssueSubcategory::query()->create(['closure_issue_category_id' => $category->id, 'name' => 'Sub ' . fake()->unique()->numerify('###')]);

    return app(ClosureIssueWorkflowService::class)->requestToPartner($second ? $f['partnerState'] : $f['state'], $category, $subcategory, $f['company'], $f['dispatcher'], 'Falta MIGO.');
}

it('serves the partner screen through the partner route and isolates other companies', function () {
    Notification::fake();
    $f      = closureScreenFixture();
    $otherU = \App\Models\User::factory()->create(['superadm' => false, 'admin' => false, 'company_id' => $f['other']->id]);

    expect($this->actingAs($f['partnerUser'])->get(route('partner.closure.work.show', $f['note']->id))->status())->not->toBeIn([302, 403, 404]);
    Livewire::actingAs($f['partnerUser'])->test(Show::class, ['noteId' => $f['note']->id])->assertSee($f['partnerState']->Order->ordem)->assertDontSee($f['order']->ordem);
    $this->actingAs($otherU)->get(route('partner.closure.work.show', $f['note']->id))->assertForbidden();
    $this->actingAs($f['outsider'])->get(route('closure.work.show', $f['note']->id))->assertForbidden();
});

it('never shows internal-only messages or internal blocks to the partner', function () {
    Notification::fake();
    $f    = closureScreenFixture();
    $work = app(ClosureWorkService::class);
    $work->postMessage($f['note'], $f['dispatcher'], 'SEGREDO INTERNO', ClosureWorkMessage::VISIBILITY_INTERNAL_ONLY);
    $work->postMessage($f['note'], $f['dispatcher'], 'Aviso público', ClosureWorkMessage::VISIBILITY_WITH_PARTNER);
    closurePartnerIssue($f);

    Livewire::actingAs($f['partnerUser'])->test(Show::class, ['noteId' => $f['note']->id])
        ->assertSee('Aviso público')
        ->assertDontSee('SEGREDO INTERNO')
        ->assertDontSee('Enviar à parceira para prioridade')
        ->assertDontSee('Prioridade da obra')
        ->assertDontSee('Enviar à parceira para resolver')
        ->assertDontSee('Finalizar obra');
});

it('forces partner messages to be visible and blocks internal actions', function () {
    Notification::fake();
    $f = closureScreenFixture();

    Livewire::actingAs($f['partnerUser'])->test(Show::class, ['noteId' => $f['note']->id])
        ->set('chatBody', 'Documento enviado')
        ->set('chatVisibility', 'INTERNAL_ONLY')
        ->call('postMessage')
        ->assertSee('Documento enviado');

    expect(ClosureWorkMessage::query()->firstOrFail()->visibility)->toBe('WITH_PARTNER');

    Livewire::actingAs($f['partnerUser'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('askFinish', 'all')->assertForbidden();
    Livewire::actingAs($f['partnerUser'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('selectStateForStatus', $f['state']->id)->assertForbidden();
    Livewire::actingAs($f['partnerUser'])->test(Show::class, ['noteId' => $f['note']->id])
        ->set('priorityReason', 'x')->call('requestPriority')->assertForbidden();
});

it('lets the partner respond to its issue with an attachment', function () {
    Notification::fake();
    Storage::fake('public');
    Storage::fake('local');
    $f     = closureScreenFixture();
    $issue = closurePartnerIssue($f);

    Livewire::actingAs($f['partnerUser'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('startIssueAction', $issue->id, 'respond')
        ->call('confirmIssueAction')
        ->assertHasErrors(['actingMessage'])
        ->set('actingMessage', 'Documento anexado.')
        ->set('actingAttachments', [UploadedFile::fake()->create('comprovante.pdf', 20, 'application/pdf')])
        ->call('confirmIssueAction')
        ->assertHasNoErrors();

    expect($issue->fresh()->status)->toBe('RESPONDED')
        ->and(ClosureWorkMessage::query()->where('closure_issue_id', $issue->id)->where('author_role', 'PARTNER')->firstOrFail()->EvidenceFiles()->count())->toBe(1);
});

it('does not let the partner validate, return or act on another company issue', function () {
    Notification::fake();
    $f      = closureScreenFixture();
    $issue  = closurePartnerIssue($f);
    $issue2 = closurePartnerIssue($f, true);
    $issue2->update(['destination_company_id' => $f['other']->id]);

    Livewire::actingAs($f['partnerUser'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('startIssueAction', $issue->id, 'validate')->assertForbidden();
    Livewire::actingAs($f['partnerUser'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('startIssueAction', $issue2->id, 'respond')->assertStatus(404);
});
