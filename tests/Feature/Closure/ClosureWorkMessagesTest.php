<?php

use App\Models\Closure\{ClosureWorkMessage};
use App\Models\{Company, EvidenceFile, User};
use App\Services\Closure\{ClosureIssueWorkflowService, ClosureWorkException, ClosureWorkService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

require_once __DIR__ . '/ClosureScreenFixture.php';

uses(RefreshDatabase::class);

it('never shows internal-only messages to the partner', function () {
    Notification::fake();
    $f    = closureCollabFixture();
    $work = app(ClosureWorkService::class);

    $work->postMessage($f['note'], $f['internal'], 'Conversa interna.', ClosureWorkMessage::VISIBILITY_INTERNAL_ONLY);
    $work->postMessage($f['note'], $f['dispatcher'], 'Aviso para a parceira.', ClosureWorkMessage::VISIBILITY_WITH_PARTNER);

    expect($work->messages($f['note'], $f['partnerUser'])->pluck('body')->all())->toBe(['Aviso para a parceira.'])
        ->and($work->messages($f['note'], $f['internal'])->pluck('body')->all())->toBe(['Conversa interna.', 'Aviso para a parceira.'])
        ->and($work->messages($f['note'], $f['dispatcher']))->toHaveCount(2)
        ->and(ClosureWorkMessage::query()->visibleToPartner()->pluck('body')->all())->toBe(['Aviso para a parceira.']);
});

it('lets the partner write only visible messages and blocks users without access', function () {
    Notification::fake();
    $f    = closureCollabFixture();
    $work = app(ClosureWorkService::class);

    $message = $work->postMessage($f['note'], $f['partnerUser'], 'Documento enviado.', ClosureWorkMessage::VISIBILITY_WITH_PARTNER);

    expect($message->author_role)->toBe(ClosureWorkMessage::ROLE_PARTNER)
        ->and($message->author_company_id)->toBe($f['company']->id)
        ->and(fn () => $work->postMessage($f['note'], $f['partnerUser'], 'Sigilo.', ClosureWorkMessage::VISIBILITY_INTERNAL_ONLY))
        ->toThrow(ClosureWorkException::class, 'só pode enviar')
        ->and(fn () => $work->postMessage($f['note'], $f['outsider'], 'Oi.'))->toThrow(ClosureWorkException::class, 'acesso')
        ->and(fn () => $work->messages($f['note'], $f['outsider']))->toThrow(ClosureWorkException::class, 'acesso')
        ->and(fn () => $work->postMessage($f['note'], $f['internal'], '   '))->toThrow(ClosureWorkException::class, 'Escreva');
});

it('hides messages of another company issue from a different partner of the same note', function () {
    Notification::fake();
    $f      = closureCollabFixture();
    $work   = app(ClosureWorkService::class);
    $other  = Company::query()->create(['name' => 'Outra parceira', 'email' => fake()->unique()->safeEmail()]);
    $otherU = User::factory()->create(['superadm' => false, 'admin' => false, 'company_id' => $other->id]);
    $second = closureCollabExtraOrder($f);
    $second->update(['current_holder_company_id' => $other->id]);
    ['category' => $category, 'subcategory' => $subcategory] = closureIssueTaxonomy();

    $issue = app(ClosureIssueWorkflowService::class)->requestToPartner($f['state'], $category, $subcategory, $f['company'], $f['dispatcher'], 'Falta MIGO.');

    expect($work->messages($f['note'], $otherU)->pluck('closure_issue_id')->filter()->all())->toBe([])
        ->and($work->messages($f['note'], $f['partnerUser'])->pluck('closure_issue_id')->filter()->all())->toBe([$issue->id])
        ->and(fn () => $work->postMessage($f['note'], $otherU, 'Intrusa.', ClosureWorkMessage::VISIBILITY_WITH_PARTNER, null, $issue))
        ->toThrow(ClosureWorkException::class, 'não pertence à sua empresa');
});

it('mirrors issue transitions in the work chat as partner-visible messages', function () {
    Notification::fake();
    $f                                                       = closureCollabFixture();
    ['category' => $category, 'subcategory' => $subcategory] = closureIssueTaxonomy();
    $workflow                                                = app(ClosureIssueWorkflowService::class);

    $issue = $workflow->requestToPartner($f['state'], $category, $subcategory, $f['company'], $f['dispatcher'], 'Falta MIGO.', 'Favor regularizar a MIGO.');
    $workflow->respond($issue, $f['company'], $f['partnerUser'], 'MIGO regularizada.');

    $messages = ClosureWorkMessage::query()->where('closure_issue_id', $issue->id)->orderBy('id')->get();

    expect($messages->pluck('body')->all())->toBe(['Favor regularizar a MIGO.', 'MIGO regularizada.'])
        ->and($messages->pluck('author_role')->all())->toBe([ClosureWorkMessage::ROLE_DISPATCHER, ClosureWorkMessage::ROLE_PARTNER])
        ->and($messages->pluck('visibility')->unique()->all())->toBe([ClosureWorkMessage::VISIBILITY_WITH_PARTNER])
        ->and($messages->last()->author_company_id)->toBe($f['company']->id)
        ->and($messages->last()->note_id)->toBe($f['note']->id);
});

it('attaches evidence files to a message through the existing morph', function () {
    Notification::fake();
    $f       = closureCollabFixture();
    $message = app(ClosureWorkService::class)->postMessage($f['note'], $f['internal'], 'Segue anexo.');

    $message->EvidenceFiles()->create([
        'user_id' => $f['internal']->id, 'original_name' => 'a.pdf', 'stored_name' => 'a', 'disk' => 'local', 'path' => 'x/a.pdf',
        'mime'    => 'application/pdf', 'extension' => 'pdf', 'size' => 1, 'sha256' => str_repeat('a', 64), 'uploaded_at' => now(), 'origin' => 'CLOSURE_WORK_MESSAGE',
    ]);

    expect($message->fresh()->EvidenceFiles)->toHaveCount(1)
        ->and(EvidenceFile::query()->first()->evidenciable)->toBeInstanceOf(ClosureWorkMessage::class);
});
