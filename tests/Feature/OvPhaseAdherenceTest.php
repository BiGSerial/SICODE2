<?php

use App\Models\{Company, HistoricNote, Note, OvPhaseDuration, Production, Service};
use App\Services\Indicators\OvAdherenceService;
use App\Services\Notes\OvPhaseDurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function phaseNote(string $ov, array $events): Note
{
    $note = Note::create(['note' => $ov, 'type_note' => 2]);

    foreach ($events as $i => [$stat, $at]) {
        HistoricNote::create([
            'note_id'  => $note->id,
            'id_num'   => $i + 1,
            'num_stat' => $stat,
            'dt_stats' => $at,
        ]);
    }

    return $note;
}

it('calcula levantamento e desenho até o evento seguinte', function () {
    $note = phaseNote('8001', [
        [10, '2026-02-01 08:00:00'],
        [27, '2026-02-03 08:00:00'],
        [28, '2026-02-09 08:00:00'],
        [50, '2026-02-13 20:00:00'],
    ]);

    app(OvPhaseDurationService::class)->rebuildForNotes([$note->id]);

    $survey = OvPhaseDuration::where('note_id', $note->id)->where('phase', 27)->sole();
    $design = OvPhaseDuration::where('note_id', $note->id)->where('phase', 28)->sole();

    expect($survey->days)->toBe(6.0)->and($design->days)->toBe(4.5)
        ->and($survey->ended_at->toDateString())->toBe('2026-02-09');
});

it('considera só a última passagem pelo status 27', function () {
    $note = phaseNote('8002', [
        [27, '2026-01-02 08:00:00'],
        [10, '2026-01-04 08:00:00'],
        [27, '2026-01-10 08:00:00'],
        [28, '2026-01-13 08:00:00'],
    ]);

    app(OvPhaseDurationService::class)->rebuildForNotes([$note->id]);

    expect(OvPhaseDuration::where('phase', 27)->sole()->days)->toBe(3.0);
});

it('fase aberta não gera linha e remove a anterior', function () {
    $note    = phaseNote('8003', [[27, '2026-01-02 08:00:00'], [28, '2026-01-05 08:00:00']]);
    $service = app(OvPhaseDurationService::class);

    $service->rebuildForNotes([$note->id]);
    expect(OvPhaseDuration::where('phase', 27)->count())->toBe(1);

    HistoricNote::create(['note_id' => $note->id, 'id_num' => 3, 'num_stat' => 27, 'dt_stats' => '2026-01-20 08:00:00']);
    $service->rebuildForNotes([$note->id]);

    expect(OvPhaseDuration::where('phase', 27)->count())->toBe(0);
});

it('vincula a empresa da produção concluída da fase', function () {
    $company = Company::create(['name' => 'EDP ES', 'email' => 'edp-es@example.test']);
    $service = Service::create(['service' => 'LEVANTAMENTO', 'status' => 27]);
    $note    = phaseNote('8004', [[27, '2026-03-01 08:00:00'], [28, '2026-03-04 08:00:00']]);

    $production = Production::forceCreate([
        'note_id'      => $note->id,
        'service_id'   => $service->uuid,
        'company_id'   => $company->id,
        'completed'    => true,
        'completed_at' => '2026-03-04 07:00:00',
    ]);

    app(OvPhaseDurationService::class)->rebuildForNotes([$note->id]);

    $phase = OvPhaseDuration::where('phase', 27)->sole();
    expect($phase->company_id)->toBe($company->id)->and($phase->production_id)->toBe($production->id);
});

it('agrupa aderência pelo mês de término e por faixa', function () {
    $ends = [
        // levantamento: 8 dias (aderente), 12 dias, 40 dias — todos terminam em fev/26
        ['9001', '2026-02-01 00:00:00', '2026-02-09 00:00:00'],
        ['9002', '2026-02-01 00:00:00', '2026-02-13 00:00:00'],
        ['9003', '2026-01-10 00:00:00', '2026-02-19 00:00:00'],
    ];

    foreach ($ends as [$ov, $start, $end]) {
        $note = phaseNote($ov, [[27, $start], [28, $end]]);
        app(OvPhaseDurationService::class)->rebuildForNotes([$note->id]);
    }

    $feb = collect(app(OvAdherenceService::class)->monthly(27))->firstWhere('month', '2026-02');

    expect($feb['total'])->toBe(3)
        ->and($feb['bands'])->toBe([1, 1, 0, 1])
        ->and($feb['adherence'])->toBe(33.3);
});

it('ignora fases terminadas antes de 2026', function () {
    $note = phaseNote('9100', [[27, '2025-12-01 00:00:00'], [28, '2025-12-05 00:00:00']]);
    app(OvPhaseDurationService::class)->rebuildForNotes([$note->id]);

    expect(app(OvAdherenceService::class)->monthly(27))->toBe([]);
});

it('mostra a tela de prazo de projetos só para gerente', function () {
    $note = phaseNote('9200', [[27, '2026-02-01 00:00:00'], [28, '2026-02-05 00:00:00']]);
    app(OvPhaseDurationService::class)->rebuildForNotes([$note->id]);

    \App\Models\Bancoupdate::create(['last_update' => now(), 'error' => 0, 'inserts' => 0, 'updates' => 0]);

    $this->actingAs(\App\Models\User::factory()->create(['management' => false, 'superadm' => false]))
        ->get(route('reports.project_deadlines'))
        ->assertForbidden();

    // O menu do layout assume que o usuário tem empresa.
    $company = Company::create(['name' => 'EDP ES', 'email' => 'es@example.test']);
    $manager = \App\Models\User::factory()->create(['management' => true, 'superadm' => false, 'engineer' => false, 'company_id' => $company->id]);

    $this->actingAs($manager)
        ->get(route('reports.project_deadlines'))
        ->assertOk()
        ->assertSee('Prazo de Projetos');

    \Livewire\Livewire::actingAs($manager)
        ->test(\App\Http\Livewire\Reports\ProjectDeadlineDashboard::class)
        ->assertSee('fev-26')
        ->assertSee('100,0%');
});

it('enfileira o Excel pelo job e o botão não baixa direto', function () {
    \Illuminate\Support\Facades\Queue::fake();

    \Livewire\Livewire::actingAs(\App\Models\User::factory()->create(['management' => true, 'superadm' => false]))
        ->test(\App\Http\Livewire\Reports\ProjectDeadlineDashboard::class)
        ->call('exportToExcel')
        ->assertDispatchedBrowserEvent('swal');

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\Reports\ProjectDeadlineExportJob::class);
});

it('gera o Excel com as abas e recusa quem não é gerente', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    \Illuminate\Support\Facades\Notification::fake();

    $company = Company::create(['name' => 'EDP SP', 'email' => 'sp@example.test']);
    $service = Service::create(['service' => 'LEVANTAMENTO', 'status' => 27]);
    $note    = phaseNote('9400', [[27, '2026-02-01 00:00:00'], [28, '2026-02-09 00:00:00']]);
    Production::forceCreate(['note_id' => $note->id, 'service_id' => $service->uuid, 'company_id' => $company->id, 'completed' => true, 'completed_at' => '2026-02-09 00:00:00']);
    app(OvPhaseDurationService::class)->rebuildForNotes([$note->id]);

    $params  = ['dt_init' => '2026-01-01', 'dt_end' => '2026-12-31'];
    $plain   = \App\Models\User::factory()->create(['management' => false, 'superadm' => false]);
    $manager = \App\Models\User::factory()->create(['management' => true, 'superadm' => false]);

    \App\Jobs\Reports\ProjectDeadlineExportJob::dispatchSync($params, $plain->id);
    expect(\Illuminate\Support\Facades\Storage::disk('local')->files('exports'))->toBeEmpty();

    \App\Jobs\Reports\ProjectDeadlineExportJob::dispatchSync($params, $manager->id);
    $files = \Illuminate\Support\Facades\Storage::disk('local')->files('exports');
    expect($files)->toHaveCount(1)->and($files[0])->toEndWith('_prazo_projetos.xlsx');

    $book = \PhpOffice\PhpSpreadsheet\IOFactory::load(\Illuminate\Support\Facades\Storage::disk('local')->path($files[0]));
    expect($book->getSheetNames())->toBe(['Resumo', 'Levantamento', 'Desenho', 'Por empresa', 'Fases concluídas']);

    $survey = $book->getSheetByName('Levantamento')->toArray(null, true, false, false);
    expect($survey[3][0])->toBe('Mês')
        ->and($survey[4][1])->toEqual(1)          // total de OVs em fev/26
        ->and($survey[4][2])->toEqual(1)          // 8 dias: faixa 0 a 8
        ->and($survey[4][6])->toEqual(100.0)      // % aderência
        ->and($survey[5][0])->toBe('TOTAL');

    $companies = $book->getSheetByName('Por empresa')->toArray(null, true, false, false);
    expect($companies[4][0])->toBe('EDP SP');

    $detail = $book->getSheetByName('Fases concluídas')->toArray(null, true, false, false);
    expect($detail[4][0])->toEqual('9400')->and($detail[4][7])->toBe('Sim');
});
