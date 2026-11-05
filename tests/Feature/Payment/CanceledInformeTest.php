<?php

use App\Models\{Analise, Company, FiveNote, Note, Notetimeline, Production, Service, User};
use App\Services\Payment\NoteFilter;
use App\Services\Production\CanceledInformeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

// Informe cancelado: a Nota não vai para o despacho sem atividade aberta; com atividade aberta é sinalizada e só pode ser finalizada
// por cancelamento (análise FINALIZADO POR CANCELAMENTO + quem e quando cancelou).

uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow('2026-11-04 10:00:00'));

afterEach(fn () => Carbon::setTestNow());

function canceledFixture(): array
{
    $company = Company::create(['name' => 'Compel', 'email' => 'c@example.com']);
    $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);
    $user    = User::factory()->create(['name' => 'Izadora Tintore']);
    $who     = User::factory()->create(['name' => 'Will Oliveira']);

    return compact('company', 'service', 'user', 'who');
}

function canceledNote(array $f, string $number, bool $canceled = true, bool $withProduction = false, bool $completed = false): array
{
    $note = Note::query()->create(['note' => $number, 'type_note' => 1]);

    $reportId = DB::table('work_reports')->insertGetId([
        'note_id'  => $note->id, 'company_id' => $f['company']->id, 'informed_at' => '2026-03-01 10:00:00',
        'canceled' => $canceled, 'canceled_at' => $canceled ? '2026-04-09 20:57:00' : null, 'canceled_by' => $canceled ? $f['who']->id : null,
        'rejected' => false, 'created_at' => now(), 'updated_at' => now(),
    ]);

    // Nota liberada pela D5 já fiscalizada e concluída (caminho que mantinha a Nota na lista mesmo com o informe cancelado).
    FiveNote::create(['note_id' => $note->id, 'note_d5' => '', 'is_supervisioned' => true, 'is_completed' => true, 'is_archived' => false]);

    $production = $withProduction ? Production::query()->create([
        'note_id' => $note->id, 'service_id' => $f['service']->uuid, 'company_id' => $f['company']->id, 'user_id' => $f['user']->id,
        'status'  => $completed ? 5 : 2, 'completed' => $completed, 'rejected' => false, 'd5' => false,
    ]) : null;

    if ($production) {
        DB::table('work_report_flow_productions')->insert([
            'work_report_id' => $reportId, 'production_id' => $production->id, 'stage' => 'payment', 'final_scope' => 'general',
            'is_current'     => true, 'linked_at' => now(), 'source' => 'test',
        ]);
    }

    return [$note, $production, $reportId];
}

function listedNotes(): array
{
    return app(NoteFilter::class)->filter(null, 'test', [])->pluck('notes.note')->map(fn ($n) => (string) $n)->all();
}

it('nota com informe cancelado e sem atividade não entra na lista de despacho', function () {
    $f = canceledFixture();

    canceledNote($f, 'N-ATIVO', false);
    canceledNote($f, 'N-CANCELADO-SEM-PRODUCAO');
    canceledNote($f, 'N-CANCELADO-FINALIZADO', true, true, true);
    canceledNote($f, 'N-CANCELADO-ATRIBUIDO', true, true, false);

    $listed = listedNotes();

    expect($listed)->toContain('N-CANCELADO-ATRIBUIDO')        // atividade aberta: aparece (sinalizada)
        ->not->toContain('N-CANCELADO-SEM-PRODUCAO')
        ->not->toContain('N-CANCELADO-FINALIZADO');
});

it('resolve o informe cancelado da produção pelo vínculo do fluxo, com quem cancelou e quando', function () {
    $f                         = canceledFixture();
    [, $production, $reportId] = canceledNote($f, 'N-1', true, true);
    [, $normal]                = canceledNote($f, 'N-2', false, true);

    $found = app(CanceledInformeService::class)->forProductions([$production->id, $normal->id]);

    expect($found)->toHaveCount(1)
        ->and($found[$production->id]['id'])->toBe($reportId)
        ->and($found[$production->id]['canceled_by'])->toBe('Will Oliveira')
        ->and($found[$production->id]['canceled_at'])->toBe('09/04/2026 20:57');
});

it('sem vínculo no fluxo, vale o informe cancelado da Nota quando ela não tem informe ativo', function () {
    $f                   = canceledFixture();
    [$note, $production] = canceledNote($f, 'N-3', true, true);
    DB::table('work_report_flow_productions')->where('production_id', $production->id)->delete();

    expect(app(CanceledInformeService::class)->forProduction($production))->not->toBeNull();

    DB::table('work_reports')->insert(['note_id' => $note->id, 'company_id' => $f['company']->id, 'canceled' => false, 'rejected' => false, 'created_at' => now(), 'updated_at' => now()]);

    expect(app(CanceledInformeService::class)->forProduction($production))->toBeNull();   // há informe ativo: nada a finalizar
});

it('finaliza por cancelamento: encerra a produção, registra a análise e descreve quem e quando cancelou', function () {
    $f                   = canceledFixture();
    [$note, $production] = canceledNote($f, 'N-4', true, true);

    app(CanceledInformeService::class)->finish($production, $f['user']);

    $production->refresh();
    $analise = Analise::where('production_id', $production->id)->first();

    expect($production->completed)->toBeTrue()
        ->and($production->status)->toBe(5)
        ->and($production->completed_at)->not->toBeNull()
        ->and($analise->conclusion)->toBe('FINALIZADO POR CANCELAMENTO')
        ->and($analise->info)->toContain('cancelado por Will Oliveira')->toContain('09/04/2026 20:57')->toContain('Izadora Tintore')->toContain('04/11/2026 10:00')
        ->and(Notetimeline::where('production_id', $production->id)->value('info'))->toContain('por cancelamento do informe');
});

it('não finaliza produção sem informe cancelado nem produção já encerrada', function () {
    $f          = canceledFixture();
    [, $normal] = canceledNote($f, 'N-5', false, true);
    [, $done]   = canceledNote($f, 'N-6', true, true, true);

    $service = app(CanceledInformeService::class);

    expect(fn () => $service->finish($normal, $f['user']))->toThrow(DomainException::class)
        ->and(fn () => $service->finish($done, $f['user']))->toThrow(DomainException::class)
        ->and($normal->fresh()->completed)->toBeFalse();
});

it('no Acompanhamento do usuário mostra o informe cancelado, só deixa finalizar, confirma e encerra a produção', function () {
    $f              = canceledFixture();
    [, $production] = canceledNote($f, 'N-7', true, true);
    [, $normal]     = canceledNote($f, 'N-8', false, true);

    $this->actingAs($f['user']);

    $component = Livewire\Livewire::test(App\Http\Livewire\Services\Payment\Accompany\Main::class, ['service' => $f['service']->uuid])
        ->assertSee('INFORME CANCELADO')
        ->assertSee('Finalizar')
        ->assertSee('Iniciar');                 // a outra produção (informe normal) segue com Iniciar

    $row = fn (int $id) => collect($component->viewData('rows'))->firstWhere('id', $id);

    expect($row($production->id)['canFinish'])->toBeTrue()
        ->and($row($production->id)['canAct'])->toBeFalse()          // informe cancelado: sem Iniciar, Transferir nem Devolver
        ->and($row($production->id)['canReturn'])->toBeFalse()
        ->and($row($normal->id)['canFinish'])->toBeFalse()
        ->and($row($normal->id)['canAct'])->toBeTrue();

    // Pede a confirmação (SweetAlert) dizendo o que vai acontecer; nada é alterado ainda.
    $component->call('askFinishCanceled', $production->id)->assertDispatchedBrowserEvent('alertar');
    expect($production->fresh()->completed)->toBeFalse();

    // Confirmado: encerra, registra a análise e some da fila.
    $component->call('finishCanceled')->assertDispatchedBrowserEvent('swal');

    expect($production->fresh()->completed)->toBeTrue()
        ->and(Analise::where('production_id', $production->id)->value('conclusion'))->toBe('FINALIZADO POR CANCELAMENTO');
});

it('não finaliza a produção de outra pessoa nem uma sem informe cancelado', function () {
    $f              = canceledFixture();
    [, $production] = canceledNote($f, 'N-9', true, true);
    [, $normal]     = canceledNote($f, 'N-10', false, true);
    $intruder       = User::factory()->create();

    $this->actingAs($intruder);
    Livewire\Livewire::test(App\Http\Livewire\Services\Payment\Accompany\Main::class, ['service' => $f['service']->uuid])
        ->call('askFinishCanceled', $production->id)->call('finishCanceled');

    $this->actingAs($f['user']);
    Livewire\Livewire::test(App\Http\Livewire\Services\Payment\Accompany\Main::class, ['service' => $f['service']->uuid])
        ->call('askFinishCanceled', $normal->id)->call('finishCanceled');

    expect($production->fresh()->completed)->toBeFalse()
        ->and($normal->fresh()->completed)->toBeFalse();
});
