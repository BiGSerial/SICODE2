<?php

use App\Http\Livewire\Partner\ClosureIssues\Detail;
use App\Models\Closure\{ClosureIssue, ClosureWorkMessage};
use App\Models\User;
use App\Services\Closure\{ClosureIssueWorkflowException, ClosureIssueWorkflowService};
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Notification, Storage};
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

// Tela da pendência na visão da parceira: ler o pedido completo e devolver um retorno rico (por exigência + mensagem + evidências).

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Storage::fake('public');
    Storage::fake('local');
});

/** @param array<int, string> $requirements */
function detailIssue(array $f, array $requirements = ['Enviar a ART assinada', 'Foto da placa do poste'], bool $blocking = true): ClosureIssue
{
    ['category' => $c, 'subcategory' => $s] = closureIssueTaxonomy();

    return app(ClosureIssueWorkflowService::class)->requestToPartner($f['state'], $c, $s, $f['company'], $f['dispatcher'], 'Faltam documentos da obra.', null, $blocking, $requirements);
}

function allDone(int $count = 2): array
{
    return array_fill(0, $count, ['status' => 'DONE', 'note' => '']);
}

// ---------------------------------------------------------------- serviço

it('stores the answers per requirement, the message and the evidences of the partner reply', function () {
    $f     = closureScreenFixture();
    $issue = detailIssue($f);

    app(ClosureIssueWorkflowService::class)->respond(
        $issue,
        $f['company'],
        $f['partnerUser'],
        'Segue tudo.',
        [['status' => 'DONE', 'note' => ''], ['status' => 'UNABLE', 'note' => 'Poste inacessível, chuva.']],
        [UploadedFile::fake()->create('art.pdf', 20, 'application/pdf'), UploadedFile::fake()->image('placa.jpg')],
    );

    $event = $issue->fresh()->Events()->where('type', 'RESPONDED')->firstOrFail();

    expect($issue->fresh()->status)->toBe('RESPONDED')
        ->and($event->meta['message'])->toBe('Segue tudo.')
        ->and($event->meta['attachments'])->toBe(2)
        ->and($event->meta['answers'])->toHaveCount(2)
        ->and($event->meta['answers'][0])->toMatchArray(['index' => 1, 'text' => 'Enviar a ART assinada', 'status' => 'DONE', 'note' => null])
        ->and($event->meta['answers'][1])->toMatchArray(['index' => 2, 'status' => 'UNABLE', 'note' => 'Poste inacessível, chuva.'])
        ->and(ClosureWorkMessage::query()->where('closure_issue_id', $issue->id)->where('author_role', 'PARTNER')->firstOrFail()->EvidenceFiles()->count())->toBe(2);
});

it('requires every requirement answered, with a reason when not done, and accepts a reply with only the answers', function () {
    $f       = closureScreenFixture();
    $service = app(ClosureIssueWorkflowService::class);

    $issue = detailIssue($f);

    expect(fn () => $service->respond($issue, $f['company'], $f['partnerUser'], 'x', [['status' => 'DONE', 'note' => '']]))->toThrow(ClosureIssueWorkflowException::class, 'item 2')
        ->and(fn () => $service->respond($issue, $f['company'], $f['partnerUser'], 'x', [['status' => 'DONE'], ['status' => 'NA', 'note' => ' ']]))->toThrow(ClosureIssueWorkflowException::class, 'Explique o item 2')
        ->and(fn () => $service->respond($issue, $f['company'], $f['partnerUser'], 'x', [['status' => 'DONE'], ['status' => 'INVENTADO']]))->toThrow(ClosureIssueWorkflowException::class, 'item 2');

    expect($issue->fresh()->status)->toBe('REQUESTED');

    // só as respostas, sem mensagem: vale, e o chat recebe o resumo
    $service->respond($issue, $f['company'], $f['partnerUser'], '', allDone());

    $chat = ClosureWorkMessage::query()->where('closure_issue_id', $issue->id)->where('author_role', 'PARTNER')->firstOrFail();

    expect($issue->fresh()->status)->toBe('RESPONDED')
        ->and($chat->body)->toContain('1. Atendido')->toContain('2. Atendido');
});

it('keeps legacy replies (message only) working and still needs something to say', function () {
    $f       = closureScreenFixture();
    $service = app(ClosureIssueWorkflowService::class);

    expect(fn () => $service->respond(detailIssue($f, blocking: false), $f['company'], $f['partnerUser'], '  '))->toThrow(ClosureIssueWorkflowException::class, 'Informe o retorno');

    $service->respond($issue = detailIssue($f, blocking: false), $f['company'], $f['partnerUser'], 'Resposta simples.');

    expect($issue->fresh()->status)->toBe('RESPONDED')
        ->and($issue->fresh()->Events()->where('type', 'RESPONDED')->first()->meta)->toBe(['message' => 'Resposta simples.']);

    // pedido sem exigências: a mensagem basta
    $service->respond($semReq = detailIssue($f, [], false), $f['company'], $f['partnerUser'], 'Feito.', allDone(0));
    expect($semReq->fresh()->status)->toBe('RESPONDED');
});

// ---------------------------------------------------------------- tela

it('lets the partner read everything the Encerramento asked, with dates and the time between stages', function () {
    $f     = closureScreenFixture();
    $issue = detailIssue($f);

    Livewire::actingAs($f['partnerUser'])->test(Detail::class, ['issueId' => $issue->id])
        ->assertSee('O que o Encerramento pediu')
        ->assertSee('Faltam documentos da obra.')
        ->assertSee('Enviar a ART assinada')
        ->assertSee('Foto da placa do poste')
        ->assertSee('Aguardando seu retorno')
        ->assertSee('Sim — a Ordem só segue após o seu retorno')
        ->assertSee('Histórico desta pendência')
        ->assertSee('Enviada à parceira')
        ->assertSee('Seu retorno')
        ->assertSee('Enviar retorno');
});

it('shows the return-for-correction reason prominently when the issue comes back', function () {
    $f     = closureScreenFixture();
    $issue = detailIssue($f);
    $svc   = app(ClosureIssueWorkflowService::class);
    $svc->respond($issue, $f['company'], $f['partnerUser'], 'Primeira tentativa.', allDone());
    $svc->returnForCorrection($issue->fresh(), $f['dispatcher'], 'A ART está sem assinatura do responsável técnico.');

    Livewire::actingAs($f['partnerUser'])->test(Detail::class, ['issueId' => $issue->id])
        ->assertSee('Devolvida para correção')
        ->assertSee('A ART está sem assinatura do responsável técnico.')
        ->assertSee('Ciclo 2')
        ->assertSee('Enviar retorno');
});

it('submits a rich reply from the screen and then shows it read-only', function () {
    $f     = closureScreenFixture();
    $issue = detailIssue($f);

    Livewire::actingAs($f['partnerUser'])->test(Detail::class, ['issueId' => $issue->id])
        ->set('answers.0.status', 'DONE')
        ->set('answers.1.status', 'NA')
        ->call('submit')
        ->assertHasErrors('form')
        ->set('answers.1.note', 'A placa já estava fotografada no ciclo anterior.')
        ->set('message', 'Tudo conferido.')
        ->set('attachments', [UploadedFile::fake()->create('art.pdf', 20, 'application/pdf')])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Retorno enviado')
        ->assertSee('Não se aplica')
        ->assertSee('A placa já estava fotografada no ciclo anterior.')
        ->assertSee('Tudo conferido.')
        ->assertDontSee('Enviar retorno')
        ->assertSee('art.pdf');

    expect($issue->fresh()->status)->toBe('RESPONDED');
});

it('rejects attachments with a forbidden format or size', function () {
    $f     = closureScreenFixture();
    $issue = detailIssue($f, []);

    Livewire::actingAs($f['partnerUser'])->test(Detail::class, ['issueId' => $issue->id])
        ->set('message', 'ok')
        ->set('attachments', [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')])
        ->assertHasErrors('attachments.0')->assertSet('attachments', []);

    Livewire::actingAs($f['partnerUser'])->test(Detail::class, ['issueId' => $issue->id])
        ->set('message', 'ok')
        ->set('attachments', [UploadedFile::fake()->create('grande.pdf', 12000, 'application/pdf')])
        ->assertHasErrors('attachments.0')->assertSet('attachments', [])->assertSee('passa de 10 MB');

    expect($issue->fresh()->status)->toBe('REQUESTED');
});

/** A tela devolve "não encontrado" (404) para pendência fora do escopo do usuário; no teste do Livewire isso sobe como exceção. */
function isNotFoundOrForbidden(callable $open): bool
{
    try {
        $open();
    } catch (Throwable $e) {
        for ($current = $e; $current; $current = $current->getPrevious()) {
            if ($current instanceof ModelNotFoundException || $current instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                return true;
            }
        }
    }

    return false;
}

it('hides the issue of another company from the partner', function () {
    $f     = closureScreenFixture();
    $issue = detailIssue($f);
    $other = User::factory()->create(['superadm' => false, 'admin' => false, 'company_id' => $f['other']->id]);

    expect(isNotFoundOrForbidden(fn () => Livewire::actingAs($other)->test(Detail::class, ['issueId' => $issue->id])))->toBeTrue()
        ->and(isNotFoundOrForbidden(fn () => Livewire::actingAs($f['outsider'])->test(Detail::class, ['issueId' => $issue->id])))->toBeTrue()
        // controle: o usuário da empresa destinatária abre normalmente
        ->and(isNotFoundOrForbidden(fn () => Livewire::actingAs($f['partnerUser'])->test(Detail::class, ['issueId' => $issue->id])))->toBeFalse();
});

it('lets the superadm only observe: sees the request but has no form and cannot reply for the partner', function () {
    $f     = closureScreenFixture();
    $issue = detailIssue($f);
    $super = User::factory()->create(['superadm' => true, 'admin' => false]);

    $component = Livewire::actingAs($super)->test(Detail::class, ['issueId' => $issue->id])
        ->assertSee('Modo observador')
        ->assertSee('Enviar a ART assinada')
        ->assertDontSee('Enviar retorno');

    $component->set('message', 'Tentando responder pela parceira')->call('submit')->assertForbidden();

    expect($issue->fresh()->status)->toBe('REQUESTED');
});

it('does not let the partner reply twice or reply to a resolved issue', function () {
    $f     = closureScreenFixture();
    $issue = detailIssue($f, []);

    $component = Livewire::actingAs($f['partnerUser'])->test(Detail::class, ['issueId' => $issue->id])
        ->set('message', 'Feito.')->call('submit')->assertHasNoErrors();

    $component->set('message', 'De novo.')->call('submit')->assertStatus(422);

    expect($issue->fresh()->Events()->where('type', 'RESPONDED')->count())->toBe(1);
});

it('tells the partner what to do next and tracks the answered items', function () {
    $f     = closureScreenFixture();
    $issue = detailIssue($f);

    $component = Livewire::actingAs($f['partnerUser'])->test(Detail::class, ['issueId' => $issue->id])
        ->assertSee('É a sua vez')->assertSee('Seu retorno')->assertSee('0 de 2 item(ns) respondido(s)')
        ->set('answers.0.status', 'DONE')->assertSee('1 de 2 item(ns) respondido(s)');

    app(ClosureIssueWorkflowService::class)->respond($issue, $f['company'], $f['partnerUser'], 'ok', allDone());

    Livewire::actingAs($f['partnerUser'])->test(Detail::class, ['issueId' => $issue->id])
        ->assertSee('Agora é com o Encerramento')->assertDontSee('É a sua vez');
});

it('links the evidences to the reply event, names them with note and order and keeps the original name', function () {
    $f     = closureScreenFixture();
    $issue = detailIssue($f);

    app(ClosureIssueWorkflowService::class)->respond($issue, $f['company'], $f['partnerUser'], 'Segue.', allDone(), [UploadedFile::fake()->create('ART assinada.pdf', 20, 'application/pdf')]);

    $event = $issue->fresh()->Events()->where('type', 'RESPONDED')->latest('id')->first();
    $file  = \App\Models\EvidenceFile::query()->firstOrFail();

    expect($file->origin)->toBe(ClosureIssueWorkflowService::REPLY_ORIGIN_PREFIX . $event->id)
        ->and($file->original_name)->toBe('ART assinada.pdf')
        ->and($file->stored_name)->toContain(strtolower($f['note']->note))->toContain($f['order']->ordem)
        ->and($file->disk)->not->toBeNull();

    Livewire::actingAs($f['partnerUser'])->test(Detail::class, ['issueId' => $issue->id])->assertSee('Evidências deste retorno (1)')->assertSee('ART assinada.pdf');
});

it('discards a forbidden attachment right away and says why', function () {
    $f     = closureScreenFixture();
    $issue = detailIssue($f);

    Livewire::actingAs($f['partnerUser'])->test(Detail::class, ['issueId' => $issue->id])
        ->set('attachments', [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'), UploadedFile::fake()->create('ok.pdf', 10, 'application/pdf')])
        ->assertSee('formato não permitido')->assertSee('1 arquivo(s) pronto(s) para enviar');
});
