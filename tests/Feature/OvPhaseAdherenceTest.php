<?php

use App\Models\{Company, HistoricNote, Note, OvPhaseDuration, Production, Service};
use App\Services\Indicators\OvAdherenceService;
use App\Services\Notes\{OvPhaseDurationService, OvStatusHistoryService};
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
    $note = phaseNote('6001', [
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
    $note = phaseNote('6002', [
        [27, '2026-01-02 08:00:00'],
        [10, '2026-01-04 08:00:00'],
        [27, '2026-01-10 08:00:00'],
        [28, '2026-01-13 08:00:00'],
    ]);

    app(OvPhaseDurationService::class)->rebuildForNotes([$note->id]);

    expect(OvPhaseDuration::where('phase', 27)->sole()->days)->toBe(3.0);
});

it('fase aberta não gera linha e remove a anterior', function () {
    $note    = phaseNote('6003', [[27, '2026-01-02 08:00:00'], [28, '2026-01-05 08:00:00']]);
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
    $note    = phaseNote('6004', [[27, '2026-03-01 08:00:00'], [28, '2026-03-04 08:00:00']]);

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

it('mantém a quantidade e a ordem dos datasets mesmo sem dados', function () {
    $report = app(\App\Services\Indicators\ProjectDeadlineAnalytics::class)->report(['from' => '2026-01-01', 'to' => '2026-01-02'], withDetail: false);
    $charts = \App\Services\Indicators\ProjectDeadlineCharts::all($report);

    expect(count($charts['survey']['data']['datasets']))->toBe(5)
        ->and(count($charts['design']['data']['datasets']))->toBe(5)
        ->and(count($charts['tmep']['data']['datasets']))->toBe(4)
        ->and(count($charts['companies']['data']['datasets']))->toBe(2)
        ->and(array_column($charts['survey']['data']['datasets'], 'label'))->toBe(['0 a 8 dias', '9 a 15 dias', '16 a 30 dias', 'Acima de 30 dias', '% aderência']);
});

it('filtra o relatório inteiro ao clicar numa empresa', function () {
    $edp     = Company::create(['name' => 'EDP SP', 'email' => 'sp2@example.test']);
    $pa      = Company::create(['name' => 'Parceira PA', 'email' => 'pa@example.test']);
    $service = Service::create(['service' => 'LEVANTAMENTO', 'status' => 27]);

    foreach ([['9501', $edp], ['9502', $pa], ['9503', $pa]] as [$ov, $company]) {
        $note = phaseNote($ov, [[27, '2026-02-01 00:00:00'], [28, '2026-02-05 00:00:00']]);
        Production::forceCreate(['note_id' => $note->id, 'service_id' => $service->uuid, 'company_id' => $company->id, 'completed' => true, 'completed_at' => '2026-02-05 00:00:00']);
        app(OvPhaseDurationService::class)->rebuildForNotes([$note->id]);
    }

    $manager = \App\Models\User::factory()->create(['management' => true, 'superadm' => false]);

    $component = \Livewire\Livewire::actingAs($manager)->test(\App\Http\Livewire\Reports\ProjectDeadlineDashboard::class);
    expect($component->get('snapshot')['phases'][27]['summary']['total'])->toBe(3);

    $component->call('focusCompany', $pa->id)->assertDispatchedBrowserEvent('my-panel:charts');
    expect($component->get('snapshot')['phases'][27]['summary']['total'])->toBe(2);

    $component->call('clearFilters');
    expect($component->get('snapshot')['phases'][27]['summary']['total'])->toBe(3);
});

it('o término da fase é o próximo id_num e não o último status da OV', function () {
    // eventos gravados fora de ordem; o último status (id_num 6) está muito depois
    $note = Note::create(['note' => '9600', 'type_note' => 2]);

    foreach ([[6, 50, '2026-05-30 08:00:00'], [3, 28, '2026-03-10 08:00:00'], [2, 27, '2026-03-04 08:00:00'], [5, 27, '2026-04-02 08:00:00'], [8, 60, '2026-06-30 08:00:00'], [1, 10, '2026-03-01 08:00:00']] as [$idNum, $stat, $at]) {
        HistoricNote::create(['note_id' => $note->id, 'id_num' => $idNum, 'num_stat' => $stat, 'dt_stats' => $at]);
    }

    app(OvPhaseDurationService::class)->rebuildForNotes([$note->id]);

    $survey = OvPhaseDuration::where('note_id', $note->id)->where('phase', 27)->sole();
    $design = OvPhaseDuration::where('note_id', $note->id)->where('phase', 28)->sole();

    // último 27 = id_num 5 (02/04); o próximo acima é o id_num 6 (30/05): 58 dias
    expect($survey->started_at->toDateTimeString())->toBe('2026-04-02 08:00:00')
        ->and($survey->ended_at->toDateTimeString())->toBe('2026-05-30 08:00:00')
        ->and($survey->days)->toBe(58.0)
        // 28 = id_num 3 (10/03); o próximo acima é o id_num 5 (02/04, o 2º status 27): 23 dias
        ->and($design->ended_at->toDateTimeString())->toBe('2026-04-02 08:00:00')
        ->and($design->days)->toBe(23.0);
});

it('a data do evento é dtStat com a hora de dhStat, mesmo se dhStat vier com outra data', function () {
    $note = Note::create(['note' => '9700', 'type_note' => 2]);

    app(OvStatusHistoryService::class)->upsertFromSource([
        // dhStat com a data do último status da OV e a data do evento só em dtStat
        ovRow(['OV' => '9700', 'dtStat' => '2026-03-05', 'dhStat' => '2026-09-20 14:20:10']),
        ovRow(['OV' => '9700', 'idNum' => 2, 'numStat' => 28, 'dtStat' => '2026-03-09', 'dhStat' => '1900-01-01 09:00:00']),
        ovRow(['OV' => '9700', 'idNum' => 3, 'numStat' => 50, 'dtStat' => '2026-03-12', 'dhStat' => '08:15:00']),
    ], ['9700' => $note->id]);

    expect(HistoricNote::orderBy('id_num')->pluck('dt_stats')->map->format('Y-m-d H:i:s')->all())
        ->toBe(['2026-03-05 14:20:10', '2026-03-09 09:00:00', '2026-03-12 08:15:00']);
});

it('o comando recalcula tudo e corrige fases calculadas por regra antiga', function () {
    $note = phaseNote('9800', [[27, '2026-02-01 08:00:00'], [28, '2026-02-04 08:00:00'], [50, '2026-02-20 08:00:00']]);
    // linha errada, como as que uma regra antiga deixou (término no último status)
    OvPhaseDuration::create(['note_id' => $note->id, 'phase' => 27, 'started_at' => '2026-02-01 08:00:00', 'ended_at' => '2026-02-20 08:00:00', 'days' => 19]);

    $this->artisan('sicode:rebuild_ov_phases')->assertSuccessful();

    expect(OvPhaseDuration::where('phase', 27)->sole()->days)->toBe(3.0)
        ->and(OvPhaseDuration::where('phase', 28)->sole()->days)->toBe(16.0);
});

it('no SP ordena pela data e pega a data imediatamente acima, ignorando a numeração', function () {
    // numeração fora de sequência: o id_num 2 (status 50) é o mais recente por data
    $events = [[1, 27, '2026-03-04 08:00:00'], [2, 50, '2026-03-30 08:00:00'], [3, 28, '2026-03-10 08:00:00']];
    $make   = function (string $ov) use ($events) {
        $note = Note::create(['note' => $ov, 'type_note' => 2]);

        foreach ($events as [$idNum, $stat, $at]) {
            HistoricNote::create(['note_id' => $note->id, 'id_num' => $idNum, 'num_stat' => $stat, 'dt_stats' => $at]);
        }

        return $note;
    };

    $es = $make('9910');
    config(['sicode.ruleset' => 'es']);
    app(OvPhaseDurationService::class)->rebuildForNotes([$es->id]);
    // ES: próximo id_num acima do 1 é o 2 (30/03): 26 dias
    expect(OvPhaseDuration::where('note_id', $es->id)->where('phase', 27)->sole()->days)->toBe(26.0);

    $sp = $make('9911');
    config(['sicode.ruleset' => 'sp']);
    app(OvPhaseDurationService::class)->rebuildForNotes([$sp->id]);
    // SP: por data, depois do 27 (04/03) vem o 28 (10/03): 6 dias; depois do 28, o 50 (30/03): 20 dias
    expect(OvPhaseDuration::where('note_id', $sp->id)->where('phase', 27)->sole()->days)->toBe(6.0)
        ->and(OvPhaseDuration::where('note_id', $sp->id)->where('phase', 28)->sole()->days)->toBe(20.0);
});

it('no SP o último 27 é o mais recente por data', function () {
    config(['sicode.ruleset' => 'sp']);
    $note = Note::create(['note' => '9912', 'type_note' => 2]);

    foreach ([[5, 27, '2026-03-04 08:00:00'], [1, 10, '2026-03-06 08:00:00'], [2, 27, '2026-03-20 08:00:00'], [9, 28, '2026-03-25 08:00:00']] as [$idNum, $stat, $at]) {
        HistoricNote::create(['note_id' => $note->id, 'id_num' => $idNum, 'num_stat' => $stat, 'dt_stats' => $at]);
    }

    app(OvPhaseDurationService::class)->rebuildForNotes([$note->id]);

    $survey = OvPhaseDuration::where('note_id', $note->id)->where('phase', 27)->sole();
    expect($survey->started_at->toDateString())->toBe('2026-03-20')->and($survey->days)->toBe(5.0);
});

it('guarda as fases de todas as OVs, inclusive 800, rubricas e seguidas do 99, com o status que encerra', function () {
    $events = [[27, '2026-03-01 08:00:00'], [28, '2026-03-04 08:00:00'], [99, '2026-03-10 08:00:00']];

    $prefix = phaseNote('8000001', $events);
    $rubric = phaseNote('7000002', $events);
    $rubric->update(['rubrica' => 'Acompanhamento']);

    app(OvPhaseDurationService::class)->rebuildForNotes([$prefix->id, $rubric->id]);

    expect(OvPhaseDuration::count())->toBe(4)
        ->and(OvPhaseDuration::where('note_id', $prefix->id)->where('phase', 27)->sole()->next_stat)->toBe(28)
        ->and(OvPhaseDuration::where('note_id', $prefix->id)->where('phase', 28)->sole()->next_stat)->toBe(99);
});

it('expurga na consulta o que foi escolhido: rubrica, prefixo do número e status que encerra a fase', function () {
    $events = [[27, '2026-03-01 08:00:00'], [28, '2026-03-04 08:00:00'], [50, '2026-03-09 08:00:00']];
    $cancel = [[27, '2026-03-01 08:00:00'], [28, '2026-03-04 08:00:00'], [99, '2026-03-10 08:00:00']];

    $kept = phaseNote('7000001', $events);
    $kept->update(['rubrica' => 'Mercado']);
    $prefix = phaseNote('8000001', $events);
    $follow = phaseNote('7000002', $events);
    $follow->update(['rubrica' => 'Acompanhamento']);
    $incorp = phaseNote('7000003', $events);
    $incorp->update(['rubrica' => 'Incoporação']);
    $ignored = phaseNote('7000004', $events);
    $ignored->update(['rubrica' => 'Desconsiderar']);
    $cancelled = phaseNote('7000005', $cancel);

    app(OvPhaseDurationService::class)->rebuildForNotes(Note::pluck('id'));

    $total = fn (array $exclude, int $phase = 27) => app(\App\Services\Indicators\ProjectDeadlineAnalytics::class)
        ->report(['from' => '2026-01-01', 'to' => '2026-12-31', 'exclude' => $exclude], withDetail: false)['phases'][$phase]['summary']['total'];

    expect($total([]))->toBe(6)                                                         // nada expurgado: todas
        ->and($total(['rubrics' => ['Acompanhamento']]))->toBe(5)
        ->and($total(['rubrics' => ['Acompanhamento', 'Incoporação', 'Desconsiderar']]))->toBe(3)  // multiescolha
        ->and($total(['prefixes' => ['800']]))->toBe(5)
        ->and($total(['rubrics' => ['Acompanhamento'], 'prefixes' => ['800']]))->toBe(4)
        ->and($total(['next_stats' => [99]], 28))->toBe(5)                              // só a fase 28 da OV cancelada é seguida do 99
        ->and($total(['next_stats' => [99]], 27))->toBe(6)                              // o levantamento dela é seguido do 28
        ->and($total(['rubrics' => ['Mercado']]))->toBe(5);                             // a rubrica vazia (null) nunca é expurgada
});

it('o expurgo escolhido também vale na lista e por empresa, e o retrato de agora respeita rubrica e prefixo', function () {
    $company = Company::create(['name' => 'EDP SP', 'email' => 'sp@example.test']);
    $service = Service::create(['service' => 'LEVANTAMENTO', 'status' => 27]);

    foreach ([['7100001', 'Mercado'], ['8100001', 'Mercado'], ['7100002', 'Acompanhamento']] as [$ov, $rubric]) {
        $note = phaseNote($ov, [[27, '2026-03-01 08:00:00'], [28, '2026-03-04 08:00:00']]);
        $note->update(['rubrica' => $rubric]);
        Production::forceCreate(['note_id' => $note->id, 'service_id' => $service->uuid, 'company_id' => $company->id, 'completed' => true, 'completed_at' => '2026-03-04 08:00:00']);
        app(OvPhaseDurationService::class)->rebuildForNotes([$note->id]);
    }

    $analytics = app(\App\Services\Indicators\ProjectDeadlineAnalytics::class);
    $all       = $analytics->report(['from' => '2026-01-01', 'to' => '2026-12-31']);
    $some      = $analytics->report(['from' => '2026-01-01', 'to' => '2026-12-31', 'exclude' => ['rubrics' => ['Acompanhamento'], 'prefixes' => ['8']]]);

    expect(count($all['detail']))->toBe(3)
        ->and(count($some['detail']))->toBe(1)
        ->and($some['detail'][0]['note'])->toBe('7100001')
        ->and($some['companies'][0][27]['total'])->toBe(1);

    Note::create(['note' => '7100003', 'type_note' => 2, 'nstats' => '27', 'rubrica' => 'Mercado']);
    Note::create(['note' => '8100003', 'type_note' => 2, 'nstats' => '27', 'rubrica' => 'Mercado']);
    Note::create(['note' => '7100004', 'type_note' => 2, 'nstats' => '28', 'rubrica' => 'Incoporação']);
    \Illuminate\Support\Facades\Cache::flush();

    expect($analytics->report([], withDetail: false)['status_now'])->toBe(['s27' => 2, 's28' => 1, 'total' => 3])
        ->and($analytics->report(['exclude' => ['rubrics' => ['Incoporação'], 'prefixes' => ['8']]], withDetail: false)['status_now'])->toBe(['s27' => 1, 's28' => 0, 'total' => 1]);
});

it('a tela guarda o expurgo escolhido, repassa ao Excel e oferece as opções vindas da base', function () {
    \Illuminate\Support\Facades\Queue::fake();
    \Illuminate\Support\Facades\Cache::flush();

    Note::create(['note' => '8200001', 'type_note' => 2, 'rubrica' => 'Acompanhamento']);
    Note::create(['note' => '7200001', 'type_note' => 2, 'rubrica' => 'Incoporação']);
    $note = phaseNote('7200002', [[27, '2026-03-01 08:00:00'], [28, '2026-03-04 08:00:00'], [99, '2026-03-10 08:00:00']]);
    app(OvPhaseDurationService::class)->rebuildForNotes([$note->id]);

    $options = \App\Services\Indicators\OvPhaseExclusions::options();
    expect(array_column($options['rubrics'], 'value'))->toContain('Acompanhamento', 'Incoporação')
        ->and(array_column($options['prefixes'], 'value'))->toContain('820', '720')
        ->and(array_column($options['next_stats'], 'value'))->toContain('28', '99');

    $manager = \App\Models\User::factory()->create(['management' => true, 'superadm' => false]);

    \Livewire\Livewire::actingAs($manager)
        ->test(\App\Http\Livewire\Reports\ProjectDeadlineDashboard::class)
        ->set('excludeRubrics', ['Acompanhamento', 'Incoporação'])
        ->set('excludePrefixes', ['800'])
        ->set('excludeStatuses', ['99'])
        ->call('applyExclusions')
        ->assertDispatchedBrowserEvent('my-panel:charts')
        ->call('exportToExcel');

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\Reports\ProjectDeadlineExportJob::class, fn ($job) => $job->params['exclude'] === [
        'rubrics' => ['Acompanhamento', 'Incoporação'], 'prefixes' => ['800'], 'next_stats' => [99],
    ]);
});

it('normaliza o expurgo: tira vazios e repetidos e só aceita status numérico', function () {
    expect(\App\Services\Indicators\OvPhaseExclusions::normalize(['rubrics' => [' A ', 'A', ''], 'prefixes' => ['800', null], 'next_stats' => ['99', 'x', '99']]))
        ->toBe(['rubrics' => ['A'], 'prefixes' => ['800'], 'next_stats' => [99]]);
});
