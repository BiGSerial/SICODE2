<?php

use App\Exports\Closure\ClosureIssueDetailExport;
use App\Http\Livewire\Partner\ClosureIssues\{Detail, History, Index};
use App\Jobs\Closure\ExportPartnerClosureListJob;
use App\Models\Closure\ClosureIssue;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\Closure\{ClosureIssueWorkflowService, ClosurePartnerExporter};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Bus, Notification, Storage};
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

// Histórico de retornos da parceira + exportação (Excel) das telas dela.

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-10-25 10:00:00');
    Notification::fake();
    Storage::fake('public');
    Storage::fake('local');
});

afterEach(fn () => Carbon::setTestNow());

function histIssue(array $f, array $requirements = ['Enviar a ART'], $company = null, $state = null): ClosureIssue
{
    ['category' => $c, 'subcategory' => $s] = closureIssueTaxonomy();

    return app(ClosureIssueWorkflowService::class)->requestToPartner($state ?? $f['state'], $c, $s, $company ?? $f['company'], $f['dispatcher'], 'Faltam documentos.', null, false, $requirements);
}

function histReply(array $f, ClosureIssue $issue, string $note = 'Segue a ART.', array $files = [], $user = null, $company = null): ClosureIssue
{
    $answers = array_fill(0, count($issue->requirements ?? []), ['status' => 'DONE', 'note' => '']);

    return app(ClosureIssueWorkflowService::class)->respond($issue, $company ?? $f['company'], $user ?? $f['partnerUser'], $note, $answers, $files);
}

// ---------------------------------------------------------------- histórico

it('lists only the responses of the user company, with what happened after each one', function () {
    $f   = closureScreenFixture();
    $svc = app(ClosureIssueWorkflowService::class);

    $accepted = histIssue($f);
    histReply($f, $accepted, 'Resposta aceita.');
    $svc->resolve($accepted->fresh(), $f['dispatcher']);

    $returned = histIssue($f);
    histReply($f, $returned, 'Resposta devolvida.', [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]);
    $svc->returnForCorrection($returned->fresh(), $f['dispatcher'], 'Corrija.');

    $waiting = histIssue($f);
    histReply($f, $waiting, 'Resposta aguardando.');

    $notReplied = histIssue($f);

    $foreign   = histIssue($f, ['x'], $f['other']);
    $otherUser = User::factory()->create(['superadm' => false, 'admin' => false, 'company_id' => $f['other']->id]);
    histReply($f, $foreign, 'Resposta de OUTRA empresa.', [], $otherUser, $f['other']);

    Livewire::actingAs($f['partnerUser'])->test(History::class)
        ->assertSee('Resposta aceita.')->assertSee('Resposta devolvida.')->assertSee('Resposta aguardando.')
        ->assertDontSee('Resposta de OUTRA empresa.')
        ->assertSee('Aceita')->assertSee('Devolvida para correção')->assertSee('Aguardando validação')
        ->assertSee('1 anexo(s)')
        ->assertSee('Retornos enviados');

    expect($notReplied->fresh()->status)->toBe('REQUESTED');
});

it('filters the history by note, current status and period', function () {
    $f   = closureScreenFixture();
    $one = histIssue($f);
    Carbon::setTestNow('2026-10-10 09:00:00');
    histReply($f, $one, 'Resposta antiga.');
    Carbon::setTestNow('2026-10-25 10:00:00');
    $two = histIssue($f);
    histReply($f, $two, 'Resposta nova.');
    app(ClosureIssueWorkflowService::class)->resolve($two->fresh(), $f['dispatcher']);

    $component = Livewire::actingAs($f['partnerUser'])->test(History::class);

    $component->set('from', '2026-10-20')->assertSee('Resposta nova.')->assertDontSee('Resposta antiga.');
    $component->set('from', '')->set('to', '2026-10-15')->assertSee('Resposta antiga.')->assertDontSee('Resposta nova.');
    $component->set('to', '')->set('status', 'RESOLVED')->assertSee('Resposta nova.')->assertDontSee('Resposta antiga.');
    $component->set('status', '')->set('search', 'NOTA-INEXISTENTE')->assertDontSee('Resposta nova.')->assertDontSee('Resposta antiga.')->assertSee('Nenhum retorno encontrado');
    $component->set('search', $f['note']->note)->assertSee('Resposta nova.')->assertSee('Resposta antiga.');
    $component->call('clearFilters')->assertSet('search', '')->assertSet('status', '');
});

it('shows nothing to users of other companies and lets the superadm see every company', function () {
    $f = closureScreenFixture();
    histReply($f, histIssue($f), 'Resposta da empresa A.');

    $b = User::factory()->create(['superadm' => false, 'admin' => false, 'company_id' => $f['other']->id]);
    Livewire::actingAs($b)->test(History::class)->assertDontSee('Resposta da empresa A.');

    // quem é de outra empresa (ou sem vínculo com a obra) abre a tela, mas o escopo dele é vazio: nada vaza
    Livewire::actingAs($f['outsider'])->test(History::class)->assertDontSee('Resposta da empresa A.')->assertSee('Nenhum retorno encontrado');

    Livewire::actingAs(User::factory()->create(['superadm' => true, 'admin' => false]))->test(History::class)
        ->assertSee('Resposta da empresa A.')
        ->set('partnerCompanyFilter', (string) $f['other']->id)
        ->assertDontSee('Resposta da empresa A.');
});

// ---------------------------------------------------------------- linhas do Excel

it('builds the issue rows with waiting days, requirements and one column per heading', function () {
    $f     = closureScreenFixture();
    $issue = histIssue($f, ['Enviar a ART', 'Foto da placa']);
    Carbon::setTestNow('2026-10-30 10:00:00');

    $records = Livewire::actingAs($f['partnerUser'])->test(Index::class)->instance()->exportQuery()->get();
    $rows    = app(ClosurePartnerExporter::class)->issueRows($records);

    expect($rows)->toHaveCount(1)
        ->and($rows[0])->toHaveCount(count(ClosurePartnerExporter::ISSUE_HEADINGS))
        ->and($rows[0][0])->toBe($issue->id)
        ->and($rows[0][5])->toBe('Aguardando resposta')
        ->and($rows[0][10])->toBe(5)
        ->and($rows[0][12])->toBe("1. Enviar a ART\n2. Foto da placa");
});

it('builds the history rows with item counts, attachments and outcome', function () {
    $f     = closureScreenFixture();
    $issue = histIssue($f, ['A', 'B', 'C']);
    app(ClosureIssueWorkflowService::class)->respond($issue, $f['company'], $f['partnerUser'], 'Mensagem.', [
        ['status' => 'DONE'], ['status' => 'NA', 'note' => 'Não precisa.'], ['status' => 'UNABLE', 'note' => 'Sem acesso.'],
    ], [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]);

    $events = Livewire::actingAs($f['partnerUser'])->test(History::class)->instance()->exportQuery()->get();
    $rows   = app(ClosurePartnerExporter::class)->historyRows($events);

    expect($rows)->toHaveCount(1)
        ->and($rows[0])->toHaveCount(count(ClosurePartnerExporter::HISTORY_HEADINGS))
        ->and($rows[0][8])->toBe('Mensagem.')
        ->and(array_slice($rows[0], 9, 4))->toBe([1, 1, 1, 1])
        ->and($rows[0][13])->toBe('Aguardando validação')
        ->and($rows[0][14])->toBe('Respondida · aguardando validação');

    expect(app(ClosurePartnerExporter::class)->summaryRows('Situação', ['A' => 1, 'B' => 3]))->toBe([['Situação', 'A', 1, 25.0], ['Situação', 'B', 3, 75.0]]);
});

// ---------------------------------------------------------------- job de exportação

it('exports the issues list and the history in the user scope and notifies with the file link', function () {
    $f = closureScreenFixture();
    histReply($f, histIssue($f), 'Minha resposta.');
    $mine = histIssue($f);

    $otherUser = User::factory()->create(['superadm' => false, 'admin' => false, 'company_id' => $f['other']->id]);
    histReply($f, histIssue($f, ['x'], $f['other']), 'De outra empresa.', [], $otherUser, $f['other']);

    foreach ([Index::class, History::class] as $component) {
        dispatch_sync(new ExportPartnerClosureListJob($component, ['search' => '', 'status' => ''], (string) $f['partnerUser']->id));
    }

    $files = Storage::disk('local')->files('exports');

    expect($files)->toHaveCount(2);

    $payloads = Notification::sent($f['partnerUser'], SystemNotification::class)->map(fn ($n) => $n->toDatabase($f['partnerUser']))->all();
    $messages = collect($payloads)->pluck('message')->filter(fn ($m) => str_contains((string) $m, 'registros'))->values();

    // Pendências: as 2 da empresa (a respondida e a nova); histórico: 1 retorno. Nada da outra empresa.
    expect($messages[0])->toContain('(2 registros)')
        ->and($messages[1])->toContain('(1 registros)')
        ->and($mine->fresh()->status)->toBe('REQUESTED');
});

it('exports an empty file, never someone else\'s data, for a user of another company', function () {
    $f = closureScreenFixture();
    histReply($f, histIssue($f), 'Resposta da empresa A.');

    dispatch_sync(new ExportPartnerClosureListJob(Index::class, [], (string) $f['outsider']->id));

    $message = Notification::sent($f['outsider'], SystemNotification::class)->map(fn ($n) => $n->toDatabase($f['outsider'])['message'])->first();

    expect($message)->toContain('(0 registros)');
});

it('queues the export from both screens with the filters that were on screen', function () {
    Bus::fake();
    $f = closureScreenFixture();

    Livewire::actingAs($f['partnerUser'])->test(Index::class)->set('search', '123')->set('status', 'REQUESTED')->call('exportToExcel')->assertDispatchedBrowserEvent('closure:toast');
    Livewire::actingAs($f['partnerUser'])->test(History::class)->set('from', '2026-10-01')->call('exportToExcel')->assertDispatchedBrowserEvent('closure:toast');

    Bus::assertDispatched(ExportPartnerClosureListJob::class, fn ($job) => $job->component === Index::class && $job->filters['search'] === '123' && $job->filters['status'] === 'REQUESTED' && $job->userId === (string) $f['partnerUser']->id);
    Bus::assertDispatched(ExportPartnerClosureListJob::class, fn ($job) => $job->component === History::class && $job->filters['from'] === '2026-10-01');
});

it('downloads the Excel of one issue with request, requirements with answers and history', function () {
    $f     = closureScreenFixture();
    $issue = histIssue($f, ['Enviar a ART', 'Foto da placa']);
    app(ClosureIssueWorkflowService::class)->respond($issue, $f['company'], $f['partnerUser'], 'Ok.', [['status' => 'DONE'], ['status' => 'UNABLE', 'note' => 'Sem acesso.']]);

    $sheets = app(ClosurePartnerExporter::class)->issueDetailSheets($issue->fresh()->load(['Order.Note', 'Category', 'Subcategory', 'Events.Actor']));

    expect(collect($sheets['info'])->firstWhere(0, 'Situação')[1])->toBe('Respondida · aguardando validação')
        ->and($sheets['items'])->toBe([[1, 'Enviar a ART', 'Atendido', null], [2, 'Foto da placa', 'Não consigo atender', 'Sem acesso.']])
        ->and($sheets['history'])->toHaveCount(3)
        ->and($sheets['history'][1][1])->toBe('Enviada à parceira')
        ->and($sheets['history'][2][1])->toBe('Parceira respondeu');

    Livewire::actingAs($f['partnerUser'])->test(Detail::class, ['issueId' => $issue->id])
        ->call('exportIssue')
        ->assertFileDownloaded('pendencia-' . $issue->id . '.xlsx');

    expect(new ClosureIssueDetailExport('x', $sheets))->toBeInstanceOf(ClosureIssueDetailExport::class);
});

it('shows the new actions on the partner list instead of the old reply modal', function () {
    $f     = closureScreenFixture();
    $issue = histIssue($f);

    Livewire::actingAs($f['partnerUser'])->test(Index::class)
        ->assertSee('Ver pedido e responder')
        ->assertSee('Exportar Excel')
        ->assertSee('Meu histórico')
        ->assertDontSee('Resposta da parceira')
        ->assertSeeHtml(route('partner.closure.issue.show', $issue->id));
});
