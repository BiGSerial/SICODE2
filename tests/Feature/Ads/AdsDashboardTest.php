<?php

use App\Services\Ads\{AdsConnectionProbe, AdsDashboardAnalytics, AdsDashboardCharts};
use App\Services\Reports\AdsRequestedReportService;
use Illuminate\Support\Facades\Cache;

const FLOW = [
    'labels'       => ['25/10', '26/10'], 'date_keys' => ['2026-10-25', '2026-10-26'], 'requested' => [3, 5], 'delivered' => [2, 4],
    'open_backlog' => [1, 2], 'overdue_backlog' => [0, 1], 'bucket_label' => 'diária',
    'analytics'    => ['requested_total' => 8, 'delivered_total' => 6, 'completion_rate' => 75.0, 'backlog_avg' => 1.5, 'backlog_peak' => 2, 'overdue_avg' => 0.5, 'current_open' => 2, 'current_overdue' => 1],
];

const QUEUE = ['labels' => ['Na Fila'], 'status_keys' => ['QUEUED'], 'values' => [4], 'colors' => ['#6b7280'], 'total' => 4];

const REUSE = ['labels' => ['Reaproveitadas', 'Novas'], 'values' => [3, 1], 'colors' => ['#059669', '#3b82f6'], 'total' => 4, 'reused' => 3, 'queued' => 1, 'reuse_rate' => 75.0];

function fakeProbe(bool $local, bool $live): AdsConnectionProbe
{
    $probe = Mockery::mock(AdsConnectionProbe::class);
    $probe->shouldReceive('status')->andReturn([
        AdsConnectionProbe::LOCAL => ['label' => 'Banco local', 'up' => $local, 'checked_at' => now(), 'error' => null],
        AdsConnectionProbe::LIVE  => ['label' => 'SQL Server', 'up' => $live, 'checked_at' => now(), 'error' => null],
    ]);

    return $probe;
}

function fakeService(array $overrides = []): AdsRequestedReportService
{
    $service = Mockery::mock(AdsRequestedReportService::class);
    $service->shouldReceive('summarize')->andReturn($overrides['summarize'] ?? ['opened_count' => 8]);
    $service->shouldReceive('demandVsDeliverySeries')->andReturn(FLOW);
    $service->shouldReceive('reuseEconomyDonutSeries')->andReturn(REUSE);

    if (isset($overrides['queue'])) {
        $overrides['queue']($service);
    }

    return $service;
}

it('testa a conexão com o SQL Server em poucos segundos e guarda o resultado em cache', function () {
    Cache::flush();
    config(['database.connections.sqlsrv2.host' => '127.0.0.1', 'database.connections.sqlsrv2.port' => 1]);

    $probe = app(AdsConnectionProbe::class);
    $start = microtime(true);
    $first = $probe->status(true);

    expect(microtime(true) - $start)->toBeLessThan(5)
        ->and($first[AdsConnectionProbe::LOCAL]['up'])->toBeTrue()
        ->and($first[AdsConnectionProbe::LIVE]['up'])->toBeFalse();

    $start  = microtime(true);
    $second = $probe->status();

    expect(microtime(true) - $start)->toBeLessThan(0.5)
        ->and($second[AdsConnectionProbe::LIVE]['up'])->toBeFalse();
});

it('desabilita só a fila quando o SQL Server está fora do ar e nem tenta consultá-lo', function () {
    $service = fakeService(['queue' => function ($mock) {
        $mock->shouldNotReceive('queueDonutSeries');
        $mock->shouldNotReceive('paginateQueue');
    }]);

    $analytics = new AdsDashboardAnalytics($service, fakeProbe(true, false));
    $snapshot  = $analytics->snapshot([]);

    expect($snapshot['summary']['state'])->toBe('ok')
        ->and($snapshot['flow']['state'])->toBe('ok')
        ->and($snapshot['reuse']['state'])->toBe('ok')
        ->and($snapshot['queue']['state'])->toBe('disabled')
        ->and($snapshot['queue']['reason'])->toContain('SQL Server')
        ->and($analytics->queueRows([], 20)['data'])->toBeNull();
});

it('desabilita a seção e marca a conexão como fora do ar quando a consulta ao vivo falha', function () {
    $probe = fakeProbe(true, true);
    $probe->shouldReceive('markDown')->once()->with(AdsConnectionProbe::LIVE, Mockery::type(Throwable::class));

    $service = fakeService(['queue' => fn ($mock) => $mock->shouldReceive('queueDonutSeries')->andThrow(new RuntimeException('timeout'))]);

    $snapshot = (new AdsDashboardAnalytics($service, $probe))->snapshot([]);

    expect($snapshot['queue']['state'])->toBe('disabled')
        ->and($snapshot['flow']['state'])->toBe('ok');
});

it('pede a rosca da fila só ao vivo, para a falha subir em vez de mostrar o espelho defasado', function () {
    $service = fakeService(['queue' => fn ($mock) => $mock->shouldReceive('queueDonutSeries')->once()
        ->with(Mockery::on(fn (array $f) => ($f['live_queue_only'] ?? false) === true))->andReturn(QUEUE)]);

    $snapshot = (new AdsDashboardAnalytics($service, fakeProbe(true, true)))->snapshot([]);

    expect($snapshot['queue']['state'])->toBe('ok')->and($snapshot['queue']['data']['total'])->toBe(4);
});

it('mantém a mesma estrutura de datasets nos gráficos, com dados ou sem conexão', function () {
    $online = (new AdsDashboardAnalytics(fakeService(['queue' => fn ($m) => $m->shouldReceive('queueDonutSeries')->andReturn(QUEUE)]), fakeProbe(true, true)))->snapshot([]);
    $down   = (new AdsDashboardAnalytics(fakeService(['queue' => fn ($m) => $m->shouldNotReceive('queueDonutSeries')]), fakeProbe(true, false)))->snapshot([]);

    foreach (['backlog' => 4, 'flow' => 4, 'queue' => 1, 'reuse' => 1] as $name => $datasets) {
        expect(AdsDashboardCharts::all($online)[$name]['data']['datasets'])->toHaveCount($datasets)
            ->and(AdsDashboardCharts::all($down)[$name]['data']['datasets'])->toHaveCount($datasets);
    }

    $labels = fn (array $snapshot, string $chart) => array_column(AdsDashboardCharts::all($snapshot)[$chart]['data']['datasets'], 'label');

    expect($labels($down, 'flow'))->toBe($labels($online, 'flow'))
        ->and($labels($down, 'backlog'))->toBe($labels($online, 'backlog'));
});

it('só envia no evento os gráficos com conexão, preservando o último estado dos demais', function () {
    $down = (new AdsDashboardAnalytics(fakeService(['queue' => fn ($m) => $m->shouldNotReceive('queueDonutSeries')]), fakeProbe(true, false)))->snapshot([]);

    expect(array_keys(AdsDashboardCharts::live($down)))->toBe(['backlog', 'flow', 'reuse']);
});

it('desabilita tudo, sem derrubar, quando o banco local também está fora do ar', function () {
    $service = Mockery::mock(AdsRequestedReportService::class);
    $service->shouldNotReceive('summarize', 'demandVsDeliverySeries', 'reuseEconomyDonutSeries', 'queueDonutSeries');

    $snapshot = (new AdsDashboardAnalytics($service, fakeProbe(false, false)))->snapshot([]);

    expect(collect(['summary', 'flow', 'queue', 'reuse'])->map(fn ($s) => $snapshot[$s]['state'])->unique()->all())->toBe(['disabled'])
        ->and(AdsDashboardCharts::all($snapshot))->toHaveKeys(['backlog', 'flow', 'queue', 'reuse']);
});

function chartComponent(): App\Http\Livewire\Reports\AdsRequestedReport
{
    $analytics = Mockery::mock(AdsDashboardAnalytics::class);
    $analytics->shouldReceive('snapshot')->andReturn(array_fill_keys(['summary', 'flow', 'queue', 'reuse'], ['state' => 'disabled', 'reason' => 'x', 'data' => null]));
    app()->instance(AdsDashboardAnalytics::class, $analytics);

    $component = new App\Http\Livewire\Reports\AdsRequestedReport();
    $component->mount();

    return $component;
}

it('clicar numa barra de concluídas filtra o dia pela data de conclusão', function () {
    $c = chartComponent();
    $c->applyChartFilter('flow', '2026-10-26', 1);

    expect($c->preset)->toBe('custom')->and($c->dateBy)->toBe('completed')
        ->and($c->filters()['completed_in'])->toBe('2026-10-26')->and($c->filters()['completed_out'])->toBe('2026-10-26')
        ->and($c->filters()['date_in'])->toBeNull();
});

it('clicar numa barra de solicitadas ou na linha filtra o dia pela data da solicitação', function () {
    $c = chartComponent();
    $c->applyChartFilter('flow', '2026-10-26', 0);
    expect($c->dateBy)->toBe('request')->and($c->filters()['date_in'])->toBe('2026-10-26');

    $c->applyChartFilter('backlog', '2026-10-27', 0);
    expect($c->filters()['date_in'])->toBe('2026-10-27')->and($c->filters()['date_out'])->toBe('2026-10-27');
});

it('clicar num mês do gráfico mensal filtra o mês inteiro, sem passar de hoje', function () {
    $c = chartComponent();
    $c->applyChartFilter('flow', '2026-03', 0);

    expect($c->dt_in)->toBe('2026-03-01')->and($c->dt_out)->toBe('2026-03-31');

    $c->applyChartFilter('flow', now()->format('Y-m'), 0);

    expect($c->dt_out)->toBe(now()->toDateString());
});

it('clicar nas roscas filtra o status exato e a origem', function () {
    $c = chartComponent();
    $c->applyChartFilter('queue', 'QUEUED');
    $c->applyChartFilter('reuse', 'reused');

    expect($c->statusExact)->toBe('QUEUED')->and($c->filters()['status_exact'])->toBe('QUEUED')
        ->and($c->origin)->toBe('reused')->and($c->filters()['origin'])->toBe('reused');
});

it('ignora chaves de clique inválidas vindas do navegador', function () {
    $c      = chartComponent();
    $before = [$c->dt_in, $c->dt_out, $c->dateBy, $c->statusExact, $c->origin];

    $c->applyChartFilter('flow', '26/10/2026', 0);
    $c->applyChartFilter('flow', "2026-10-26' OR 1=1", 0);
    $c->applyChartFilter('queue', 'DROP', 0);
    $c->applyChartFilter('reuse', 'x', 0);
    $c->applyChartFilter('outro', '2026-10-26', 0);

    expect([$c->dt_in, $c->dt_out, $c->dateBy, $c->statusExact, $c->origin])->toBe($before);
});

it('leva as chaves do clique em cada gráfico e a quantidade na legenda das roscas', function () {
    $snapshot = (new AdsDashboardAnalytics(fakeService(['queue' => fn ($m) => $m->shouldReceive('queueDonutSeries')->andReturn(QUEUE)]), fakeProbe(true, true)))->snapshot([]);
    $charts   = AdsDashboardCharts::all($snapshot);

    expect($charts['flow']['options']['plugins']['mpClick']['keys'])->toBe(FLOW['date_keys'])
        ->and($charts['backlog']['options']['plugins']['mpClick']['keys'])->toBe(FLOW['date_keys'])
        ->and($charts['queue']['options']['plugins']['mpClick']['keys'])->toBe(['QUEUED'])
        ->and($charts['reuse']['options']['plugins']['mpClick']['keys'])->toBe(['reused', 'new'])
        ->and($charts['queue']['data']['labels'][0])->toBe('Na Fila · 4 (100,0%)')
        ->and($charts['reuse']['data']['labels'])->toBe(['Reaproveitadas · 3 (75,0%)', 'Novas · 1 (25,0%)'])
        ->and($charts['flow']['options']['plugins']['mpBarLabels'])->toBe(['outside' => true]);
});
