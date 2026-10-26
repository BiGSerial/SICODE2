<?php

use App\Exports\Closure\Dashboard\{ClosureDashboardExport, DashboardTheme};
use App\Http\Livewire\Closure\Dispatch\Dashboard;
use App\Jobs\Closure\ExportClosureDashboardJob;
use App\Models\Closure\{ClosureOrderState, ClosurePriorityRequest};
use App\Models\{Company, Operation, User};
use App\Notifications\SystemNotification;
use App\Services\Closure\Analytics\{ClosureDashboardReport, ClosureDispatcherAnalytics};
use App\Services\Closure\{ClosureEligibleOrders, ClosureIssueWorkflowService};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Bus, Notification, Storage};
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

require_once __DIR__ . '/ClosureScreenFixture.php';

// Excel do Painel analítico: uma aba por bloco da tela + as atividades detalhadas por status.

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-10-25 12:00:00');
    Notification::fake();
    Storage::fake('local');
    ClosureEligibleOrders::forget();
});

afterEach(fn () => Carbon::setTestNow());

/** Estado da Ordem num status, com responsável/posse e quando entrou no status. */
function dashState(string $status, array $attrs = [], string $statusSist = 'LIB CNPA', ?string $note = null): ClosureOrderState
{
    $f = closureWorkflowFixture($status);

    $f['order']->update(['statusSist' => $statusSist]);
    $f['state']->update($attrs + ['entered_current_status_at' => '2026-10-25 09:00:00']);

    return $f['state']->fresh();
}

/** Cenário com os 4 sinais, uma pendência com a parceira e um pedido de prioridade. */
function dashScenario(): array
{
    $ana = User::factory()->create(['name' => 'Ana Operadora', 'closure_operator' => true]);
    $bia = User::factory()->create(['name' => 'Bia Operadora', 'closure_operator' => true]);

    $assigned = dashState('IN_PROGRESS', ['assigned_user_id' => $ana->id, 'entered_current_status_at' => '2026-10-22 08:00:00']);
    $done     = dashState('FINISHED', ['assigned_user_id' => $ana->id, 'entered_current_status_at' => '2026-10-25 08:00:00']);
    $overdue  = dashState('FINISHED', ['assigned_user_id' => $bia->id, 'entered_current_status_at' => '2026-10-23 08:00:00']);
    $pile     = dashState('AVAILABLE', ['assigned_user_id' => null]);
    $partner  = dashState('IN_PROGRESS', ['assigned_user_id' => $bia->id]);

    ['category' => $c, 'subcategory' => $s] = closureIssueTaxonomy();
    $dispatcher                             = User::factory()->create(['closure_manager' => true, 'name' => 'Gestor Despacho']);
    $issue                                  = app(ClosureIssueWorkflowService::class)->requestToPartner($partner, $c, $s, $partner->Production->Company, $dispatcher, 'Falta MIGO.', null, false);

    ClosurePriorityRequest::query()->create(['note_id' => $assigned->Order->note_id, 'requested_by' => $ana->id, 'reason' => 'Cliente cobrando a obra.', 'status' => ClosurePriorityRequest::STATUS_PENDING]);

    return compact('ana', 'bia', 'assigned', 'done', 'overdue', 'pile', 'partner', 'issue', 'dispatcher');
}

/** Gera o arquivo de verdade e o reabre (com gráficos) para conferir o que o usuário vai ver. */
function dashWorkbook(array $report, string $by = 'Gestor Despacho'): \PhpOffice\PhpSpreadsheet\Spreadsheet
{
    $path = 'exports/teste_painel.xlsx';
    Excel::store(new ClosureDashboardExport($report, $by), $path, 'local');

    $reader = IOFactory::createReader('Xlsx');
    $reader->setIncludeCharts(true);

    return $reader->load(Storage::disk('local')->path($path));
}

// ---------------------------------------------------------------- dados

it('builds every block of the dashboard in flat tables, with all active Orders and the signal counts', function () {
    dashScenario();

    $report = app(ClosureDashboardReport::class)->build([], 7);

    expect($report['summary']['total'])->toBe(5)
        ->and($report['summary']['finished'])->toBe(2)
        ->and($report['signals'])->toMatchArray(['overdue' => 1, 'done' => 1, 'assigned' => 2, 'pile' => 1])
        ->and($report['orders'])->toHaveCount(5)
        ->and($report['priorities_count'])->toBe(1)
        ->and($report['volume']['total'][5])->toBe(5)
        ->and(collect($report['status'])->pluck(1, 0)->all())->toMatchArray(['Em andamento' => 2, 'Finalizado' => 2, 'Disponível' => 1])
        ->and(array_keys($report['orders_by_status']))->toBe(['IN_PROGRESS', 'FINISHED', 'AVAILABLE'])
        ->and($report['partners'])->toHaveCount(1)
        ->and($report['partners'][0][2])->toBe(1)           // aguardando resposta
        ->and($report['users'])->toHaveCount(1)
        ->and($report['priorities'][0][2])->toBe('Cliente cobrando a obra.');
});

it('classifies each Order with the same signal rule as the screens and counts the days in the status', function () {
    $s = dashScenario();

    $byOrdem = collect(app(ClosureDashboardReport::class)->build([])['orders'])->keyBy('ordem');

    expect($byOrdem[$s['overdue']->Order->ordem]['signal_key'])->toBe('overdue')
        ->and($byOrdem[$s['overdue']->Order->ordem]['days'])->toBe(2)
        ->and($byOrdem[$s['done']->Order->ordem]['signal_key'])->toBe('done')
        ->and($byOrdem[$s['pile']->Order->ordem]['signal_key'])->toBe('pile')
        ->and($byOrdem[$s['pile']->Order->ordem]['responsible'])->toBe('Sem responsável (pilha)')
        ->and($byOrdem[$s['assigned']->Order->ordem]['signal_key'])->toBe('assigned')
        ->and($byOrdem[$s['assigned']->Order->ordem]['days'])->toBe(3);
});

it('respects the dashboard filters (company and period) and describes them', function () {
    $s       = dashScenario();
    $company = $s['partner']->CurrentHolderCompany;

    $report = app(ClosureDashboardReport::class)->build(['company_id' => $company->id, 'from' => '2026-10-24', 'to' => '2026-10-25'], 7);

    expect($report['orders'])->toHaveCount(1)
        ->and($report['orders'][0]['ordem'])->toBe((string) $s['partner']->Order->ordem)
        ->and($report['filters']['Empresa (posse atual)'])->toBe($company->name)
        ->and($report['filters']['No status desde'])->toBe('24/10/2026')
        ->and($report['filters']['No status até'])->toBe('25/10/2026');
});

// ---------------------------------------------------------------- arquivo

it('writes one tab per dashboard block and one tab per status with activities', function () {
    dashScenario();

    $book  = dashWorkbook(app(ClosureDashboardReport::class)->build([], 7));
    $names = $book->getSheetNames();

    expect($names)->toBe([
        'Resumo', 'Volumetria', 'Por status', 'Matriz', 'Ordens ativas',
        'Atividades · Em andamento', 'Atividades · Finalizado', 'Atividades · Disponível',
        'Parceiras', 'Pendências por usuário', 'Obras críticas', 'Prioridades',
    ]);

    // abas por status só existem quando há atividades naquele status
    expect($names)->not->toContain('Atividades · Pausa')->and($names)->not->toContain('Atividades · Aguard. parceira');
});

it('shows the KPIs as cards, the applied filters and a clickable index of the tabs on the summary', function () {
    $s = dashScenario();

    $book    = dashWorkbook(app(ClosureDashboardReport::class)->build(['type' => null], 7));
    $summary = $book->getSheetByName('Resumo');
    $text    = collect($summary->toArray(null, true, true, false))->flatten()->filter()->implode(' | ');

    expect($summary->getCell('A1')->getValue())->toBe('SICODE · Painel analítico do Encerramento')
        ->and($summary->getCell('A2')->getValue())->toContain('25/10/2026 12:00')->toContain('Gestor Despacho')
        ->and($text)->toContain('ESTOQUE ATIVO')->toContain('SINALIZAÇÃO DAS ORDENS')->toContain('FILTROS APLICADOS')->toContain('ABAS DESTA PLANILHA')
        ->and($text)->toContain('Sem baixa no SAP')->toContain('Na pilha, sem atribuição')->toContain('Obras críticas');

    // cartão "Total ativo": valor em destaque abaixo do rótulo
    expect($summary->getCell('A5')->getValue())->toBe('Total ativo')->and($summary->getCell('A6')->getValue())->toBe(5);

    // índice de abas com link interno
    $links = collect($summary->getHyperlinkCollection())->map(fn ($h) => $h->getUrl())->values();

    expect($links->count())->toBe(11)   // todas as abas, menos o próprio Resumo
        ->and($links->first())->toStartWith("sheet://'");

    expect($summary->getTabColor()->getRGB())->toBe(DashboardTheme::INK);
});

it('colors each activity row by its signal, like the screen, with the data and the volume bars', function () {
    $s = dashScenario();

    $book  = dashWorkbook(app(ClosureDashboardReport::class)->build([], 7));
    $sheet = $book->getSheetByName('Ordens ativas');

    expect($sheet->getCell('A4')->getValue())->toBe('★')
        ->and($sheet->getCell('F4')->getValue())->toBe('Sinal')
        ->and($sheet->getCell('A1')->getValue())->toBe('SICODE · Encerramento · Ordens ativas');

    $rows = [];

    foreach (range(5, 9) as $row) {
        $rows[(string) $sheet->getCell("D{$row}")->getValue()] = [
            'signal' => $sheet->getCell("F{$row}")->getValue(),
            'fill'   => $sheet->getStyle("B{$row}")->getFill()->getStartColor()->getRGB(),
            'edge'   => $sheet->getStyle("A{$row}")->getBorders()->getLeft()->getColor()->getRGB(),
        ];
    }

    $overdue = $rows[(string) $s['overdue']->Order->ordem];
    $pile    = $rows[(string) $s['pile']->Order->ordem];

    expect($overdue['signal'])->toBe('Sem baixa no SAP')
        ->and($overdue['fill'])->toBe('F8D7DA')->and($overdue['edge'])->toBe(DashboardTheme::RED)
        ->and($pile['signal'])->toBe('Na pilha, sem atribuição')->and($pile['fill'])->toBe('FFF3CD')
        ->and($rows[(string) $s['done']->Order->ordem]['fill'])->toBe('D1E7DD')
        ->and($rows[(string) $s['assigned']->Order->ordem]['fill'])->toBe('CFE2FF');

    // cabeçalho congelado e filtro ligado
    expect($sheet->getFreezePane())->toBe('A5')->and($sheet->getAutoFilter()->getRange())->toBe('A4:J4');
});

it('puts native charts on the volume and status tabs and data bars on the volume columns', function () {
    dashScenario();

    $book = dashWorkbook(app(ClosureDashboardReport::class)->build([], 7));

    expect($book->getSheetByName('Volumetria')->getChartCollection())->toHaveCount(1)
        ->and($book->getSheetByName('Por status')->getChartCollection())->toHaveCount(1)
        ->and($book->getSheetByName('Matriz')->getChartCollection())->toHaveCount(0);

    $path = Storage::disk('local')->path('exports/teste_painel.xlsx');
    $zip  = new ZipArchive();
    $zip->open($path);

    $barSheets = 0;

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);

        if (str_starts_with($name, 'xl/worksheets/sheet') && str_contains((string) $zip->getFromIndex($i), '<dataBar>')) {
            $barSheets++;
        }
    }

    $zip->close();

    expect($barSheets)->toBeGreaterThanOrEqual(6);
});

it('gives the volume tab a total row and keeps the percentage consistent', function () {
    dashScenario();

    $sheet = dashWorkbook(app(ClosureDashboardReport::class)->build([], 7))->getSheetByName('Volumetria');

    expect($sheet->toArray(null, true, true, false)[3])->toBe(['Usuário', 'Passivo', 'Meta atual', 'Meta futura', 'Sem tipo', 'Total', '% do total']);

    $lastRow = $sheet->getHighestRow();

    expect($sheet->getCell("A{$lastRow}")->getValue())->toBe('Total geral')
        ->and($sheet->getCell("F{$lastRow}")->getValue())->toBe(5)
        ->and((float) $sheet->getCell("G{$lastRow}")->getValue())->toBe(100.0);
});

it('flattens the matrix by user and type with the totals at the end', function () {
    dashScenario();

    $sheet = dashWorkbook(app(ClosureDashboardReport::class)->build([], 7))->getSheetByName('Matriz');
    $rows  = $sheet->toArray(null, true, true, false);

    expect($rows[3])->toBe(['Usuário', 'Tipo', 'Em andamento', 'Atribuída', 'Pausa', 'Aguard. parceira', 'Dep. bloqueado', 'Dep. liberado', 'Finalizado', 'Aguardando meta', 'Disponível', 'Total']);

    $last = end($rows);

    expect($last[0])->toBe('Total geral')->and((int) $last[11])->toBe(5);
});

it('still builds a readable file when there is nothing to show', function () {
    $book = dashWorkbook(app(ClosureDashboardReport::class)->build([], 7));

    expect($book->getSheetNames())->toBe(['Resumo', 'Volumetria', 'Por status', 'Matriz', 'Ordens ativas', 'Parceiras', 'Pendências por usuário', 'Obras críticas', 'Prioridades'])
        ->and($book->getSheetByName('Ordens ativas')->getCell('A5')->getValue())->toBe('Nenhum registro para os filtros aplicados.')
        ->and($book->getSheetByName('Resumo')->getCell('A6')->getValue())->toBe(0);
});

// ---------------------------------------------------------------- job e tela

it('exports in the background, in the dispatcher filters, and notifies with the file link', function () {
    dashScenario();
    $dispatcher = User::factory()->create(['closure_manager' => true]);

    dispatch_sync(new ExportClosureDashboardJob([], 7, (string) $dispatcher->id));

    expect(Storage::disk('local')->files('exports'))->toHaveCount(1);

    $payload = Notification::sent($dispatcher, SystemNotification::class)->map(fn ($n) => $n->toDatabase($dispatcher))->first();

    expect($payload['title'])->toBe('Exportação - Painel analítico do Encerramento')
        ->and($payload['message'])->toContain('5 Ordens ativas')->toContain('12 abas');
});

it('refuses the export to anyone who is not a dispatcher', function () {
    $operator = User::factory()->create(['closure_operator' => true, 'closure_manager' => false, 'admin' => false, 'superadm' => false]);

    expect(fn () => dispatch_sync(new ExportClosureDashboardJob([], 7, (string) $operator->id)))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    expect(Storage::disk('local')->files('exports'))->toBe([]);
});

it('queues the export from the dashboard with the filters on screen', function () {
    Bus::fake();
    $dispatcher = User::factory()->create(['closure_manager' => true]);
    $company    = Company::query()->create(['name' => 'Empresa X', 'email' => fake()->unique()->safeEmail()]);

    Livewire::actingAs($dispatcher)->test(Dashboard::class)
        ->assertSee('Exportar Excel')
        ->set('filterType', 'P')->set('filterCompany', (string) $company->id)->set('dateFrom', '2026-10-01')->set('staleDays', 10)
        ->call('exportToExcel')
        ->assertDispatchedBrowserEvent('closure:toast');

    Bus::assertDispatched(ExportClosureDashboardJob::class, fn ($job) => $job->filters === ['type' => 'P', 'company_id' => (string) $company->id, 'from' => '2026-10-01', 'to' => null]
        && $job->staleDays === 10 && $job->userId === (string) $dispatcher->id);
});

it('keeps the analytics labels and the export sheet statuses in sync', function () {
    foreach (ClosureDispatcherAnalytics::MATRIX_STATUSES as $code) {
        expect(ClosureDashboardReport::STATUS_SHORT)->toHaveKey($code);
    }

    expect(Operation::query()->count())->toBe(0);
});
