<?php

use App\Http\Livewire\Engineers\PepAlerts;
use App\Models\{City, Company, Note, Order, Partial, Pep, PepBalanceAlert, User, UserRegion, Viability, WorkReport};
use App\Notifications\SystemNotification;
use App\Services\Partner\{PepBudgetWatchService, WorkReportBudgetGuard};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

// Informe sem saldo no PEP não bloqueia: entra na lista de observação da Engenharia.

uses(RefreshDatabase::class);

beforeEach(fn () => config(['sicode.ruleset' => 'es']));

function watchEngineer(array $regions = [], array $companies = [], bool $engineer = true): User
{
    $company = Company::query()->create(['name' => 'Base ' . uniqid(), 'email' => uniqid() . '@example.test']);
    $user    = User::factory()->create(['company_id' => $company->id, 'engineer' => $engineer, 'superadm' => false]);

    foreach ($regions as $region) {
        UserRegion::query()->create(['user_id' => $user->id, 'region' => $region]);
    }

    if ($companies) {
        $user->Companies()->sync($companies);
    }

    return $user;
}

/** @return array{0: WorkReport, 1: Note, 2: Company} */
function watchWorkReport(?float $available, string $region = 'BASE-VIT'): array
{
    static $n = 0;
    $n++;
    $pep = "PEP-W{$n}-2";

    $city = City::query()->firstOrCreate(['rdMunicipio' => 'RD' . $region], ['baseConstrucao' => $region, 'cidade' => 'X', 'municipio' => 'X']);
    $note = Note::query()->create(['note' => '80000' . $n, 'nstats' => 51, 'type_note' => 1, 'nexp' => $city->rdMunicipio]);

    if ($available !== null) {
        Pep::query()->updateOrCreate(['objeto' => $pep], ['disponivel' => $available]);
    }

    $order   = Order::query()->create(['note_id' => $note->id, 'ordem' => '1700' . $n, 'statusSist' => 'ABER', 'pep' => $pep]);
    $company = Company::query()->create(['name' => 'Empreiteira ' . $n, 'email' => "emp{$n}@example.test"]);
    $partner = User::factory()->create(['company_id' => $company->id]);

    $report = WorkReport::query()->create([
        'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $partner->id,
        'date'    => now()->toDateString(), 'informed_at' => now(), 'rejected' => false, 'canceled' => false,
    ]);
    $report->Orders()->sync([$order->id]);

    return [$report->fresh(), $note, $company];
}

it('registra o informe sem saldo, grava a região e avisa só o engenheiro da região', function () {
    Notification::fake();

    $regional = watchEngineer(['BASE-VIT']);
    $outra    = watchEngineer(['BASE-OUTRA']);
    $naoEng   = watchEngineer(['BASE-VIT'], [], false);

    [$report] = watchWorkReport(0.0);

    $alert = app(PepBudgetWatchService::class)->registerWorkReport($report);

    expect($alert)->not->toBeNull()
        ->and($alert->region)->toBe('BASE-VIT')
        ->and($alert->reason)->toBe(PepBalanceAlert::REASON_NO_BALANCE)
        ->and($alert->status)->toBe('open');

    Notification::assertSentTo($regional, SystemNotification::class);
    Notification::assertNotSentTo($outra, SystemNotification::class);
    Notification::assertNotSentTo($naoEng, SystemNotification::class);
});

it('avisa também o engenheiro responsável pela empresa do informe', function () {
    Notification::fake();

    [$report, , $company] = watchWorkReport(-5.0);
    $responsavel          = watchEngineer([], [$company->id]);

    app(PepBudgetWatchService::class)->registerWorkReport($report);

    Notification::assertSentTo($responsavel, SystemNotification::class);
});

it('não registra com saldo, sem PEP sincronizado ou com o monitor desligado (SP)', function () {
    $watch = app(PepBudgetWatchService::class);

    expect($watch->registerWorkReport(watchWorkReport(500.0)[0]))->toBeNull()
        ->and($watch->registerWorkReport(watchWorkReport(null)[0]))->toBeNull();

    config(['sicode.ruleset' => 'sp']);
    expect($watch->registerWorkReport(watchWorkReport(0.0)[0]))->toBeNull()
        ->and(PepBalanceAlert::query()->count())->toBe(0);
});

it('é idempotente: registrar duas vezes mantém um item e avisa uma vez', function () {
    Notification::fake();

    $eng      = watchEngineer(['BASE-VIT']);
    [$report] = watchWorkReport(0.0);
    $watch    = app(PepBudgetWatchService::class);

    $watch->registerWorkReport($report);
    $watch->registerWorkReport($report);

    expect(PepBalanceAlert::query()->count())->toBe(1);
    Notification::assertSentToTimes($eng, SystemNotification::class, 1);
});

it('parcial com ADS acima do saldo entra na lista; dentro do saldo, não', function () {
    [$report, $note] = watchWorkReport(100.0);
    $order           = $report->Orders->first();

    $partial = Partial::query()->create(['note_id' => $note->id, 'company_id' => $report->company_id, 'scope' => 'network', 'value' => 250.0]);
    $partial->Orders()->attach($order->id);

    $alert = app(PepBudgetWatchService::class)->registerPartial($partial);

    expect($alert?->reason)->toBe(PepBalanceAlert::REASON_ADS_EXCEEDS)
        ->and($alert->informe_type)->toBe('partial');

    $ok = Partial::query()->create(['note_id' => $note->id, 'company_id' => $report->company_id, 'value' => 90.0]);
    $ok->Orders()->attach($order->id);

    expect(app(PepBudgetWatchService::class)->registerPartial($ok))->toBeNull();
});

it('o bloqueio segue desligado por padrão: o parceiro não é barrado', function () {
    $note = watchWorkReport(0.0)[1];

    expect(app(WorkReportBudgetGuard::class)->blockedPeps($note->Orders))->toBe([]);
});

it('a pilha aberta esconde informe final cancelado e parcial negada', function () {
    [$report, $note] = watchWorkReport(0.0);
    $order           = $report->Orders->first();
    $partial         = Partial::query()->create(['note_id' => $note->id, 'company_id' => $report->company_id, 'value' => 10.0]);
    $partial->Orders()->attach($order->id);

    $watch = app(PepBudgetWatchService::class);
    $watch->registerWorkReport($report);
    $watch->registerPartial($partial);

    expect($watch->openQuery()->count())->toBe(2);

    $report->forceFill(['canceled' => true])->saveQuietly();
    expect($watch->openQuery()->count())->toBe(1);

    $partial->forceFill(['deny' => true])->save();
    expect($watch->openQuery()->count())->toBe(0)
        ->and(PepBalanceAlert::query()->open()->count())->toBe(2);
});

it('restringe por região ou empresa responsável; sem vínculo vê tudo', function () {
    [$a, , $companyA] = watchWorkReport(0.0, 'BASE-A');
    [$b]              = watchWorkReport(0.0, 'BASE-B');
    $watch            = app(PepBudgetWatchService::class);
    $watch->registerWorkReport($a);
    $watch->registerWorkReport($b);

    $porRegiao  = watchEngineer(['BASE-B']);
    $porEmpresa = watchEngineer([], [$companyA->id]);
    $semVinculo = watchEngineer();

    $count = fn (User $u) => $watch->scopeVisibleTo($watch->openQuery(), $u)->count();

    expect($count($porRegiao))->toBe(1)
        ->and($count($porEmpresa))->toBe(1)
        ->and($count($semVinculo))->toBe(2);
});

it('saída automática: cobre por mais de 48h sem ação vai para o histórico; saldo que volta a faltar zera a contagem', function () {
    [$report] = watchWorkReport(0.0);
    $watch    = app(PepBudgetWatchService::class);
    $alert    = $watch->registerWorkReport($report);
    $pep      = Pep::query()->first();

    // Ainda sem saldo: nada muda.
    expect($watch->reconcile()['resolved'])->toBe(0);
    expect($alert->fresh()->covered_since)->toBeNull();

    // Saldo passa a cobrir: começa a contar, mas não sai ainda.
    $pep->update(['disponivel' => 1000]);
    $watch->reconcile();
    expect($alert->fresh()->covered_since)->not->toBeNull()->and($alert->fresh()->status)->toBe('open');

    // Volta a faltar: zera.
    $pep->update(['disponivel' => 0]);
    $watch->reconcile();
    expect($alert->fresh()->covered_since)->toBeNull();

    // Cobre de novo e passa de 48h sem ação.
    $pep->update(['disponivel' => 1000]);
    $watch->reconcile();
    $alert->fresh()->forceFill(['covered_since' => now()->subHours(49)])->save();
    $stats = $watch->reconcile();

    $alert = $alert->fresh();
    expect($stats['resolved'])->toBe(1)
        ->and($alert->status)->toBe('handled')
        ->and($alert->handled_via)->toBe('auto')
        ->and($alert->handled_by)->toBeNull();
});

it('dentro das 48h com saldo cobrindo, o item continua na pilha', function () {
    [$report] = watchWorkReport(0.0);
    $watch    = app(PepBudgetWatchService::class);
    $alert    = $watch->registerWorkReport($report);

    Pep::query()->update(['disponivel' => 1000]);
    $alert->forceFill(['covered_since' => now()->subHours(47)])->save();

    expect($watch->reconcile()['resolved'])->toBe(0)
        ->and($alert->fresh()->status)->toBe('open');
});

it('o engenheiro vê a pilha, marca como tratado e o item vai ao histórico com a observação', function () {
    [$report] = watchWorkReport(0.0);
    $alert    = app(PepBudgetWatchService::class)->registerWorkReport($report);
    $eng      = watchEngineer(['BASE-VIT']);

    Livewire::actingAs($eng)->test(PepAlerts::class)
        ->assertSee($report->Note->note)
        ->call('askHandle', $alert->id)
        ->set('handleNotes', 'Suplementação solicitada')
        ->call('confirmHandle')
        ->assertDontSee('Suplementação solicitada')
        ->call('setTab', 'history')
        ->assertSee('Suplementação solicitada')
        ->assertSee($report->Note->note);

    $alert = $alert->fresh();
    expect($alert->status)->toBe('handled')
        ->and($alert->handled_via)->toBe('engineer')
        ->and($alert->handled_by)->toBe($eng->id);
});

it('engenheiro de outra região não consegue tratar item que não enxerga', function () {
    [$report] = watchWorkReport(0.0, 'BASE-VIT');
    $alert    = app(PepBudgetWatchService::class)->registerWorkReport($report);
    $outro    = watchEngineer(['BASE-OUTRA']);

    Livewire::actingAs($outro)->test(PepAlerts::class)
        ->call('askHandle', $alert->id)
        ->call('confirmHandle');

    expect($alert->fresh()->status)->toBe('open');
});

it('a tela da Engenharia abre para o engenheiro e é negada para quem não é', function () {
    \App\Models\Bancoupdate::create(['last_update' => now(), 'error' => 0, 'inserts' => 0, 'updates' => 0]); // o layout lê a última atualização

    $this->actingAs(watchEngineer(['BASE-VIT']))->get(route('engineers.pep_alerts'))->assertOk()->assertSee('Informes sem saldo no PEP');
    $this->actingAs(watchEngineer([], [], false))->get(route('engineers.pep_alerts'))->assertForbidden();
});

it('o modal do engenheiro mostra o saldo do PEP da parcial', function () {
    [$report, $note] = watchWorkReport(1234.5);
    $partial         = Partial::query()->create(['note_id' => $note->id, 'company_id' => $report->company_id, 'value' => 5000.0]);
    $partial->Orders()->attach($report->Orders->first()->id);

    \Livewire\Livewire::actingAs(watchEngineer(['BASE-VIT']))
        ->test(\App\Http\Livewire\Engineers\Actions\CheckPartial::class)
        ->call('show_form', $partial->id)
        ->assertSee('Saldo do PEP')
        ->assertSee('1.234,50');
});

it('o painel calcula pendentes, valor, idade, ranking e fluxo no mesmo recorte da tela', function () {
    [$a, $noteA, $companyA] = watchWorkReport(0.0, 'BASE-A');
    [$b]                    = watchWorkReport(-3.0, 'BASE-B');
    $watch                  = app(PepBudgetWatchService::class);

    $partial = Partial::query()->create(['note_id' => $noteA->id, 'company_id' => $companyA->id, 'scope' => 'network', 'value' => 750.0]);
    $partial->Orders()->attach($a->Orders->first()->id);

    $alertA = $watch->registerWorkReport($a);
    $watch->registerWorkReport($b);
    $watch->registerPartial($partial);
    $alertA->forceFill(['created_at' => now()->subDays(10)])->save();

    $eng      = watchEngineer();
    $snapshot = app(\App\Services\Engineers\PepAlertsAnalytics::class)->snapshot($eng, []);
    $s        = $snapshot['summary'];

    expect($s['open'])->toBe(3)
        ->and($s['open_value'])->toBe(750.0)
        ->and($s['with_value'])->toBe(1)
        ->and($s['oldest'])->toBe(10)
        ->and($s['entered'])->toBe(3)
        ->and(collect($snapshot['by_region'])->pluck('total', 'label')->all())->toBe(['BASE-A' => 2, 'BASE-B' => 1])
        ->and($snapshot['age_buckets']['values'])->toBe([2, 0, 1, 0])
        ->and(collect($snapshot['by_type'])->pluck('total', 'label')->all())->toBe(['Parcial' => 1, 'Final' => 2]);

    $filtered = app(\App\Services\Engineers\PepAlertsAnalytics::class)->snapshot($eng, ['region' => 'BASE-B']);
    expect($filtered['summary']['open'])->toBe(1);

    $watch->handle($alertA, $eng, 'ok');
    $after = app(\App\Services\Engineers\PepAlertsAnalytics::class)->snapshot($eng, []);

    expect($after['summary']['open'])->toBe(2)
        ->and($after['summary']['handled'])->toBe(1)
        ->and($after['summary']['handled_eng'])->toBe(1)
        ->and($after['history'][0]['handler'])->toBe($eng->name);
});

it('o painel respeita a região do engenheiro', function () {
    $watch = app(PepBudgetWatchService::class);
    $watch->registerWorkReport(watchWorkReport(0.0, 'BASE-A')[0]);
    $watch->registerWorkReport(watchWorkReport(0.0, 'BASE-B')[0]);

    $snapshot = app(\App\Services\Engineers\PepAlertsAnalytics::class)->snapshot(watchEngineer(['BASE-B']), []);

    expect($snapshot['summary']['open'])->toBe(1)->and($snapshot['options']['regions'])->toBe(['BASE-B']);
});

it('os gráficos têm quantidade e ordem de datasets fixas, com ou sem dados', function () {
    $analytics = app(\App\Services\Engineers\PepAlertsAnalytics::class);
    $empty     = \App\Services\Engineers\PepAlertsCharts::all($analytics->snapshot(watchEngineer(), []));

    app(PepBudgetWatchService::class)->registerWorkReport(watchWorkReport(0.0)[0]);
    $full = \App\Services\Engineers\PepAlertsCharts::all($analytics->snapshot(watchEngineer(), []));

    foreach (['flow' => 2, 'reason' => 1, 'age' => 1] as $chart => $datasets) {
        expect($empty[$chart]['data']['datasets'])->toHaveCount($datasets)
            ->and($full[$chart]['data']['datasets'])->toHaveCount($datasets);
    }

    expect($full['reason']['options']['plugins']['mpCenter']['text'])->toBe('1')
        ->and($full['flow']['data']['datasets'][0]['label'])->toBe('Entraram na lista');
});

it('o Excel monta o resumo e uma aba por bloco com os mesmos números da tela', function () {
    app(PepBudgetWatchService::class)->registerWorkReport(watchWorkReport(0.0)[0]);

    $eng    = watchEngineer();
    $report = app(\App\Services\Engineers\PepAlertsAnalytics::class)->report($eng, []);
    $sheets = (new \App\Exports\Engineers\PepAlertsExport($report, $eng->name))->sheets();

    expect(collect($sheets)->map(fn ($s) => $s->title())->all())
        ->toBe(['Resumo', 'Pendentes', 'Tratados', 'Por região', 'Por empresa', 'Fluxo diário']);

    $pending = collect($sheets)->first(fn ($s) => $s->title() === 'Pendentes')->array();
    expect($pending)->toHaveCount(5); // título, descrição, vazio, cabeçalho e 1 linha
});

it('a fila de parciais mostra o saldo do PEP por linha, os indicadores e não vaza parciais de outra empresa na busca', function () {
    [$report, $note, $company] = watchWorkReport(100.0);
    $order                     = $report->Orders->first();

    $mine = Partial::query()->create(['note_id' => $note->id, 'company_id' => $company->id, 'value' => 400.0]);
    $mine->Orders()->attach($order->id);

    [$other, $noteB, $companyB] = watchWorkReport(5000.0);
    $theirs                     = Partial::query()->create(['note_id' => $noteB->id, 'company_id' => $companyB->id, 'value' => 10.0]);
    $theirs->Orders()->attach($other->Orders->first()->id);

    $eng = watchEngineer([], [$company->id]);

    Livewire::actingAs($eng)->test(\App\Http\Livewire\Engineers\PartialList::class)
        ->assertSee($note->note)
        ->assertDontSee($noteB->note)
        ->assertSee('ADS acima')
        ->assertSee('Aguardando aprovação')
        ->set('search', $noteB->note)
        ->call('pesquisar')
        ->assertSee('Nenhum informe parcial aguardando aprovação');

    $summary = app(\App\Services\Engineers\PartialApprovalQueue::class)->summary(
        app(\App\Services\Engineers\PartialApprovalQueue::class)->query($eng)
    );

    expect($summary['waiting'])->toBe(1)->and($summary['value'])->toBe(400.0)->and($summary['oldest'])->toBe(0);
});

it('os checkpoints mostram a posição da parcial em cada etapa, inclusive a rejeição', function (array $state, array $expected) {
    $partial             = new Partial($state);
    $partial->created_at = now();

    $steps = collect(\App\Services\Engineers\PartialProgress::steps($partial))->pluck('state', 'key')->all();

    expect($steps)->toBe($expected);
})->with([
    'aguardando aprovação'      => [[], ['activity' => 'done', 'approval' => 'current', 'supervision' => 'todo', 'payment' => 'todo', 'finalized' => 'todo']],
    'em fiscalização'           => [['allow' => true], ['activity' => 'done', 'approval' => 'done', 'supervision' => 'current', 'payment' => 'todo', 'finalized' => 'todo']],
    'em medição'                => [['allow' => true, 'supervision' => true], ['activity' => 'done', 'approval' => 'done', 'supervision' => 'done', 'payment' => 'current', 'finalized' => 'todo']],
    'paga, sem finalizar'       => [['allow' => true, 'supervision' => true, 'payment' => true], ['activity' => 'done', 'approval' => 'done', 'supervision' => 'done', 'payment' => 'done', 'finalized' => 'current']],
    'finalizada'                => [['allow' => true, 'supervision' => true, 'payment' => true, 'complete' => true], ['activity' => 'done', 'approval' => 'done', 'supervision' => 'done', 'payment' => 'done', 'finalized' => 'done']],
    'rejeitada na aprovação'    => [['deny' => true], ['activity' => 'done', 'approval' => 'rejected', 'supervision' => 'todo', 'payment' => 'todo', 'finalized' => 'todo']],
    'rejeitada na fiscalização' => [['allow' => true, 'supervision' => true, 'deny' => true], ['activity' => 'done', 'approval' => 'done', 'supervision' => 'rejected', 'payment' => 'todo', 'finalized' => 'todo']],
    'rejeitada na medição'      => [['allow' => true, 'supervision' => true, 'payment' => true, 'deny' => true], ['activity' => 'done', 'approval' => 'done', 'supervision' => 'done', 'payment' => 'rejected', 'finalized' => 'todo']],
]);

it('o histórico mostra os indicadores, a posição das parciais e respeita a empresa do engenheiro', function () {
    [$report, $note, $company] = watchWorkReport(100.0);
    $done                      = Partial::query()->create(['note_id' => $note->id, 'company_id' => $company->id, 'value' => 300.0, 'allow' => true, 'decision_at' => now()]);
    $deny                      = Partial::query()->create(['note_id' => $note->id, 'company_id' => $company->id, 'value' => 50.0, 'deny' => true, 'decision_at' => now()]);

    [, $noteB, $companyB] = watchWorkReport(100.0);
    Partial::query()->create(['note_id' => $noteB->id, 'company_id' => $companyB->id, 'value' => 999.0, 'allow' => true, 'decision_at' => now()]);

    $eng = watchEngineer([], [$company->id]);

    Livewire::actingAs($eng)->test(\App\Http\Livewire\Engineers\PartialHist::class)
        ->assertSee('Histórico de informes parciais')
        ->assertSee('Em fiscalização')
        ->assertSee('Rejeitada na aprovação')
        ->assertDontSee($noteB->note);

    $summary = app(\App\Services\Engineers\PartialHistoryService::class)->summary(
        app(\App\Services\Engineers\PartialHistoryService::class)->query([], $eng)
    );

    expect($summary['total'])->toBe(2)->and($summary['value'])->toBe(350.0)->and($summary['rejected'])->toBe(1)->and($summary['supervision'])->toBe(1);
});

it('o histórico vem do mais recente para o mais antigo pela data de envio', function () {
    [$report, $note, $company] = watchWorkReport(100.0);
    $old                       = Partial::query()->create(['note_id' => $note->id, 'company_id' => $company->id, 'value' => 1.0, 'allow' => true]);
    $new                       = Partial::query()->create(['note_id' => $note->id, 'company_id' => $company->id, 'value' => 2.0, 'allow' => true]);
    $middle                    = Partial::query()->create(['note_id' => $note->id, 'company_id' => $company->id, 'value' => 3.0, 'allow' => true]);

    $old->forceFill(['created_at' => now()->subDays(10)])->save();
    $middle->forceFill(['created_at' => now()->subDays(5)])->save();
    $new->forceFill(['created_at' => now()->subDay()])->save();

    $ids = app(\App\Services\Engineers\PartialHistoryService::class)->query([], watchEngineer([], [$company->id]))->pluck('id')->all();

    expect($ids)->toBe([$new->id, $middle->id, $old->id]);
});

it('a navegação da Engenharia localiza módulo, grupo e item pela rota, inclusive por apelidos e prefixos', function (string $route, string $module, ?string $item) {
    $where = \App\Support\Menu\EngineerNav::locate($route);

    expect($where['module'])->toBe($module)->and($where['item'])->toBe($item);
})->with([
    'viabilidade'             => ['engineers.viab_list', 'projetos', 'viab-em'],
    'alias da validação'      => ['engineers.validation', 'projetos', 'val-pilha'],
    'parcial (alias)'         => ['engineers.parciais', 'informes', 'par-aguardando'],
    'lançar informe (alias)'  => ['engineers.informes', 'informes', 'fin-lancar'],
    'calendário de ADS'       => ['engineers.ads.calendar', 'informes', 'ads-cal'],
    'D5 (alias)'              => ['engineers.d5', 'informes', 'd5-resolucao'],
    'PEP'                     => ['engineers.pep_alerts', 'informes', 'pep-sem-saldo'],
    'detalhe de cancelamento' => ['engineers.cancellations.show', 'cancelamentos', 'can-aprovacao'],
    'rota desconhecida'       => ['engineers.nao_existe', 'projetos', null],
]);

it('todo item do menu lateral aponta para uma rota existente e abre ativo na própria tela', function () {
    \App\Models\Bancoupdate::create(['last_update' => now(), 'error' => 0, 'inserts' => 0, 'updates' => 0]);
    $eng = watchEngineer();

    foreach (\App\Support\Menu\EngineerNav::modules() as $module) {
        foreach ($module['groups'] as $group) {
            foreach ($group['items'] as $item) {
                expect(\Illuminate\Support\Facades\Route::has($item['route']))->toBeTrue("rota {$item['route']} não existe");
            }
        }
    }

    $this->actingAs($eng)->get(route('engineers.pep_alerts'))
        ->assertOk()
        ->assertSee('Orçamento (PEP)')
        ->assertSee('Informes sem saldo')
        ->assertSee('Aguardando aprovação')
        ->assertSee('Painel de informes')
        ->assertSee('aria-current="page"', false);

    $this->actingAs($eng)->get(route('engineers.cancellations.history'))
        ->assertOk()
        ->assertSee('Notas em cancelamento')
        ->assertDontSee('Orçamento (PEP)');
});

it('o menu do topo usa a mesma definição: quatro itens, sem Notas D5 solta', function () {
    $labels = collect(\App\Support\Menu\EngineerNav::topItems())->pluck('label')->all();

    expect($labels)->toBe(['VIABILIDADE', 'VALIDAÇÃO DE PROJETOS', 'INFORMES', 'CANCELAMENTOS']);
});

function viabilityFixture(User $eng, array $attrs = []): Viability
{
    static $n = 0;
    $n++;
    $company = Company::query()->create(['name' => 'Viab Emp ' . $n, 'email' => "v{$n}@example.test"]);
    $note    = Note::query()->create(['note' => '90000' . $n, 'nstats' => 51, 'type_note' => 1]);

    return Viability::query()->create(array_merge([
        'note_id'   => $note->id, 'company_id' => $company->id, 'user_id' => $eng->id, 'engineer_id' => $eng->id, 'sended_at' => now()->subDays(2),
        'completed' => false, 'tacit' => false, 'rejected' => false, 'canceled' => false, 'visible_partner' => false, 'status' => 1, 'value' => 100.0,
    ], $attrs));
}

it('o painel de viabilidade separa fila de agora, realizadas, não realizadas (multa de 1%) e rejeições', function () {
    $eng = watchEngineer();
    $eng->forceFill(['superadm' => true])->save();

    viabilityFixture($eng, ['sended_at' => now()->subDays(10)]);                                  // aberta e fora do prazo
    viabilityFixture($eng);                                                                       // aberta no prazo
    viabilityFixture($eng, ['completed' => true, 'returned_at' => now()->subDay(), 'value' => 300.0]); // realizada
    $tacit   = viabilityFixture($eng, ['completed' => true, 'tacit' => true, 'completed_at' => now()->subDay(), 'value' => 1000.0]); // não realizada
    $granted = viabilityFixture($eng, ['completed' => true, 'tacit' => true, 'completed_at' => now()->subDay(), 'value' => 50.0]);
    \App\Models\TacitComment::query()->create(['viability_id' => $granted->id, 'user_id' => $eng->id, 'granted' => true, 'dismissed' => false]);

    $rejected = viabilityFixture($eng, ['rejected' => true]);
    \App\Models\Form::query()->create(['viability_id' => $rejected->id, 'user_id' => $eng->id, 'reason' => 'Projeto incompleto', 'rejected' => true]);

    $snapshot = app(\App\Services\Engineers\ViabilityDashboardAnalytics::class)->snapshot($eng, []);
    $s        = $snapshot['summary'];

    expect($s['open'])->toBe(2)
        ->and($s['open_late'])->toBe(1)
        ->and($s['oldest'])->toBe(10)
        ->and($s['realized'])->toBe(2)                 // não tácita + tácita com justificativa concedida
        ->and($s['realized_value'])->toBe(350.0)
        ->and($s['not_realized'])->toBe(1)
        ->and($s['penalty'])->toBe(10.0)               // 1% de 1000
        ->and($s['rejected'])->toBe(1)
        ->and($snapshot['reasons'][0]['label'])->toBe('Projeto incompleto')
        ->and($snapshot['age_buckets']['values'])->toBe([1, 0, 1, 0, 0]);
});

it('o painel de viabilidade respeita a visão do engenheiro e abre para ele', function () {
    $obStart = ob_get_level();
    \App\Models\Bancoupdate::create(['last_update' => now(), 'error' => 0, 'inserts' => 0, 'updates' => 0]);
    $mine  = watchEngineer();
    $other = watchEngineer();

    // A visão vem da hierarquia (user_closure): cada engenheiro enxerga a si mesmo.
    foreach ([$mine, $other] as $user) {
        \Illuminate\Support\Facades\DB::table('user_closure')->insert(['ancestor_id' => $user->id, 'descendant_id' => $user->id, 'depth' => 0]);
    }

    viabilityFixture($mine);
    viabilityFixture($other);

    $snapshot = app(\App\Services\Engineers\ViabilityDashboardAnalytics::class)->snapshot($mine, []);
    expect($snapshot['summary']['open'])->toBe(1);

    $this->actingAs($mine)->get(route('engineers.main'))->assertOk()->assertSee('Painel de viabilidade')->assertSee('Produtividade por empresa');
    // A renderização completa da página (layout + Livewire) deixa um buffer de saída aberto no teste; fecha para não marcar como arriscado.
    while (ob_get_level() > $obStart) {
        ob_end_clean();
    }
});

it('os gráficos do painel de viabilidade mantêm a quantidade de datasets', function () {
    $eng    = watchEngineer();
    $charts = \App\Services\Engineers\ViabilityDashboardCharts::all(app(\App\Services\Engineers\ViabilityDashboardAnalytics::class)->snapshot($eng, []));

    expect($charts['flow']['data']['datasets'])->toHaveCount(2)
        ->and($charts['outcome']['data']['datasets'])->toHaveCount(1)
        ->and($charts['age']['data']['datasets'])->toHaveCount(1);
});

it('o Excel do painel de viabilidade monta o resumo e as abas', function () {
    $eng = watchEngineer();
    $eng->forceFill(['superadm' => true])->save();
    viabilityFixture($eng);

    $report = app(\App\Services\Engineers\ViabilityDashboardAnalytics::class)->report($eng, []);
    $sheets = (new \App\Exports\Engineers\ViabilityDashboardExport($report, $eng->name))->sheets();

    expect(collect($sheets)->map(fn ($s) => $s->title())->all())->toBe(['Resumo', 'Em viabilidade', 'Por empresa', 'Motivos de rejeição', 'Fluxo diário']);
});

it('todo menu lateral do padrão (*Nav) tem estrutura válida: chaves únicas, rotas existentes e ícones Remix', function () {
    $classes = collect(glob(app_path('Support/Menu/*Nav.php')))
        ->map(fn (string $file) => 'App\\Support\\Menu\\' . basename($file, '.php'))
        ->reject(fn (string $class) => $class === \App\Support\Menu\SidebarNav::class);

    expect($classes)->not->toBeEmpty();

    $remix = file_get_contents(public_path('assets/vendor/remixicon/remixicon.css'));

    foreach ($classes as $class) {
        expect(is_subclass_of($class, \App\Support\Menu\SidebarNav::class))->toBeTrue("{$class} deve estender SidebarNav");

        foreach ($class::sampleContexts() as $context) {
            $keys    = [];
            $modules = $class::modules($context);

            expect($modules)->not->toBeEmpty("{$class} sem módulos para " . json_encode($context['variant'] ?? 'padrão'));

            foreach ($modules as $module) {
                expect($module)->toHaveKeys(['label', 'icon', 'hint', 'groups']);
                expect($module['icon'])->toStartWith('ri-');

                foreach ($module['groups'] as $group) {
                    expect($group)->toHaveKeys(['key', 'label', 'icon', 'items']);
                    expect($group['icon'])->toStartWith('ri-');

                    foreach ($group['items'] as $item) {
                        expect(\Illuminate\Support\Facades\Route::has($item['route']))->toBeTrue("{$class}: rota {$item['route']} não existe");
                        expect($item['icon'])->toStartWith('ri-');
                        expect(str_contains($remix, '.' . $item['icon'] . ':'))->toBeTrue("{$class}: ícone {$item['icon']} não existe no Remix do projeto");
                        expect(in_array($group['key'] . '.' . $item['key'], $keys, true))->toBeFalse("{$class}: chave de item repetida {$item['key']}");
                        $keys[] = $group['key'] . '.' . $item['key'];
                    }
                }
            }
        }
    }
});

it('o menu do Responsável segue a mesma organização da Engenharia e localiza as rotas antigas por apelido', function (string $route, string $module, ?string $item) {
    $where = \App\Support\Menu\ResponsibleNav::locate($route);

    expect($where['module'])->toBe($module)->and($where['item'])->toBe($item);
})->with([
    'viabilidade'       => ['responsible.viab_list', 'projetos', 'viab-em'],
    'painel (alias)'    => ['responsible.main', 'projetos', 'viab-em'],
    'validação (alias)' => ['responsible.validation', 'projetos', 'val-pilha'],
    'informe de obra'   => ['responsible.inform_obra', 'informes', 'fin-lancar'],
    'parciais (alias)'  => ['responsible.parciais', 'informes', 'par-hist'],
    'D5 (alias)'        => ['responsible.d5', 'informes', 'd5-resolucao'],
    'ADS'               => ['responsible.ads.requests', 'informes', 'ads-solic'],
]);

it('os menus de atividade (despacho e serviço) levam a atividade nos parâmetros e filtram pelos gates do Encerramento', function () {
    $service = (object) ['uuid' => 'abc-123', 'service' => 'Levantamento', 'folder' => 'levantamento'];

    $dispatch = \App\Support\Menu\DispatchNav::modules(['service' => $service, 'variant' => 'levantamento']);
    $items    = collect($dispatch['atividade']['groups'])->flatMap(fn ($g) => $g['items']);

    expect($dispatch['atividade']['label'])->toBe('Levantamento')
        ->and($items->pluck('route')->all())->toBe(['dispatch.dashboard', 'dispatch.main', 'dispatch.stack', 'dispatch.transprod', 'dispatch.d5'])
        ->and($items->every(fn ($i) => $i['params'] === ['service' => 'abc-123']))->toBeTrue();

    $serviceMenu = \App\Support\Menu\ServiceNav::locate('services.historic', ['service' => $service, 'variant' => 'levantamento']);
    expect($serviceMenu['item'])->toBe('historico');

    // Encerramento: sem os gates, os grupos somem (e o módulo some junto); com eles, volta.
    $noGate = (object) ['uuid' => 'x', 'service' => 'Encerramento', 'folder' => 'encerramento'];
    $this->actingAs(watchEngineer());

    \Illuminate\Support\Facades\Gate::define('closure.dispatch', fn () => false);
    \Illuminate\Support\Facades\Gate::define('closure.manager', fn () => false);

    expect(\App\Support\Menu\DispatchNav::resolve(['service' => $noGate, 'variant' => 'encerramento']))->toBe([]);

    \Illuminate\Support\Facades\Gate::define('closure.dispatch', fn () => true);

    $groups = \App\Support\Menu\DispatchNav::resolve(['service' => $noGate, 'variant' => 'encerramento'])['atividade']['groups'];
    expect(collect($groups)->pluck('key')->all())->toBe(['acompanhamento', 'gestao'])
        ->and(collect($groups[1]['items'])->pluck('key')->all())->toBe(['depositos']);
});

it('o componente do menu renderiza a atividade, os parâmetros da rota e o painel extra do rodapé', function () {
    $this->actingAs(watchEngineer());
    $service = (object) ['uuid' => 'abc-123', 'service' => 'Levantamento', 'folder' => 'levantamento'];

    $html = \Illuminate\Support\Facades\Blade::render(
        '<x-nav.sidebar :nav="\\App\\Support\\Menu\\DispatchNav::class" :route="\'dispatch.stack\'" label="Menu de despacho" :context="[\'service\' => $service, \'variant\' => \'levantamento\']"><span>PAINEL-EXTRA</span></x-nav.sidebar>',
        ['service' => $service]
    );

    expect($html)->toContain('Controle de Levantamento')
        ->and($html)->toContain('/abc-123')
        ->and($html)->toContain('snav-item is-active')
        ->and($html)->toContain('PAINEL-EXTRA');
});

it('o grupo do menu lateral tem cabeçalho próprio (details/summary) e o item ativo marca a página', function () {
    \App\Models\Bancoupdate::create(['last_update' => now(), 'error' => 0, 'inserts' => 0, 'updates' => 0]);

    $this->actingAs(watchEngineer())->get(route('engineers.pep_alerts'))
        ->assertOk()
        ->assertSee('snav-group-head', false)
        ->assertSee('snav-item is-active', false);
});

it('todos os arquivos de menu de atividade (despacho e serviço) renderizam com o menu padrão, sem HTML de sidebar antigo', function () {
    $user = watchEngineer();
    $user->forceFill(['superadm' => true])->save();
    $this->actingAs($user->fresh());

    // Serviço de verdade: os contadores do menu (Livewire) carregam a atividade pelo uuid.
    $service = \App\Models\Service::create(['service' => 'Pagamento', 'folder' => 'pagamento'])->fresh();
    $files   = array_merge(
        glob(resource_path('views/dispatchs/*/menu.blade.php')),
        glob(resource_path('views/services/*/menu.blade.php')),
        glob(resource_path('views/protest/*/menu.blade.php')),
        glob(resource_path('views/admin/*/menu.blade.php')),
        glob(resource_path('views/config/*menu.blade.php')),
        [resource_path('views/config/wall/menu.blade.php'), resource_path('views/admin/menu.blade.php')],
        [resource_path('views/dispatchs/menu.blade.php'), resource_path('views/services/menu.blade.php')],
    );

    expect(count($files))->toBeGreaterThan(20);

    foreach ($files as $file) {
        $view = str_replace(['/', '.blade.php'], ['.', ''], substr($file, strlen(resource_path('views')) + 1));

        expect(file_get_contents($file))->not->toContain('sidebar-nav')->not->toContain('nav-link');

        $html = view($view, ['service' => $service])->render();

        expect(str_contains($html, 'snav-aside'))->toBeTrue("{$view} não usa o menu padrão");
    }
});

it('a ocupação do menu mostra a fila por pessoa, marca quem está online e o líder, sem estilo inline', function () {
    $rows = collect([
        (object) ['user_id' => 'u1', 'id' => 0, 'name' => 'Izadora Maria Tintore', 'registros' => 24, 'notes' => 6, 'ov' => 18, 'deleted_at' => null],
        (object) ['id' => 'u2', 'name' => 'Giovana Loyola', 'registros' => 12, 'notes' => 6, 'ov' => 6, 'deleted_at' => null],
        (object) ['id' => 'u3', 'name' => 'Sonia Baldotto', 'registros' => 6, 'notes' => 3, 'ov' => 3, 'deleted_at' => now()],
    ]);

    $html = \Illuminate\Support\Facades\Blade::render(
        '<x-nav.occupation :rows="$rows" :online="[\'u1\']" :avatars="[\'u1\' => \'https://exemplo.test/foto.png\']" />',
        ['rows' => $rows]
    );

    expect($html)->toContain('snav-occ-head')
        ->and($html)->toContain('>42<')                 // total em aberto
        ->and($html)->toContain('Izadora Tintore')
        ->and($html)->toContain('snav-occ-w-100')       // líder = 100% da barra
        ->and($html)->toContain('snav-occ-w-50')
        ->and($html)->toContain('1 online')
        ->and($html)->toContain('is-top')
        ->and($html)->toContain('inativo')
        ->and($html)->toContain('snav-occ-img')
        ->and($html)->toContain('https://exemplo.test/foto.png')
        ->and(substr_count($html, 'snav-occ-img'))->toBe(1)   // só quem tem foto; os demais ficam com as iniciais
        ->and($html)->not->toContain('style=');
});

it('o painel de ocupação usa o avatar do usuário e, sem foto, o mesmo avatar padrão (API) do topo do sistema', function () {
    $company = Company::query()->create(['name' => 'Emp Ocup', 'email' => 'ocup@example.test']);
    $service = \App\Models\Service::create(['service' => 'Pagamento', 'folder' => 'pagamento'])->fresh();
    $note    = Note::query()->create(['note' => '95000001', 'nstats' => 51, 'type_note' => 1]);

    $withPhoto = User::factory()->create(['company_id' => $company->id, 'avatar' => 'https://exemplo.test/foto.png', 'email' => 'com-foto@example.test']);
    $noPhoto   = User::factory()->create(['company_id' => $company->id, 'avatar' => null, 'email' => 'sem-foto@example.test']);

    foreach ([$withPhoto, $noPhoto] as $u) {
        \App\Models\Production::create(['note_id' => $note->id, 'service_id' => $service->uuid, 'user_id' => $u->id, 'company_id' => $company->id, 'completed' => false, 'status' => 1]);
    }

    $viewer = watchEngineer();
    $viewer->forceFill(['superadm' => true, 'contract' => false])->save();

    Livewire::actingAs($viewer->fresh())
        ->test(\App\Http\Livewire\Production\Users\Occupation::class, ['service_id' => $service->uuid])
        ->assertSee('https://exemplo.test/foto.png', false)
        ->assertSee('https://api.dicebear.com/9.x/pixel-art/svg?seed=' . urlencode('sem-foto@example.test'), false)
        // O clique na pessoa filtra pelo id real (uuid); antes chegava "0" por causa do cast do model Production.
        ->assertSee("filterUser', '{$withPhoto->id}'", false);
});

it('a Administração agrupa as telas por processo e o topo é gerado da mesma definição', function (string $route, string $module, ?string $item) {
    $super = watchEngineer();
    $super->forceFill(['superadm' => true])->save();
    $this->actingAs($super->fresh());

    $where = \App\Support\Menu\AdminNav::locate($route);

    expect($where['module'])->toBe($module)->and($where['item'])->toBe($item);
})->with([
    'usuários'        => ['admin.user.list', 'cadastros', 'usuarios'],
    'contratos'       => ['admin.company.contracts_list', 'cadastros', 'contratos'],
    'controle de D5'  => ['admin.control.d5', 'dados', 'd5'],
    'auditoria'       => ['admin.audits.notes', 'dados', 'auditoria-notas'],
    'wall (prefixo)'  => ['config.wall.settings', 'configuracoes', 'wall'],
    'config (início)' => ['config.main', 'configuracoes', 'servicos'],
    'monitor ADS'     => ['admin.control.ads_requests', 'configuracoes', 'ads-monitor'],
    'jobs'            => ['config.system.jobs_view', 'sistema', 'jobs'],
    'tokens de API'   => ['config.system.api_tokens', 'configuracoes', 'tokens'],
]);

it('a Administração respeita as permissões: admin comum não vê o que é só de superadm, e o topo segue o mesmo recorte', function () {
    $admin = watchEngineer();
    $admin->forceFill(['admin' => true, 'superadm' => false, 'management' => false])->save();
    $this->actingAs($admin->fresh());

    $keys = collect(\App\Support\Menu\AdminNav::resolve())->flatMap(fn ($m) => collect($m['groups'])->flatMap(fn ($g) => collect($g['items'])->pluck('key')))->all();

    expect($keys)->toContain('usuarios', 'auditoria-notas', 'status')
        ->and($keys)->not->toContain('empresas', 'd5', 'scheduler', 'wall', 'wall-v2');

    $labels = collect(\App\Support\Menu\AdminNav::topNodes())->flatMap(fn ($m) => collect($m['nodes'])->flatMap(fn ($g) => collect($g['nodes'])->pluck('label')))->all();
    expect($labels)->toContain('USUÁRIOS')->and($labels)->not->toContain('SCHEDULER');

    $admin->forceFill(['superadm' => true])->save();
    $this->actingAs($admin->fresh());

    $all = collect(\App\Support\Menu\AdminNav::resolve())->flatMap(fn ($m) => collect($m['groups'])->flatMap(fn ($g) => collect($g['items'])->pluck('key')))->all();
    expect($all)->toContain('empresas', 'd5', 'scheduler', 'wall');
});
