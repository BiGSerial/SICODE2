<?php

use App\Http\Livewire\Closure\Works\Show;
use App\Jobs\Files\GenerateFileDownloadBatchJob;
use App\Models\{Adsform, File, WorkReport};
use App\Services\Closure\{ClosureInternalReturnService, ClosureWorkDossier};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Bus, Notification, Storage};
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

// Gestão da Obra para o executor: abas Retorno Interno / Informes / Arquivos (dossiê somente leitura da obra).

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Storage::fake('public');
});

function dossierFile(array $f, string $name, array $extra = []): File
{
    Storage::disk('public')->put("obra/{$name}", 'conteudo');

    return File::query()->create(array_merge(['note_id' => $f['note']->id, 'file_name' => $name, 'path' => "obra/{$name}", 'disk' => 'public', 'ext' => pathinfo($name, PATHINFO_EXTENSION)], $extra));
}

it('shows the dossier tabs only to the internal team', function () {
    $f = closureScreenFixture();

    Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])
        ->assertSee('Retorno Interno')->assertSee('Informes')->assertSee('Arquivos');

    $partner = Livewire::actingAs($f['partnerUser'])->test(Show::class, ['noteId' => $f['note']->id]);
    $partner->assertDontSee('Retorno Interno')->assertDontSee('Informes registrados');

    $partner->call('setTab', 'files')->assertForbidden();
});

it('lists every informe of the work with company, amount and tacit ADS', function () {
    $f      = closureScreenFixture();
    $report = WorkReport::query()->create(['note_id' => $f['note']->id, 'company_id' => $f['company']->id, 'user_id' => $f['internal']->id, 'date' => now()->subDays(3), 'description' => 'Obra concluída', 'informer' => 'Fulano']);
    $report->Orders()->attach($f['order']->id);
    Adsform::query()->create(['work_report_id' => $report->id, 'note_id' => $f['note']->id, 'user_id' => $f['internal']->id, 'name' => 'ADS', 'amount' => 1234.5, 'tacit' => true, 'tacit_due_at' => now()->addDays(2)]);

    $rows = app(ClosureWorkDossier::class)->informs($f['note']);

    expect($rows)->toHaveCount(1)->and($rows[0])->toMatchArray(['company' => $f['company']->name, 'amount' => 1234.5, 'tacit' => true, 'orders' => [$f['order']->ordem]]);

    Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('setTab', 'informs')->assertSee('Informes registrados da obra')->assertSee('R$ 1.234,50')->assertSee('Tácita')->assertSee($f['company']->name);
});

it('lists all files of the note, classifies them and lets the executor download them including tacit ADS', function () {
    $f      = closureScreenFixture();
    $plain  = dossierFile($f, 'projeto.pdf');
    $photo  = dossierFile($f, 'foto1.jpg');
    $ads    = dossierFile($f, 'ads-tacita.pdf');
    $report = WorkReport::query()->create(['note_id' => $f['note']->id, 'company_id' => $f['company']->id, 'user_id' => $f['internal']->id, 'date' => now()]);
    $form   = Adsform::query()->create(['work_report_id' => $report->id, 'note_id' => $f['note']->id, 'user_id' => $f['internal']->id, 'name' => 'ADS', 'amount' => 10, 'tacit' => true]);
    $form->Files()->attach($ads->id);

    $items = app(ClosureWorkDossier::class)->files($f['note']);

    expect($items->pluck('name')->all())->toContain('projeto.pdf', 'foto1.jpg', 'ads-tacita.pdf')
        ->and($items->firstWhere('name', 'foto1.jpg')['kind'])->toBe('foto')
        ->and($items->firstWhere('name', 'ads-tacita.pdf'))->toMatchArray(['kind' => 'ads', 'tacit' => true])
        ->and(app(ClosureWorkDossier::class)->files($f['note'], 'foto')->pluck('name')->all())->toBe(['foto1.jpg'])
        ->and(app(ClosureWorkDossier::class)->files($f['note'], '', 'projeto')->pluck('name')->all())->toBe(['projeto.pdf']);

    $component = Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('setTab', 'files')->assertSee('Arquivos da obra')->assertSee('ADS tácita');

    $component->call('downloadFile', $ads->id)->assertHasNoErrors();
    $component->call('downloadFile', $plain->id)->assertHasNoErrors();
});

it('never downloads a file that belongs to another note', function () {
    $f     = closureScreenFixture();
    $other = closureScreenFixture();
    $file  = dossierFile($other, 'alheio.pdf');

    Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])->call('downloadFile', $file->id)->assertNotFound();

    Bus::fake();
    Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])
        ->set('selectedFiles', [(string) $file->id])->call('downloadZip');

    Bus::assertNotDispatched(GenerateFileDownloadBatchJob::class);
});

it('queues one ZIP with the selected files of the note', function () {
    Bus::fake();
    $f = closureScreenFixture();
    $a = dossierFile($f, 'a.pdf');
    $b = dossierFile($f, 'b.pdf');

    Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('setTab', 'files')->call('selectAllFiles')->call('downloadZip')->assertSet('selectedFiles', []);

    Bus::assertDispatchedTimes(GenerateFileDownloadBatchJob::class, 1);
    expect(collect(\App\Models\FileDownloadBatch::query()->firstOrFail()->file_ids)->sort()->values()->all())->toBe(collect([$a->id, $b->id])->sort()->values()->all());
});

it('shows the RI stage for who is waiting: sent, working, returned and cancelled', function () {
    $f = closureScreenFixture();
    \App\Models\Service::query()->firstOrCreate(['folder' => 'desenho'], ['service' => 'Desenho', 'project' => true, 'construction' => false, 'icon' => 'ri-pencil-ruler-line']);
    \App\Models\Viability::query()->create(['note_id' => $f['note']->id, 'company_id' => $f['company']->id, 'user_id' => $f['internal']->id, 'status' => 6]);
    $cat = \App\Models\Category::query()->create(['name' => 'PROJETO']);
    $sub = \App\Models\Subcategory::query()->create(['name' => 'ALTERAR PROJETO', 'category_id' => $cat->id]);

    $service = app(ClosureInternalReturnService::class);
    $ri      = $service->send($f['note'], $f['dispatcher'], 'Corrigir trecho X', $sub);
    $dossier = app(ClosureWorkDossier::class);

    expect($dossier->internalReturns($f['note'])[0])->toMatchArray(['category' => 'PROJETO', 'subcategory' => 'ALTERAR PROJETO'])
        ->and($dossier->internalReturns($f['note'])[0]['stage']['key'])->toBe('sent');

    $reclaim = \App\Models\Reclaim::query()->find($ri->reclaim_id);
    $reclaim->update(['production_id' => \App\Models\Production::query()->first()->id]);
    expect($dossier->internalReturns($f['note'])[0]['stage']['key'])->toBe('working');

    Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])
        ->call('setTab', 'ri')->assertSee('Em tratamento no Desenho')->assertSee('Corrigir trecho X')->assertSee('PROJETO');

    $reclaim->update(['completed' => true, 'completed_at' => now()]);
    expect($dossier->internalReturns($f['note'])[0]['stage']['key'])->toBe('returned')
        ->and($ri->fresh()->status)->toBe('RETURNED');
});

it('shows the lock banner and the summary cards on the overview while a RI is active', function () {
    $f = closureScreenFixture();
    \App\Models\Service::query()->firstOrCreate(['folder' => 'desenho'], ['service' => 'Desenho', 'project' => true, 'construction' => false, 'icon' => 'ri-pencil-ruler-line']);
    \App\Models\Viability::query()->create(['note_id' => $f['note']->id, 'company_id' => $f['company']->id, 'user_id' => $f['internal']->id, 'status' => 6]);

    $component = Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])
        ->assertSee('Enviar RI ao Desenho')->assertSee('Ordem(ns) concluída(s)')->assertDontSee('Obra travada por RI');

    app(ClosureInternalReturnService::class)->send($f['note'], $f['dispatcher'], 'x');

    $component->call('$refresh')->assertSee('Obra travada por RI com o Desenho')->assertSee('Aguardando o Desenho')->assertDontSee('Enviar RI ao Desenho</button>', false);
});

it('counts orders already closed in SAP as concluded in the progress bar', function () {
    $f = closureScreenFixture();
    $f['order']->update(['statusSist' => 'ENCE CONF']);

    Livewire::actingAs($f['internal'])->test(Show::class, ['noteId' => $f['note']->id])
        ->assertSee('1</strong> de 2 Ordem(ns) concluída(s)', false)->assertSee('Encerrada no SAP · 1');
});
