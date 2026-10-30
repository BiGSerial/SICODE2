<?php

use App\Exports\Engineers\InformDashboardExport;
use App\Http\Livewire\Engineers\InformDashboard;
use App\Jobs\Engineers\InformDashboardExportJob;
use App\Models\{Adsform, Company, Note, ReturnWork, Service, User, WorkReport};
use App\Services\Engineers\{InformDashboardAnalytics, InformDashboardCharts};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, DB, Notification, Storage};
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;

// "Painel de informes" (Engenharia → Informes): recebimento, devoluções, entrega da ADS e situação atual dos informes de conclusão.

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-10-25 12:00:00');
    Cache::flush();
    Notification::fake();
    Storage::fake('local');
});

afterEach(fn () => Carbon::setTestNow());

/** Informe de conclusão (e, se pedido, a ADS e as devoluções). `ads` = data de entrega da ADS; `returns` = motivos de devolução. */
function informRow(Company $company, User $user, string $informedAt, array $attrs = [], ?string $ads = null, array $returns = [], ?Service $service = null): WorkReport
{
    static $n = 0;
    $n++;
    $note   = Note::create(['note' => 'INF-' . $n, 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
    $report = WorkReport::create($attrs + [
        'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
        'date'    => substr($informedAt, 0, 10), 'informed_at' => $informedAt, 'rejected' => false, 'canceled' => false,
    ]);

    if ($ads) {
        $form = Adsform::create(['work_report_id' => $report->id, 'note_id' => $note->id, 'user_id' => $user->id, 'amount' => 0, 'partial' => false]);
        DB::table('adsforms')->where('id', $form->id)->update(['created_at' => $ads, 'updated_at' => $ads]);
    }

    foreach ($returns as $reason) {
        ReturnWork::create(['work_report_id' => $report->id, 'service_id' => $service->uuid, 'user_id' => $user->id, 'category' => $reason]);
    }

    return $report;
}

function informFixture(): array
{
    $alpha   = Company::query()->create(['name' => 'Alfa Obras', 'email' => 'alfa@example.com']);
    $beta    = Company::query()->create(['name' => 'Beta Redes', 'email' => 'beta@example.com']);
    $user    = User::factory()->create(['company_id' => $alpha->id, 'name' => 'Engenheiro', 'superadm' => true, 'management' => true, 'contract' => false]);
    $service = Service::query()->create(['service' => 'Informe', 'folder' => 'informe', 'construction' => true]);

    return compact('alpha', 'beta', 'user', 'service') + [
        // Alfa: no prazo, em atraso, vencida sem entrega e um informe devolvido que ainda não foi reenviado
        'onTime'  => informRow($alpha, $user, '2026-10-05 10:00:00', ads: '2026-10-07 09:00:00'),
        'late'    => informRow($alpha, $user, '2026-10-05 11:00:00', ads: '2026-10-15 09:00:00'),
        'overdue' => informRow($alpha, $user, '2026-10-06 10:00:00'),
        'waiting' => informRow($alpha, $user, '2026-10-10 10:00:00', ['rejected' => true], returns: ['TRAFO NAO ATENDIDO'], service: $service),
        // Beta: reinformado depois de duas devoluções, ADS no prazo
        'retried' => informRow($beta, $user, '2026-10-12 10:00:00', ['retry' => true], ads: '2026-10-13 09:00:00', returns: ['TRAFO NAO ATENDIDO', 'FALTA DE EVIDENCIAS'], service: $service),
        // Fora do recorte: mês anterior e cancelado
        'before'   => informRow($beta, $user, '2026-09-20 10:00:00', ads: '2026-09-21 09:00:00'),
        'canceled' => informRow($beta, $user, '2026-10-08 10:00:00', ['canceled' => true]),
    ];
}

function informAnalytics(array $filters = []): array
{
    $a = app(InformDashboardAnalytics::class);
    $f = $a->normalize($filters + ['from' => '2026-10-01', 'to' => '2026-10-25']);

    return [$a, $f, $a->assemble($f)];
}

it('conta só os informes do período, sem os cancelados, e classifica a ADS de cada um', function () {
    informFixture();
    [, , $snap] = informAnalytics();
    $s          = $snap['summary'];

    expect($s['total'])->toBe(5)
        ->and($s['buckets'])->toMatchArray(['on_time' => 2, 'late' => 1, 'overdue' => 1, 'returned' => 1])
        ->and($s['due_base'])->toBe(4)
        ->and($s['pct_on_time'])->toBe(50.0)
        ->and($s['returned'])->toBe(2)
        ->and($s['waiting_new'])->toBe(1)
        ->and($s['retries'])->toBe(1)
        ->and($s['pct_returned'])->toBe(40.0);
});

it('lista do informe mais novo para o mais antigo', function () {
    $fx             = informFixture();
    [$a, $f, $snap] = informAnalytics();

    $ids = $a->listRows($snap, $f)->pluck('id')->all();

    expect($ids)->toBe([$fx['retried']->id, $fx['waiting']->id, $fx['overdue']->id, $fx['late']->id, $fx['onTime']->id]);
});

it('calcula atraso, multa prevista e tempo de entrega', function () {
    $fx         = informFixture();
    [, , $snap] = informAnalytics();
    $rows       = collect($snap['rows'])->keyBy('id');

    expect($rows[$fx['late']->id]['delay_days'])->toBeGreaterThan(0)
        ->and($rows[$fx['late']->id]['penalty'])->toBeGreaterThan(0)
        ->and($rows[$fx['overdue']->id]['delay_days'])->toBeGreaterThan($rows[$fx['late']->id]['delay_days'])
        ->and($rows[$fx['onTime']->id]['delay_days'])->toBe(0)
        ->and($rows[$fx['onTime']->id]['delivery_days'])->toBeBetween(1.9, 2.1)
        ->and($snap['summary']['avg_late'])->toBeGreaterThan(0);
});

it('agrupa os motivos de devolução e as empreiteiras', function () {
    informFixture();
    [, , $snap] = informAnalytics();

    expect(collect($snap['reasons'])->pluck('total', 'reason')->all())->toBe(['TRAFO NAO ATENDIDO' => 2, 'FALTA DE EVIDENCIAS' => 1]);

    $alpha = collect($snap['companies'])->firstWhere('name', 'Alfa Obras');
    expect($alpha)->toMatchArray(['total' => 4, 'on_time' => 1, 'late' => 1, 'overdue' => 1, 'returned' => 1]);
});

it('filtra por empreiteira, devolução, reinforme e situação, e a situação da ADS só mexe na lista', function () {
    $fx = informFixture();

    [, , $snap] = informAnalytics(['company' => $fx['beta']->id]);
    expect($snap['summary']['total'])->toBe(1);

    [, , $snap] = informAnalytics(['returned' => 'aguardando']);
    expect($snap['summary']['total'])->toBe(1)->and($snap['rows'][0]['id'])->toBe($fx['waiting']->id);

    [, , $snap] = informAnalytics(['returned' => 'nao']);
    expect($snap['summary']['total'])->toBe(3);

    [, , $snap] = informAnalytics(['retry' => 'sim']);
    expect($snap['summary']['total'])->toBe(1)->and($snap['rows'][0]['retry'])->toBeTrue();

    [$a, $f, $snap] = informAnalytics(['ads' => 'overdue']);
    expect($snap['summary']['total'])->toBe(5)
        ->and($a->listRows($snap, $f)->pluck('id')->all())->toBe([$fx['overdue']->id]);
});

it('busca por nota e por empreiteira', function () {
    $fx = informFixture();

    [, , $snap] = informAnalytics(['search' => $fx['overdue']->Note->note]);
    expect($snap['summary']['total'])->toBe(1);

    [, , $snap] = informAnalytics(['search' => 'Beta']);
    expect($snap['summary']['total'])->toBe(1);
});

it('respeita o recorte de empresas do usuário de contrato', function () {
    $fx = informFixture();

    [, , $snap] = informAnalytics(['scope' => [$fx['beta']->id]]);
    expect($snap['summary']['total'])->toBe(1);

    [, , $snap] = informAnalytics(['scope' => []]);
    expect($snap['summary']['total'])->toBe(0);
});

it('limita o período e troca a série diária pela mensal em períodos longos', function () {
    informFixture();
    $a = app(InformDashboardAnalytics::class);

    $f = $a->normalize(['from' => '2024-01-01', 'to' => '2026-10-25']);
    expect($f['truncated'])->toBeTrue()->and($f['days'])->toBe(InformDashboardAnalytics::MAX_DAYS);

    $snap = $a->assemble($a->normalize(['from' => '2026-05-01', 'to' => '2026-10-25']));
    expect($snap['series']['unit'])->toBe('month')->and($snap['series']['labels'])->toHaveCount(6);

    $snap = $a->assemble($a->normalize(['from' => '2026-10-01', 'to' => '2026-10-25']));
    expect($snap['series']['unit'])->toBe('day')->and(array_sum($snap['series']['received']))->toBe(5);
});

it('mantém fixos a quantidade e a ordem dos datasets de cada gráfico', function () {
    informFixture();
    [, , $full]  = informAnalytics();
    [, , $empty] = informAnalytics(['search' => 'inexistente-xyz']);

    foreach ([$full, $empty] as $snap) {
        $charts = InformDashboardCharts::all($snap);

        expect($charts['evolution']['data']['datasets'])->toHaveCount(3)
            ->and(array_column($charts['evolution']['data']['datasets'], 'label'))->toBe(['Informes recebidos', 'ADS em atraso ou vencida', 'Devolvidos'])
            ->and($charts['ads']['data']['datasets'])->toHaveCount(1)
            ->and($charts['ads']['data']['datasets'][0]['data'])->toHaveCount(count(InformDashboardAnalytics::BUCKETS))
            ->and($charts['companies']['data']['datasets'])->toHaveCount(4)
            ->and($charts['reasons']['data']['datasets'])->toHaveCount(1);
    }
});

it('renderiza a tela e atualiza o painel ao filtrar', function () {
    $fx = informFixture();

    Livewire::actingAs($fx['user'])
        ->withQueryParams(['periodo' => 'custom', 'de' => '2026-10-01', 'ate' => '2026-10-25'])
        ->test(InformDashboard::class)
        ->assertSee('Painel de informes')
        ->assertSee('Alfa Obras')
        ->assertSee($fx['overdue']->Note->note)
        ->assertDontSee($fx['before']->Note->note)
        ->call('setAds', 'overdue')
        ->assertSee($fx['overdue']->Note->note)
        ->assertDontSee($fx['onTime']->Note->note)
        ->set('company', $fx['beta']->id)
        ->assertDontSee($fx['overdue']->Note->note)
        ->call('cleanAll')
        ->assertSet('company', null)
        ->assertSet('ads', '');
});

it('mostra o estado vazio quando não há informes no período', function () {
    $fx = informFixture();

    Livewire::actingAs($fx['user'])
        ->withQueryParams(['periodo' => 'custom', 'de' => '2025-01-01', 'ate' => '2025-01-31'])
        ->test(InformDashboard::class)
        ->assertSee('Nenhum informe nos filtros atuais');
});

it('gera o Excel pelo job, com as abas do painel e o controle da exportação', function () {
    $fx = informFixture();

    (new InformDashboardExportJob(['from' => '2026-10-01', 'to' => '2026-10-25'], (string) $fx['user']->id))
        ->handle(app(InformDashboardAnalytics::class), app(\App\Services\Files\ExportStorage::class));

    $file = collect(Storage::disk('local')->files('exports'))->first(fn ($f) => str_contains($f, 'painel_informes'));
    expect($file)->not->toBeNull();

    $book = IOFactory::load(Storage::disk('local')->path($file));
    expect($book->getSheetNames())->toBe(['Resumo', 'Empreiteiras', 'Devoluções', 'Situação atual', 'Evolução', 'Registros', 'Regras', 'Controle Exportacao']);

    $rows = $book->getSheetByName('Registros')->toArray();
    expect($rows)->toHaveCount(4 + 5)->and($rows[4][1])->toBe($fx['retried']->Note->note);

    $control = collect($book->getSheetByName('Controle Exportacao')->toArray())->pluck(1, 0);
    expect($control['Usuário'])->toBe('Engenheiro')->and($control['Tipo'])->toBe('Painel de informes');

    Notification::assertSentTo($fx['user'], \App\Notifications\SystemNotification::class);
});

it('o Excel traz a mesma lista da tela, filtrada pela situação da ADS', function () {
    $fx = informFixture();
    $a  = app(InformDashboardAnalytics::class);

    $report            = $a->report(['from' => '2026-10-01', 'to' => '2026-10-25', 'ads' => 'late']);
    $report['records'] = $a->listRows($report, $report['filters'])->all();

    expect(collect($report['records'])->pluck('id')->all())->toBe([$fx['late']->id])
        ->and((new InformDashboardExport($report, 'Teste', ['Usuário' => 'Teste']))->sheets())->toHaveCount(8);
});
