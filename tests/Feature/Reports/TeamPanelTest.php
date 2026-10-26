<?php

use App\Exports\Reports\TeamPanel\TeamPanelExport;
use App\Http\Livewire\Reports\TeamPanel;
use App\Jobs\Reports\TeamPanelExportJob;
use App\Models\{Company, Note, Production, Service, User};
use App\Services\Reports\TeamPanel\TeamPanelAnalytics;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, Notification, Route, Storage};
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

// "Painel da equipe" (Serviços → Relatórios, gerência): o Meu painel sobre a equipe toda, com filtros por empresa, atividade e pessoa.

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-10-25 12:00:00');
    Cache::flush();
    Notification::fake();
    Storage::fake('local');
});

afterEach(fn () => Carbon::setTestNow());

/** A factory sorteia os perfis; aqui eles são explícitos. */
function teamUser(array $attrs): User
{
    return User::factory()->create($attrs + ['superadm' => false, 'management' => false]);
}

function teamFixture(): array
{
    $alpha = Company::query()->create(['name' => 'Alfa Engenharia', 'email' => 'alfa@example.com']);
    $beta  = Company::query()->create(['name' => 'Beta Serviços', 'email' => 'beta@example.com']);

    return [
        'alpha'    => $alpha,
        'beta'     => $beta,
        'manager'  => teamUser(['company_id' => $alpha->id, 'name' => 'Gerente Geral', 'management' => true]),
        'ana'      => teamUser(['company_id' => $alpha->id, 'name' => 'Ana Alfa']),
        'bruno'    => teamUser(['company_id' => $beta->id, 'name' => 'Bruno Beta']),
        'analysis' => Service::query()->create(['service' => 'Análise', 'folder' => 'analises', 'project' => true]),
        'fiscal'   => Service::query()->create(['service' => 'Fiscalização', 'folder' => 'fiscalizacao', 'construction' => true]),
    ];
}

function teamProduction(Company $company, ?User $user, Service $service, array $attrs = []): Production
{
    static $n = 0;
    $note     = Note::query()->create(['note' => 'TP-' . ++$n, 'type_note' => 1]);

    return Production::query()->create($attrs + [
        'note_id'     => $note->id,
        'service_id'  => $service->uuid,
        'company_id'  => $company->id,
        'user_id'     => $user?->id,
        'dispatch_at' => '2026-10-01 08:00:00',
        'att_at'      => '2026-10-10 08:00:00',
        'status'      => 2,
        'completed'   => false,
        'rejected'    => false,
        'd5'          => false,
    ]);
}

it('enxerga a equipe toda e filtra por empresa, atividade e pessoa', function () {
    $f = teamFixture();

    teamProduction($f['alpha'], $f['ana'], $f['analysis'], ['att_at' => '2026-10-05 08:00:00']);   // 20 dias
    teamProduction($f['beta'], $f['bruno'], $f['fiscal'], ['att_at' => '2026-10-20 08:00:00']);    // 5 dias
    teamProduction($f['beta'], null, $f['analysis'], ['att_at' => '2026-10-24 08:00:00']);          // sem responsável
    teamProduction($f['alpha'], $f['ana'], $f['analysis'], ['att_at' => '2026-10-01 08:00:00', 'rejected' => true]);

    $analytics = app(TeamPanelAnalytics::class);
    $all       = $analytics->snapshot([]);

    expect($all['summary']['open'])->toBe(3)
        ->and($all['summary']['unassigned'])->toBe(1)
        ->and($all['summary']['open_people'])->toBe(2)
        ->and($all['summary']['oldest'])->toBe(20)
        ->and($all['next']['user'])->toBe('Ana Alfa')
        ->and(collect($all['open_by_user'])->pluck('name')->all())->toContain('Ana Alfa', 'Bruno Beta', TeamPanelAnalytics::NO_OWNER)
        ->and($analytics->snapshot(['company' => $f['beta']->id])['summary']['open'])->toBe(2)
        ->and($analytics->snapshot(['service' => $f['fiscal']->uuid])['summary']['open'])->toBe(1)
        ->and($analytics->snapshot(['user' => $f['ana']->id])['summary']['open'])->toBe(1);
});

it('compara cada pessoa e empresa com a média da equipe e aponta quem não concluiu nada', function () {
    $f = teamFixture();

    // Ana: 3 notas em 2 dias; Bruno: 1 nota em 1 dia → equipe = 4 notas / 3 pessoa-dias.
    teamProduction($f['alpha'], $f['ana'], $f['analysis'], ['completed' => true, 'completed_at' => '2026-10-20 10:00:00', 'postes_u' => 3]);
    teamProduction($f['alpha'], $f['ana'], $f['analysis'], ['completed' => true, 'completed_at' => '2026-10-20 11:00:00', 'postes_u' => 1]);
    teamProduction($f['alpha'], $f['ana'], $f['analysis'], ['completed' => true, 'completed_at' => '2026-10-21 10:00:00', 'postes_u' => 2]);
    teamProduction($f['beta'], $f['bruno'], $f['analysis'], ['completed' => true, 'completed_at' => '2026-10-20 12:00:00', 'postes_u' => 4]);
    // Concluída no período anterior (06 a 15/10): só compara.
    teamProduction($f['beta'], $f['bruno'], $f['analysis'], ['completed' => true, 'completed_at' => '2026-10-12 10:00:00']);
    // Pessoa com abertas e nenhuma conclusão: aparece como parada.
    $idle = teamUser(['company_id' => $f['beta']->id, 'name' => 'Carla Parada']);
    teamProduction($f['beta'], $idle, $f['fiscal'], ['att_at' => '2026-10-02 08:00:00']);

    $snapshot = app(TeamPanelAnalytics::class)->snapshot(['from' => '2026-10-16', 'to' => '2026-10-25']);
    $users    = collect($snapshot['users'])->keyBy('name');

    expect($snapshot['summary']['done'])->toBe(4)
        ->and($snapshot['summary']['done_prev'])->toBe(1)
        ->and($snapshot['summary']['people'])->toBe(2)
        ->and($snapshot['summary']['idle_people'])->toBe(1)
        ->and($snapshot['summary']['per_person'])->toBe(1.33)
        ->and($users['Ana Alfa']['notas'])->toBe(3)
        ->and($users['Ana Alfa']['dias'])->toBe(2)
        ->and($users['Ana Alfa']['per_day'])->toBe(1.5)
        ->and($users['Bruno Beta']['per_day'])->toBe(1.0)
        ->and($users['Carla Parada']['idle'])->toBeTrue()
        ->and($users['Carla Parada']['open'])->toBe(1)
        ->and($snapshot['users'][0]['name'])->toBe('Ana Alfa')
        ->and(collect($snapshot['companies'])->pluck('name')->all())->toBe(['Alfa Engenharia', 'Beta Serviços'])
        ->and($snapshot['companies'][0]['pessoas'])->toBe(1)
        ->and($snapshot['activities'][0]['pessoas'])->toBe(2)
        ->and(array_sum($snapshot['daily']['people']))->toBe(3);
});

it('protege a rota com a permissão de gerência', function () {
    $f = teamFixture();

    // A página inteira depende do layout (status do banco), que não renderiza no banco de teste vazio: aqui vale o portão.
    expect(Route::getRoutes()->getByName('reports.team_panel')->gatherMiddleware())->toContain('can:management');

    $this->actingAs($f['ana'])->get(route('reports.team_panel'))->assertForbidden();
});

it('renderiza, filtra, limpa os filtros e manda os gráficos para atualização in-place', function () {
    $f = teamFixture();
    teamProduction($f['alpha'], $f['ana'], $f['analysis'], ['att_at' => '2026-10-05 08:00:00']);
    teamProduction($f['beta'], $f['bruno'], $f['fiscal'], ['att_at' => '2026-10-20 08:00:00']);

    $this->actingAs($f['manager']);

    Livewire::test(TeamPanel::class)

        ->assertDontSee('Mais antiga em aberto')
        ->assertDontSee('Ritmo da equipe')
        ->call('loadNext')                       // 1ª parte: abertas (cartão de idade e fila); produção ainda é esqueleto
        ->assertSee('Mais antiga em aberto')
        ->assertDontSee('Ritmo da equipe')
        ->call('loadNext')
        ->call('loadNext')                       // produção
        ->assertSee('Ritmo da equipe')
        ->assertSet('loaded.monthly', false)
        ->call('loadNext')
        ->assertSet('loaded.monthly', true)
        ->assertSee('Painel da equipe')
        ->assertSee('Ana Alfa')
        ->assertSee('Bruno Beta')
        ->set('selectedCompany', $f['beta']->id)
        ->assertDispatchedBrowserEvent('my-panel:charts')
        ->assertSee('Bruno Beta')
        ->assertDontSee('Ana Alfa')
        ->call('focusUser', $f['bruno']->id)
        ->assertSet('selectedUser', $f['bruno']->id)
        ->set('selectedCompany', $f['alpha']->id)      // Bruno não é da Alfa: o filtro de pessoa cai
        ->assertSet('selectedUser', null)
        ->call('clearFilters')
        ->assertSet('selectedCompany', null)
        ->call('setPreset', '7d')
        ->assertSet('dt_in', '2026-10-19');
});

it('barra quem não é gerente no componente', function () {
    $f = teamFixture();
    $this->actingAs($f['ana']);

    Livewire::test(TeamPanel::class)->assertForbidden();
});

it('mostra o estado vazio quando o recorte não tem nada', function () {
    $f = teamFixture();
    $this->actingAs($f['manager']);

    Livewire::test(TeamPanel::class)->call('loadNext')->call('loadNext')->call('loadNext')->call('loadNext')->assertSee('Nada nos filtros atuais');
});

it('gera o Excel com as abas da gerência', function () {
    $f = teamFixture();
    teamProduction($f['alpha'], $f['ana'], $f['analysis'], ['att_at' => '2026-10-05 08:00:00']);
    teamProduction($f['beta'], $f['bruno'], $f['fiscal'], ['att_at' => '2026-10-20 08:00:00']);
    teamProduction($f['alpha'], $f['ana'], $f['analysis'], ['completed' => true, 'completed_at' => '2026-10-20 10:00:00', 'postes_u' => 3]);

    $report = app(TeamPanelAnalytics::class)->report(['from' => '2026-10-01', 'to' => '2026-10-25']);
    Excel::store(new TeamPanelExport($report, 'Gerente Geral'), 'equipe.xlsx', 'local');

    $book = IOFactory::load(Storage::disk('local')->path('equipe.xlsx'));

    expect($book->getSheetNames())->toBe([
        'Resumo', 'Pessoas', 'Empresas', 'Produtividade', 'Etapas', 'Abertas', 'Abertas por pessoa', 'Abertas por atividade', 'Por situação', 'Diário', 'Mensal', 'Pessoa × Atividade', 'Concluídas',
    ]);

    $open = $book->getSheetByName('Abertas')->toArray(null, true, false, false);
    expect($open[3][4])->toBe('Responsável')
        ->and($open[4][4])->toBe('Ana Alfa')       // 20 dias vem antes
        ->and($open[5][4])->toBe('Bruno Beta');

    $people = $book->getSheetByName('Pessoas')->toArray(null, true, false, false);
    expect($people[4][0])->toBe('Ana Alfa')->and($people[4][2])->toEqual(1);

    $matrix = $book->getSheetByName('Pessoa × Atividade')->toArray(null, true, false, false);
    expect($matrix[3])->toContain('Análise');
});

it('enfileira o Excel pelo job para a gerência e recusa quem não é gerente', function () {
    $f = teamFixture();
    teamProduction($f['alpha'], $f['ana'], $f['analysis']);

    TeamPanelExportJob::dispatchSync(['dt_init' => '2026-10-01', 'dt_end' => '2026-10-25', 'include_ri' => false], $f['ana']->id);
    expect(Storage::disk('local')->files('exports'))->toBeEmpty();

    TeamPanelExportJob::dispatchSync(['company' => $f['alpha']->id, 'dt_init' => '2026-10-01', 'dt_end' => '2026-10-25', 'include_ri' => false], $f['manager']->id);
    $files = Storage::disk('local')->files('exports');

    expect($files)->toHaveCount(1)->and($files[0])->toEndWith('_painel_equipe.xlsx');
});

it('entrega as partes em partes e guarda cada uma em cache por filtro', function () {
    $f = teamFixture();
    teamProduction($f['alpha'], $f['ana'], $f['analysis'], ['att_at' => '2026-10-05 08:00:00']);
    teamProduction($f['alpha'], $f['ana'], $f['analysis'], ['completed' => true, 'completed_at' => '2026-10-20 10:00:00']);

    $analytics = app(TeamPanelAnalytics::class);
    $f1        = $analytics->normalize(['from' => '2026-10-01', 'to' => '2026-10-25']);

    $onlyOpen = $analytics->assemble($f1, now(), ['open'], cache: true);

    expect($onlyOpen['summary']['open'])->toBe(1)
        ->and($onlyOpen['summary']['done'])->toBe(0)      // produção ainda não carregada: esqueleto vazio
        ->and($onlyOpen['users'])->toBe([]);

    $all = $analytics->assemble($f1, now(), TeamPanelAnalytics::PARTS, cache: true);

    expect($all['summary']['open'])->toBe(1)
        ->and($all['summary']['done'])->toBe(1)
        ->and($all['summary']['people'])->toBe(1)
        ->and(array_sum($all['monthly']['series']['project']))->toBe(1)
        ->and($all)->toEqual($analytics->snapshot(['from' => '2026-10-01', 'to' => '2026-10-25']));
});
