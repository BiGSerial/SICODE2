<?php

use App\Exports\Home\MyPanel\MyPanelExport;
use App\Http\Livewire\Home\Dashboard\MyPanel;
use App\Jobs\Home\PersonalProductionsJob;
use App\Models\{Company, Note, Production, Service, User};
use App\Notifications\SystemNotification;
use App\Services\Home\MyPanel\MyPanelAnalytics;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, DB, Notification, Storage};
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

// "Meu painel" (home): atividades abertas por tempo (ATT / informe nas pós-obra), produtividade por atividade e o Excel.

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-10-25 12:00:00');
    Cache::flush();
    Notification::fake();
    Storage::fake('local');
});

afterEach(fn () => Carbon::setTestNow());

function panelFixture(): array
{
    $company = Company::query()->create(['name' => 'Empresa Painel', 'email' => 'painel@example.com']);
    $me      = User::factory()->create(['company_id' => $company->id, 'name' => 'Rafael Executor']);
    $other   = User::factory()->create(['company_id' => $company->id, 'name' => 'Outra Pessoa']);

    $analysis = Service::query()->create(['service' => 'Análise', 'folder' => 'analises', 'project' => true]);
    $fiscal   = Service::query()->create(['service' => 'Fiscalização', 'folder' => 'fiscalizacao', 'construction' => true]);

    return compact('company', 'me', 'other', 'analysis', 'fiscal');
}

function panelProduction(array $f, User $user, Service $service, array $attrs = [], ?string $informedAt = null): Production
{
    static $n = 0;
    $note     = Note::query()->create(['note' => 'PN-' . ++$n, 'type_note' => 1]);

    if ($informedAt) {
        DB::table('work_reports')->insert(['note_id' => $note->id, 'company_id' => $f['company']->id, 'informed_at' => $informedAt, 'canceled' => false, 'rejected' => false, 'created_at' => now(), 'updated_at' => now()]);
    }

    return Production::query()->create($attrs + [
        'note_id'     => $note->id,
        'service_id'  => $service->uuid,
        'company_id'  => $f['company']->id,
        'user_id'     => $user->id,
        'dispatch_at' => '2026-10-01 08:00:00',
        'att_at'      => '2026-10-10 08:00:00',
        'status'      => 2,
        'completed'   => false,
        'rejected'    => false,
        'd5'          => false,
    ]);
}

it('lista as abertas da mais antiga para a mais recente, só do usuário, contando do informe nas pós-obra', function () {
    $f = panelFixture();

    $recent  = panelProduction($f, $f['me'], $f['analysis'], ['att_at' => '2026-10-22 08:00:00']);          // 3 dias
    $oldAtt  = panelProduction($f, $f['me'], $f['analysis'], ['att_at' => '2026-10-05 08:00:00']);          // 20 dias
    $postObr = panelProduction($f, $f['me'], $f['fiscal'], ['att_at' => '2026-10-24 08:00:00'], '2026-09-25 10:00:00'); // informe: 30 dias
    panelProduction($f, $f['other'], $f['analysis'], ['att_at' => '2026-01-01 08:00:00']);                  // de outra pessoa
    panelProduction($f, $f['me'], $f['analysis'], ['att_at' => '2026-10-01 08:00:00', 'rejected' => true]); // rejeitada
    panelProduction($f, $f['me'], $f['analysis'], ['att_at' => '2026-10-01 08:00:00', 'completed' => true, 'completed_at' => '2026-10-20 08:00:00']);

    $snapshot = app(MyPanelAnalytics::class)->snapshot($f['me']->id, []);

    expect(collect($snapshot['open'])->pluck('production_id')->all())->toBe([$postObr->id, $oldAtt->id, $recent->id])
        ->and(collect($snapshot['open'])->pluck('age_days')->all())->toBe([30, 20, 3])
        ->and($snapshot['open'][0]['basis'])->toBe('informe')
        ->and($snapshot['open'][0]['att_at']->toDateString())->toBe('2026-10-24')
        ->and($snapshot['open'][1]['basis'])->toBe('ATT')
        ->and($snapshot['summary']['open'])->toBe(3)
        ->and($snapshot['summary']['oldest'])->toBe(30)
        ->and($snapshot['next']['production_id'])->toBe($postObr->id)
        ->and(collect($snapshot['open_by'])->pluck('service')->all())->toBe(['Fiscalização', 'Análise']);
});

it('deixa a RI de fora até o filtro ser ligado', function () {
    $f = panelFixture();
    panelProduction($f, $f['me'], $f['analysis'], ['d5' => true, 'att_at' => '2026-10-23 08:00:00']);
    panelProduction($f, $f['me'], $f['analysis']);

    $analytics = app(MyPanelAnalytics::class);

    expect($analytics->snapshot($f['me']->id, [])['summary']['open'])->toBe(1)
        ->and($analytics->snapshot($f['me']->id, ['include_ri' => true])['summary']['open'])->toBe(2)
        ->and($analytics->snapshot($f['me']->id, ['include_ri' => true])['summary']['ri'])->toBe(1);
});

it('soma a produtividade por atividade no período', function () {
    $f = panelFixture();

    // Eu: 2 notas em 2 dias na Análise; a outra pessoa: 1 nota em 1 dia.
    panelProduction($f, $f['me'], $f['analysis'], ['completed' => true, 'completed_at' => '2026-10-20 10:00:00', 'postes_u' => 3]);
    panelProduction($f, $f['me'], $f['analysis'], ['completed' => true, 'completed_at' => '2026-10-21 10:00:00', 'postes_u' => 2]);
    panelProduction($f, $f['other'], $f['analysis'], ['completed' => true, 'completed_at' => '2026-10-20 11:00:00', 'postes_u' => 9]);
    // Concluída no período anterior (06 a 15/10): conta só na comparação.
    panelProduction($f, $f['me'], $f['analysis'], ['completed' => true, 'completed_at' => '2026-10-12 10:00:00']);

    $snapshot = app(MyPanelAnalytics::class)->snapshot($f['me']->id, ['from' => '2026-10-16', 'to' => '2026-10-25']);
    $row      = $snapshot['activities'][0];

    expect($row['service'])->toBe('Análise')
        ->and($row['notas'])->toBe(2)
        ->and($row['postes'])->toBe(5)
        ->and($row['dias'])->toBe(2)
        ->and($row['per_day'])->toBe(1.0)
        ->and($snapshot['summary']['done'])->toBe(2)
        ->and($snapshot['summary']['done_prev'])->toBe(1)
        ->and($snapshot['summary']['done_delta'])->toBe(100.0)
        ->and(array_sum($snapshot['daily']['completed']))->toBe(2)
        ->and($snapshot['daily']['labels'])->toHaveCount(10);
});

it('renderiza a home, troca o período e manda os gráficos para atualização in-place', function () {
    $f = panelFixture();
    panelProduction($f, $f['me'], $f['analysis'], ['att_at' => '2026-10-05 08:00:00']);

    $this->actingAs($f['me']);

    Livewire::test(MyPanel::class)
        ->assertSee('Meu painel')
        ->assertSee('Rafael')
        ->assertSee('Mais antiga em aberto')
        ->assertSee('PN-')
        ->assertSet('preset', '30d')
        ->call('setPreset', '7d')
        ->assertSet('dt_in', '2026-10-19')
        ->assertDispatchedBrowserEvent('my-panel:charts')
        ->set('dt_in', '2026-10-01')
        ->assertSet('preset', 'custom');
});

it('mostra o estado vazio quando não há nada com o usuário', function () {
    $f = panelFixture();
    $this->actingAs($f['me']);

    Livewire::test(MyPanel::class)->assertSee('Nada com você nos filtros atuais');
});

it('gera o Excel com as abas do painel e as abertas da mais antiga para a mais recente', function () {
    $f = panelFixture();
    panelProduction($f, $f['me'], $f['analysis'], ['att_at' => '2026-10-22 08:00:00']);
    panelProduction($f, $f['me'], $f['fiscal'], ['att_at' => '2026-10-24 08:00:00'], '2026-09-25 10:00:00');
    panelProduction($f, $f['me'], $f['analysis'], ['completed' => true, 'completed_at' => '2026-10-20 10:00:00', 'postes_u' => 3]);

    $report = app(MyPanelAnalytics::class)->report($f['me']->id, ['from' => '2026-10-01', 'to' => '2026-10-25']);
    Excel::store(new MyPanelExport($report, 'Rafael Executor'), 'painel.xlsx', 'local');

    $book = IOFactory::load(Storage::disk('local')->path('painel.xlsx'));

    expect($book->getSheetNames())->toBe(['Resumo', 'Abertas', 'Abertas por atividade', 'Por situação', 'Produtividade', 'Etapas', 'Diário', 'Mensal', 'Concluídas']);

    $open = $book->getSheetByName('Abertas')->toArray(null, true, false, false);
    expect($open[3][1])->toBe('Atividade')
        ->and($open[4][1])->toBe('Fiscalização')   // informe há 30 dias vem antes da Análise (3 dias)
        ->and($open[4][10])->toEqual(30)
        ->and($open[5][1])->toBe('Análise');

    $done = $book->getSheetByName('Concluídas')->toArray(null, true, false, false);
    expect($done[4][1])->toBe('Análise')->and($done[4][10])->toEqual(3);
});

it('enfileira o Excel pelo job, grava no disco local e avisa o usuário', function () {
    $f = panelFixture();
    panelProduction($f, $f['me'], $f['analysis']);

    PersonalProductionsJob::dispatchSync(['service' => [], 'dt_init' => '2026-10-01', 'dt_end' => '2026-10-25', 'include_ri' => false], $f['me']->id);

    $files = Storage::disk('local')->files('exports');

    expect($files)->toHaveCount(1)->and($files[0])->toEndWith('_meu_painel.xlsx');

    // O link da notificação é a rota assinada de download do sistema e o próprio dono consegue baixar.
    $link = null;
    Notification::assertSentTo($f['me'], SystemNotification::class, function ($notification) use (&$link, $f) {
        preg_match('#https?://[^"\\s<]+/exports/[^"\\s<]+#', str_replace('\\/', '/', json_encode($notification->toDatabase($f['me']))), $m);
        $link = $m[0] ?? null;

        return true;
    });

    expect($link)->toContain('/exports/' . $f['me']->id . '/');
    $this->actingAs($f['me'])->get($link)->assertOk()->assertDownload(basename($files[0]));
});
