<?php

use App\Models\Closure\{ClosureIssue, ClosureIssueCategory, ClosureIssueSubcategory};
use App\Models\Company;
use App\Services\Closure\{ClosureIssueWorkflowException, ClosureIssueWorkflowService, ClosureOrderWorkflowService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

require_once __DIR__ . '/ClosureScreenFixture.php';

uses(RefreshDatabase::class);

it('classifies, blocks, and restores the activity after resolving a pending issue', function () {
    ['actor' => $actor, 'state' => $state, 'category' => $category, 'subcategory' => $subcategory, 'destination' => $destination] = closureIssueFixture(true);
    $workflow                                                                                                                     = app(ClosureIssueWorkflowService::class);

    expect(fn () => $workflow->open($state, $category, $subcategory, $destination, $actor, null))
        ->toThrow(ClosureIssueWorkflowException::class, 'exige uma descrição');

    $issue = $workflow->open($state, $category, $subcategory, $destination, $actor, 'Falta documento de aceite.');

    expect($issue->status)->toBe(ClosureIssue::STATUS_OPEN)
        ->and($issue->cycle)->toBe(1)
        ->and($issue->Events()->sole()->type)->toBe('OPENED')
        ->and($state->fresh()->operational_status)->toBe(ClosureOrderWorkflowService::STATUS_WAITING_PARTNER)
        ->and($state->fresh()->LastEvent->type)->toBe(ClosureOrderWorkflowService::EVENT_ISSUE_BLOCKED);

    $requested = $workflow->request($issue, $actor, 'Favor enviar o comprovante.');
    $responded = $workflow->respond($requested, $destination, $actor, 'Comprovante anexado no atendimento.');
    $resolved  = $workflow->resolve($responded, $actor, 'Conferência concluída.');

    expect($resolved->status)->toBe(ClosureIssue::STATUS_RESOLVED)
        ->and($resolved->resolved_at)->not->toBeNull()
        ->and($resolved->Events()->count())->toBe(4)
        ->and($state->fresh()->operational_status)->toBe(ClosureOrderWorkflowService::STATUS_AVAILABLE)
        ->and($state->fresh()->LastEvent->type)->toBe(ClosureOrderWorkflowService::EVENT_ISSUE_RELEASED);
});

it('keeps one issue and increments its cycle when it is returned for correction', function () {
    ['actor' => $actor, 'state' => $state, 'category' => $category, 'subcategory' => $subcategory, 'destination' => $destination] = closureIssueFixture();
    $workflow                                                                                                                     = app(ClosureIssueWorkflowService::class);
    $issue                                                                                                                        = $workflow->open($state, $category, $subcategory, $destination, $actor, 'Dados divergentes.');
    $responded                                                                                                                    = $workflow->respond($workflow->request($issue, $actor), $destination, $actor, 'Ajuste realizado.');
    $returned                                                                                                                     = $workflow->returnForCorrection($responded, $actor, 'Documento ilegível.');

    expect($returned->id)->toBe($issue->id)
        ->and($returned->status)->toBe(ClosureIssue::STATUS_RETURNED)
        ->and($returned->cycle)->toBe(2)
        ->and($returned->Events()->reorder()->latest('id')->first()->meta['message'])->toBe('Documento ilegível.');
});

it('refuses taxonomy misuse, inactive taxonomy and an answer from another partner', function () {
    ['actor' => $actor, 'state' => $state, 'category' => $category, 'subcategory' => $subcategory, 'destination' => $destination] = closureIssueFixture();
    $workflow                                                                                                                     = app(ClosureIssueWorkflowService::class);
    $anotherCategory                                                                                                              = ClosureIssueCategory::query()->create(['name' => 'Outra categoria ' . fake()->unique()->numerify('###')]);
    $otherPartner                                                                                                                 = Company::query()->create(['name' => 'Outro parceiro', 'email' => fake()->unique()->safeEmail()]);

    expect(fn () => $workflow->open($state, $anotherCategory, $subcategory, $destination, $actor, null))
        ->toThrow(ClosureIssueWorkflowException::class, 'não pertence');

    $category->update(['active' => false]);
    expect(fn () => $workflow->open($state, $category, $subcategory, $destination, $actor, null))
        ->toThrow(ClosureIssueWorkflowException::class, 'inativa');
    $category->update(['active' => true]);

    $issue = $workflow->request($workflow->open($state, $category, $subcategory, $destination, $actor, null), $actor);
    expect(fn () => $workflow->respond($issue, $otherPartner, $actor, 'Resposta indevida.'))
        ->toThrow(ClosureIssueWorkflowException::class, 'destinatária');
});

it('preserves historical taxonomy and makes issue events immutable', function () {
    ['actor' => $actor, 'state' => $state, 'category' => $category, 'subcategory' => $subcategory, 'destination' => $destination] = closureIssueFixture();
    $issue                                                                                                                        = app(ClosureIssueWorkflowService::class)->open($state, $category, $subcategory, $destination, $actor, null);
    $category->update(['active' => false]);
    $subcategory->update(['active' => false]);

    expect($issue->fresh()->Category->active)->toBeFalse()
        ->and($issue->fresh()->Subcategory->active)->toBeFalse()
        ->and(fn () => $issue->Events()->first()->update(['type' => 'ALTERED']))->toThrow(LogicException::class, 'imutáveis');
});

it('opens and sends to the partner in one step, requiring a valid taxonomy', function () {
    Notification::fake();
    ['actor' => $actor, 'state' => $state, 'category' => $category, 'subcategory' => $subcategory, 'destination' => $destination] = closureIssueFixture(true);
    $workflow                                                                                                                     = app(ClosureIssueWorkflowService::class);
    $anotherCategory                                                                                                              = ClosureIssueCategory::query()->create(['name' => 'Categoria alheia ' . fake()->unique()->numerify('###')]);

    expect(fn () => $workflow->requestToPartner($state, $anotherCategory, $subcategory, $destination, $actor, 'Descrição.'))
        ->toThrow(ClosureIssueWorkflowException::class, 'não pertence')
        ->and(fn () => $workflow->requestToPartner($state, $category, $subcategory, $destination, $actor, null))
        ->toThrow(ClosureIssueWorkflowException::class, 'exige uma descrição');

    $issue = $workflow->requestToPartner($state, $category, $subcategory, $destination, $actor, 'Falta documento.', 'Favor enviar.');

    expect($issue->status)->toBe(ClosureIssue::STATUS_REQUESTED)
        ->and($issue->closure_issue_category_id)->toBe($category->id)
        ->and($issue->Events()->pluck('type')->all())->toBe(['OPENED', 'REQUESTED']);
});

it('ships the seeded closure taxonomy honoring requires_description', function () {
    $category = ClosureIssueCategory::query()->where('slug', 'material-migo-pendente')->firstOrFail();

    expect(ClosureIssueCategory::query()->count())->toBeGreaterThanOrEqual(7)
        ->and($category->Subcategories()->where('active', true)->count())->toBeGreaterThan(1)
        ->and(ClosureIssueSubcategory::query()->where('slug', 'outro-motivo')->firstOrFail()->requires_description)->toBeTrue()
        ->and(ClosureIssueSubcategory::query()->where('slug', 'migo-nao-realizada')->where('requires_description', false)->exists())->toBeTrue();
});
