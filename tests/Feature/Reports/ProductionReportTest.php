<?php

use App\Exports\Reports\Production\ProductionReportExport;
use App\Http\Livewire\Reports\Productions;
use App\Jobs\Reports\{ProductionLegacyLayoutExportJob, ProductionReportExportJob};
use App\Models\{Analise, Company, Note, Production, Service, User};
use App\Services\Reports\Production\{ProductionReportAnalytics, ProductionReportCharts};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, Notification, Queue, Storage};
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

// "Relatório de Produção" (Serviços → Relatórios): lista do mais novo ao mais antigo, filtros completos e indicadores da mesma consulta.

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-10-25 12:00:00');
    Cache::flush();
    Notification::fake();
    Storage::fake('local');
});

afterEach(fn () => Carbon::setTestNow());

function prodFixture(): array
{
    $alpha = Company::query()->create(['name' => 'Alfa Engenharia', 'email' => 'alfa@example.com']);
    $beta  = Company::query()->create(['name' => 'Beta Serviços', 'email' => 'beta@example.com']);

    return [
        'alpha'    => $alpha,
        'beta'     => $beta,
        'viewer'   => User::factory()->create(['company_id' => $alpha->id, 'name' => 'Gerente', 'superadm' => true, 'management' => true, 'contract' => false]),
        'ana'      => User::factory()->create(['company_id' => $alpha->id, 'name' => 'Ana Alfa', 'superadm' => false, 'management' => false]),
        'bruno'    => User::factory()->create(['company_id' => $beta->id, 'name' => 'Bruno Beta', 'superadm' => false, 'management' => false]),
        'analysis' => Service::query()->create(['service' => 'Análise', 'folder' => 'analises', 'project' => true]),
        'fiscal'   => Service::query()->create(['service' => 'Fiscalização', 'folder' => 'fiscalizacao', 'construction' => true]),
    ];
}

/** Produção concluída por padrão; `note`, `conclusion` e `doe` configuram Nota e Análise. */
function prodRow(Company $company, ?User $user, Service $service, array $attrs = [], array $note = [], ?string $conclusion = null): Production
{
    static $n = 0;
    $n++;
    $noteModel = Note::query()->create($note + ['note' => 'PR-' . $n, 'type_note' => 1]);

    $production = Production::query()->create($attrs + [
        'note_id'      => $noteModel->id,
        'service_id'   => $service->uuid,
        'company_id'   => $company->id,
        'user_id'      => $user?->id,
        'dispatch_at'  => '2026-10-01 08:00:00',
        'att_at'       => '2026-10-10 08:00:00',
        'completed_at' => '2026-10-20 10:00:00',
        'status'       => 5,
        'completed'    => true,
        'rejected'     => false,
        'd5'           => false,
    ]);

    if ($conclusion !== null) {
        Analise::query()->create(['production_id' => $production->id, 'conclusion' => $conclusion]);
    }

    return $production;
}

function prodFilters(array $extra = []): array
{
    $a = app(ProductionReportAnalytics::class);

    return $a->normalize($extra + ['from' => '2026-10-01', 'to' => '2026-10-25']);
}

it('lista do mais novo para o mais antigo, com abertas por último', function () {
    $f    = prodFixture();
    $old  = prodRow($f['alpha'], $f['ana'], $f['analysis'], ['completed_at' => '2026-10-02 10:00:00']);
    $new  = prodRow($f['alpha'], $f['ana'], $f['analysis'], ['completed_at' => '2026-10-24 10:00:00']);
    $mid  = prodRow($f['alpha'], $f['ana'], $f['analysis'], ['completed_at' => '2026-10-12 10:00:00']);
    $open = prodRow($f['alpha'], $f['ana'], $f['analysis'], ['completed' => false, 'completed_at' => null]);

    $a = app(ProductionReportAnalytics::class);

    expect($a->listQuery(prodFilters())->pluck('productions.id')->all())->toBe([$new->id, $mid->id, $old->id])
        ->and($a->listQuery(prodFilters(['include_open' => true]))->pluck('productions.id')->all())->toBe([$new->id, $mid->id, $old->id, $open->id]);
});

it('limita ao período da conclusão e esconde RI até ligar o filtro', function () {
    $f = prodFixture();
    prodRow($f['alpha'], $f['ana'], $f['analysis'], ['completed_at' => '2026-09-10 10:00:00']);
    prodRow($f['alpha'], $f['ana'], $f['analysis']);
    prodRow($f['alpha'], $f['ana'], $f['analysis'], ['d5' => true]);
    prodRow($f['alpha'], $f['ana'], $f['analysis'], ['rejected' => true]);

    $a = app(ProductionReportAnalytics::class);

    expect($a->query(prodFilters())->count())->toBe(1)
        ->and($a->query(prodFilters(['include_ri' => true]))->count())->toBe(2);
});

it('filtra por conclusão, órgão externo, meio ambiente e contabilização', function () {
    $f    = prodFixture();
    $doe  = prodRow($f['alpha'], $f['ana'], $f['analysis'], ['ma' => false], ['doe' => true], 'DEPENDE DE ORGAO EXTERNO');
    $ma   = prodRow($f['alpha'], $f['ana'], $f['analysis'], ['ma' => true, 'confirmed' => true], ['doe' => false], 'ISR - LIBERADO');
    $bare = prodRow($f['alpha'], $f['ana'], $f['analysis']);

    $a   = app(ProductionReportAnalytics::class);
    $ids = fn (array $x) => $a->query(prodFilters($x))->pluck('productions.id')->all();

    expect($ids(['conclusions' => ['DEPENDE DE ORGAO EXTERNO']]))->toBe([$doe->id])
        ->and($ids(['conclusions' => [ProductionReportAnalytics::NO_CONCLUSION]]))->toBe([$bare->id])
        ->and($ids(['doe' => 'sim']))->toBe([$doe->id])
        ->and($ids(['doe' => 'nao']))->toEqualCanonicalizing([$ma->id, $bare->id])
        ->and($ids(['ma' => 'sim']))->toBe([$ma->id])
        ->and($ids(['ma' => 'nao']))->toEqualCanonicalizing([$doe->id, $bare->id])
        ->and($ids(['confirmed' => 'sim']))->toBe([$ma->id])
        ->and($ids(['confirmed' => 'nao']))->toEqualCanonicalizing([$doe->id, $bare->id]);
});

it('busca por nota, material, grupo e ordem, com curinga e busca múltipla', function () {
    $f  = prodFixture();
    $a1 = prodRow($f['alpha'], $f['ana'], $f['analysis'], [], ['note' => '4001000001', 'material' => 'LIGAÇÃO NOVA', 'group5' => 'ISR']);
    $a2 = prodRow($f['alpha'], $f['ana'], $f['analysis'], [], ['note' => '4001000002', 'material' => 'ALTERAÇÃO DE CARGA', 'group5' => 'DSR']);
    \Illuminate\Support\Facades\DB::table('orders')->insert(['note_id' => $a2->note_id, 'ordem' => 'OR-777', 'created_at' => now(), 'updated_at' => now()]);

    $a   = app(ProductionReportAnalytics::class);
    $ids = fn (array $x) => $a->query(prodFilters($x))->pluck('productions.id')->all();

    expect($ids(['search' => '4001000001']))->toBe([$a1->id])
        ->and($ids(['search' => 'ALTERAÇÃO DE CARGA']))->toBe([$a2->id])
        ->and($ids(['search' => 'DSR']))->toBe([$a2->id])
        ->and($ids(['search' => 'OR-777']))->toBe([$a2->id])
        ->and($ids(['search' => '40010000*']))->toEqualCanonicalizing([$a1->id, $a2->id])
        ->and($ids(['multisearch' => ['4001000001', 'OR-777']]))->toEqualCanonicalizing([$a1->id, $a2->id])
        ->and($ids(['search' => 'inexistente']))->toBe([]);
});

it('indicadores batem com a lista (mesma consulta)', function () {
    $f = prodFixture();
    prodRow($f['alpha'], $f['ana'], $f['analysis'], ['postes_u' => 2, 'eo' => 1, 'iproject' => 2, 'confirmed' => true, 'completed_at' => '2026-10-20 10:00:00', 'att_at' => '2026-10-19 10:00:00'], ['doe' => true], 'DEPENDE DE ORGAO EXTERNO');
    prodRow($f['alpha'], $f['ana'], $f['analysis'], ['postes_u' => 4, 'ma' => true, 'completed_at' => '2026-10-20 11:00:00', 'att_at' => '2026-10-20 08:00:00'], ['doe' => true], 'ISR - LIBERADO');
    prodRow($f['beta'], $f['bruno'], $f['fiscal'], ['ma' => true, 'completed_at' => '2026-10-21 09:00:00', 'att_at' => null], ['doe' => false], 'ISR - LIBERADO');
    prodRow($f['beta'], $f['bruno'], $f['fiscal'], ['completed_at' => '2026-10-21 09:30:00', 'att_at' => null]);
    prodRow($f['beta'], $f['bruno'], $f['fiscal'], ['completed' => false, 'completed_at' => null]);

    $a    = app(ProductionReportAnalytics::class);
    $snap = $a->assemble(prodFilters(['include_open' => true]));

    expect($snap['summary'])->toMatchArray(['total' => 5, 'done' => 4, 'open' => 1, 'doe' => 2, 'ma' => 2, 'postes' => 3 * 2 + 4, 'counted' => 1, 'people' => 2])
        ->and($snap['summary']['avg_total'])->toEqual((86400 + 3 * 3600) / 2)
        ->and($snap['flags'])->toBe(['doe_only' => 1, 'ma_only' => 1, 'both' => 1, 'none' => 2])
        ->and(collect($snap['conclusions'])->firstWhere('conclusion', 'ISR - LIBERADO'))->toMatchArray(['total' => 2, 'ma' => 2])
        ->and(array_column($snap['services'], 'service'))->toEqualCanonicalizing(['Análise', 'Fiscalização'])
        ->and(array_column($snap['companies'], 'name'))->toEqualCanonicalizing(['Alfa Engenharia', 'Beta Serviços'])
        ->and($snap['series']['unit'])->toBe('day')
        ->and($snap['series']['labels'])->toHaveCount(25)
        ->and(array_sum($snap['series']['done']))->toBe(4)
        ->and($snap['series']['done'][19])->toBe(2)                     // 20/10
        ->and($snap['summary']['total'])->toBe($a->query(prodFilters(['include_open' => true]))->count());
});

it('troca a série diária pela mensal quando o período passa de dois meses', function () {
    $f = prodFixture();
    prodRow($f['alpha'], $f['ana'], $f['analysis'], ['completed_at' => '2026-06-10 10:00:00']);
    prodRow($f['alpha'], $f['ana'], $f['analysis'], ['completed_at' => '2026-10-10 10:00:00']);

    $series = app(ProductionReportAnalytics::class)->assemble(app(ProductionReportAnalytics::class)->normalize(['from' => '2026-05-01', 'to' => '2026-10-25']))['series'];

    expect($series['unit'])->toBe('month')
        ->and($series['labels'])->toBe(['05/2026', '06/2026', '07/2026', '08/2026', '09/2026', '10/2026'])
        ->and($series['done'])->toBe([0, 1, 0, 0, 0, 1]);
});

it('respeita o recorte de empresas do usuário', function () {
    $f = prodFixture();
    prodRow($f['alpha'], $f['ana'], $f['analysis']);
    prodRow($f['beta'], $f['bruno'], $f['fiscal']);

    $a = app(ProductionReportAnalytics::class);

    expect($a->query(prodFilters(['scope' => [$f['alpha']->id]]))->count())->toBe(1)
        ->and($a->query(prodFilters(['scope' => []]))->count())->toBe(0)
        ->and($a->query(prodFilters(['scope' => null]))->count())->toBe(2);
});

it('só calcula indicadores na tela para períodos de até 93 dias e pagina a lista sem contagem nos longos', function () {
    $f = prodFixture();
    prodRow($f['alpha'], $f['ana'], $f['analysis'], ['completed_at' => '2026-10-02 10:00:00'], ['note' => '4005550001']);
    $a = app(ProductionReportAnalytics::class);

    expect($a->screenAllowed($a->normalize(['from' => '2026-07-25', 'to' => '2026-10-25'])))->toBeTrue()      // 93 dias
        ->and($a->screenAllowed($a->normalize(['from' => '2026-07-24', 'to' => '2026-10-25'])))->toBeFalse()   // 94 dias
        ->and($a->screenAllowed($a->normalize([])))->toBeFalse();

    Livewire::actingAs($f['viewer'])->test(Productions::class)
        ->call('setPreset', 'all')
        ->assertSee('Indicadores e gráficos para períodos de até 93 dias')
        ->assertSee('4005550001')
        ->call('setPreset', '30d')
        ->assertDontSee('Indicadores e gráficos para períodos de até');
});

it('mantém fixos a quantidade e a ordem dos datasets de cada gráfico', function () {
    $f     = prodFixture();
    $empty = app(ProductionReportAnalytics::class)->assemble(prodFilters());
    prodRow($f['alpha'], $f['ana'], $f['analysis'], [], ['doe' => true], 'ISR - LIBERADO');
    $full = app(ProductionReportAnalytics::class)->assemble(prodFilters());

    $shape = fn (array $snap) => array_map(fn (array $c) => array_column($c['data']['datasets'], 'label'), ProductionReportCharts::all($snap));

    expect($shape($empty))->toBe($shape($full))
        ->and($shape($full)['evolution'])->toHaveCount(3)
        ->and($shape($full)['flags'])->toHaveCount(1)
        ->and(ProductionReportCharts::all($full)['flags']['data']['datasets'][0]['data'])->toHaveCount(4);
});

it('renderiza a tela com filtros na URL e atualiza o painel ao filtrar', function () {
    $f = prodFixture();
    prodRow($f['alpha'], $f['ana'], $f['analysis'], [], ['note' => '4009990001', 'doe' => true], 'DEPENDE DE ORGAO EXTERNO');
    prodRow($f['alpha'], $f['ana'], $f['analysis'], [], ['note' => '4009990002'], 'ISR - LIBERADO');

    Livewire::actingAs($f['viewer'])->test(Productions::class)
        ->call('loadNext')->call('loadNext')->call('loadNext')
        ->assertSee('Relatório de Produção')
        ->assertSee('4009990001')->assertSee('4009990002')
        ->set('doe', 'sim')
        ->assertSee('4009990001')->assertDontSee('4009990002')
        ->call('focusConclusion', 'DEPENDE DE ORGAO EXTERNO')
        ->assertSet('conclusions', ['DEPENDE DE ORGAO EXTERNO'])
        ->call('cleanAll')
        ->assertSet('doe', '')->assertSet('conclusions', [])->assertSet('preset', '30d')
        ->set('search', '4009990002')
        ->assertSee('4009990002')->assertDontSee('4009990001');
});

it('o mês escolhido vira o período e o atalho "todo o período" tira as datas', function () {
    $f = prodFixture();

    Livewire::actingAs($f['viewer'])->test(Productions::class)
        ->set('monthYear', '2026-08')
        ->assertSet('dt_init', '2026-08-01')->assertSet('dt_end', '2026-08-31')->assertSet('preset', 'custom')
        ->call('setPreset', 'all')
        ->assertSet('dt_init', null)->assertSet('dt_end', null)->assertSet('monthYear', null);
});

it('gera o Excel pelo job, com as abas do relatório e o controle da exportação', function () {
    $f = prodFixture();
    prodRow($f['alpha'], $f['ana'], $f['analysis'], ['completed_at' => '2026-10-02 10:00:00', 'ma' => true], ['note' => '4001111111', 'doe' => true], 'DEPENDE DE ORGAO EXTERNO');
    prodRow($f['alpha'], $f['ana'], $f['analysis'], ['completed_at' => '2026-10-22 14:45:30', 'att_at' => '2026-10-20 08:05:00', 'dt_note' => '2026-10-15 07:30:00'], ['note' => '4002222222'], 'ISR - LIBERADO');

    ProductionReportExportJob::dispatchSync(['from' => '2026-10-01', 'to' => '2026-10-25', 'doe' => ''], $f['viewer']->id);

    $files = Storage::disk('local')->files('exports');
    expect($files)->toHaveCount(1)->and($files[0])->toEndWith('_relatorio_producao.xlsx');

    $book = IOFactory::load(Storage::disk('local')->path($files[0]));

    expect($book->getSheetNames())->toBe(['Resumo', 'Conclusões', 'Atividades', 'Pessoas', 'Empresas', 'Evolução', 'Registros', 'LayoutAntigo', 'Regras', 'Controle Exportacao']);

    $legacy = $book->getSheetByName('LayoutAntigo')->toArray(null, true, false, false);
    expect($legacy[0])->toHaveCount(59)
        ->and(array_slice($legacy[0], 0, 8))->toBe(['Despachante', 'Empresa', 'Usuario', 'Empresa', 'Serviço', 'Rubrica', 'TipoNota', 'Nota'])
        ->and($legacy)->toHaveCount(3)
        ->and($legacy[1][7])->toEqual('4001111111');     // conclusão mais antiga primeiro, como no layout de sempre

    $records = $book->getSheetByName('Registros')->toArray(null, true, false, false);
    expect(array_slice($records[0], 0, 6))->toBe(['Fim (data)', 'Fim (hora)', 'Início (data)', 'Início (hora)', 'Data do status', 'Hora do status'])
        ->and($records[0][9])->toBe('Nota')
        ->and($records[1][9])->toEqual('4002222222')      // mais recente primeiro
        ->and($records[2][9])->toEqual('4001111111')
        ->and($records[2][13])->toBe('SIM')             // órgão externo
        ->and($records[2][14])->toBe('SIM');            // meio ambiente

    // Data e hora em colunas separadas, como números do Excel (dia inteiro e fração do dia).
    $excelDate = fn (string $day) => \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel(new DateTime($day));
    expect((float) $records[1][0])->toBe((float) $excelDate('2026-10-22'))                       // fim: dia
        ->and(round((float) $records[1][1], 6))->toBe(round((14 * 3600 + 45 * 60 + 30) / 86400, 6))   // fim: 14:45:30
        ->and((float) $records[1][2])->toBe((float) $excelDate('2026-10-20'))                    // início: dia
        ->and(round((float) $records[1][3], 6))->toBe(round((8 * 3600 + 5 * 60) / 86400, 6))
        ->and((float) $records[1][4])->toBe((float) $excelDate('2026-10-15'))                    // dt_note da produção
        ->and(round((float) $records[1][5], 6))->toBe(round((7 * 3600 + 30 * 60) / 86400, 6));

    $control = $book->getSheetByName('Controle Exportacao')->toArray(null, true, false, false);
    expect(collect($control)->pluck(0))->toContain('Usuário', 'E-mail', 'Data/hora', 'Tipo');
});

it('o Excel usa a mesma consulta da lista', function () {
    $f = prodFixture();
    prodRow($f['alpha'], $f['ana'], $f['analysis']);

    $a      = app(ProductionReportAnalytics::class);
    $report = $a->report(['from' => '2026-10-01', 'to' => '2026-10-25']);
    Excel::store(new ProductionReportExport($report, $a->listQuery($report['filters']), 'Gerente', ['Usuário' => 'Gerente']), 'prod.xlsx', 'local');

    $book = IOFactory::load(Storage::disk('local')->path('prod.xlsx'));

    expect($book->getSheetByName('Registros')->getHighestRow())->toBe(1 + $a->query($report['filters'])->count());
});

it('exporta a lista no layout anterior (59 colunas) com o mesmo filtro da tela', function () {
    $f = prodFixture();
    prodRow($f['alpha'], $f['ana'], $f['analysis'], ['completed_at' => '2026-10-02 10:00:00', 'confirmed' => true, 'dispatch_by' => $f['viewer']->id], ['note' => '4001111111'], 'ISR - LIBERADO');
    prodRow($f['alpha'], $f['ana'], $f['analysis'], ['completed_at' => '2026-10-22 14:45:30', 'confirmed' => false], ['note' => '4002222222'], 'ISR - LIBERADO');
    prodRow($f['beta'], $f['bruno'], $f['analysis'], ['completed_at' => '2026-10-22 14:45:30'], ['note' => '4003333333']);

    ProductionLegacyLayoutExportJob::dispatchSync(['from' => '2026-10-01', 'to' => '2026-10-25', 'company' => $f['alpha']->id], $f['viewer']->id);

    $files = Storage::disk('local')->files('exports');
    expect($files)->toHaveCount(1)->and($files[0])->toEndWith('_producao_layout_anterior.xlsx');

    $book = IOFactory::load(Storage::disk('local')->path($files[0]));

    // A lista é a primeira aba, como sempre foi; o controle vai por último.
    expect($book->getSheetNames())->toBe(['Planilha1', 'Controle Exportacao']);

    $rows = $book->getSheet(0)->toArray(null, true, false, false);

    expect($rows[0])->toHaveCount(59)
        ->and(array_slice($rows[0], 0, 8))->toBe(['Despachante', 'Empresa', 'Usuario', 'Empresa', 'Serviço', 'Rubrica', 'TipoNota', 'Nota'])
        ->and($rows[0][12])->toBe('Municipio')
        ->and($rows[0][13])->toBe('Centro')
        ->and($rows[0][14])->toBe('Base')
        ->and($rows[0][16])->toBe('Despachado em')
        ->and($rows[0][33])->toBe('Situação')
        ->and($rows[0][58])->toBe('Status D5')
        ->and($rows)->toHaveCount(3)                       // só a empresa filtrada (2 produções) + cabeçalho
        ->and($rows[1][0])->toBe('Gerente')
        ->and($rows[1][1])->toBe($f['alpha']->name)        // empresa do despachante (exige company_id no eager load)
        ->and($rows[1][33])->toBe('CONFIRMADO')            // o mais antigo primeiro, como no layout de sempre
        ->and($rows[2][33])->toBe('NAO CONFIRMADO');

    $control = $book->getSheetByName('Controle Exportacao')->toArray(null, true, false, false);
    expect(collect($control)->pluck(1))->toContain('Relatório de Produção (layout anterior, 59 colunas)');
});

it('a tela oferece o layout anterior e dispara o job com o filtro atual', function () {
    Queue::fake();
    $f = prodFixture();
    $this->actingAs($f['viewer']);

    Livewire::test(Productions::class)
        ->assertSee('Exportar layout anterior')
        ->call('exportLegacyLayout');

    Queue::assertPushed(ProductionLegacyLayoutExportJob::class, fn ($job) => (string) $job->userId === (string) $f['viewer']->id);
});
