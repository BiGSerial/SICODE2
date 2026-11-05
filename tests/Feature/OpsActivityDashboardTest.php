<?php

use App\Http\Livewire\Dispatchs\Dashboard;
use App\Models\{Company, Note, Production, Service, User};
use App\Services\Dispatch\Dashboard\{ActivityDashboardAnalytics, ActivityDashboardCharts};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Dashboard de atividade: docs/dashboards/DASHBOARD_ATIVIDADE.md

beforeEach(function () {
    Cache::flush();

    $this->user    = User::factory()->create(['contract' => false]);
    $this->service = Service::create(['service' => 'Analise', 'folder' => 'analises', 'status' => 2]);
    $this->actingAs($this->user);
});

function dashNote(string $number, array $extra = []): Note
{
    return Note::create($extra + ['note' => $number, 'dt_status' => now()->subDays(3), 'dt_created' => now()->subDays(9), 'nstats' => 2, 'type_note' => 2, 'rubrica' => 'Mercado', 'days_left' => 20]);
}

it('normaliza filtros inválidos para o padrão', function () {
    $f = app(ActivityDashboardAnalytics::class)->normalize(['type' => 'x', 'from' => '2026-05-10', 'to' => '2026-05-01', 'limit' => '7']);

    expect($f['type'])->toBe('2')
        ->and($f['limit'])->toBe(8)
        ->and($f['to']->toDateString())->toBe('2026-05-10')
        ->and($f['tracking'])->toBeFalse();
});

it('o contrato dos gráficos tem datasets fixos e rótulos coerentes', function () {
    $snapshot = app(ActivityDashboardAnalytics::class)->snapshot($this->service, ['type' => '2']);
    $charts   = ActivityDashboardCharts::all($snapshot);

    expect(array_keys($charts))->toBe(['status', 'deadline', 'users', 'band_satel', 'band_own'])
        ->and($charts['status']['data']['datasets'])->toHaveCount(2)
        ->and($charts['deadline']['data']['datasets'])->toHaveCount(count(ActivityDashboardAnalytics::DEADLINE_SERIES))
        ->and($charts['users']['data']['datasets'])->toHaveCount(3)
        ->and($charts['band_satel']['data']['datasets'])->toHaveCount(4)
        ->and($charts['users']['options']['indexAxis'])->toBe('y');

    foreach ($charts['deadline']['data']['datasets'] as $set) {
        expect($set['data'])->toHaveCount(count($charts['deadline']['data']['labels']));
    }
});

it('a tela renderiza, filtra, aplica período e limpa', function () {
    Livewire::test(Dashboard::class, ['service' => $this->service->uuid])
        ->assertStatus(200)
        ->assertSee('Dashboard de analise')
        ->assertSee('Total na pilha')
        ->assertSee('data-my-panel-chart="status"', false)
        ->assertDontSee('<style', false)
        ->set('noteType', '1')
        ->assertSet('noteType', '1')
        ->call('setPreset', 'prev')
        ->set('adherenceDays', '6')
        ->assertSet('adherenceDays', '6')
        ->set('noteType', 'invalido')
        ->assertSet('noteType', '2')
        ->call('clearFilters')
        ->assertSet('noteType', '2')
        ->assertSet('adherenceDays', '8')
        ->assertDispatchedBrowserEvent('my-panel:charts');
});

it('conta a pilha, a atribuição e o prazo vencido a partir da mesma consulta', function () {
    $company = Company::create(['name' => 'Empresa de teste', 'email' => 'teste@example.com']);
    $free    = dashNote('4000070001', ['days_left' => -2, 'dt_status' => now()->subDays(40)]);
    $taken   = dashNote('4000070002', ['days_left' => 5]);

    dashNote('4000070003', ['days_left' => null, 'dt_status' => null]);
    Production::create(['note_id' => $taken->id, 'service_id' => $this->service->uuid, 'company_id' => $company->id, 'user_id' => $this->user->id, 'status' => 2, 'dt_note' => now(), 'status_note' => 2, 'dispatch_at' => now()->subDays(6), 'att_at' => now()->subDays(6), 'confirmed' => false]);

    $s = app(ActivityDashboardAnalytics::class)->snapshot($this->service, ['type' => '2']);

    expect($s['summary']['total'])->toBe(3)
        ->and($s['summary']['assigned'])->toBe(1)
        ->and($s['summary']['unassigned'])->toBe(2)
        ->and($s['summary']['overdue'])->toBe(1)
        ->and($s['summary']['oldest_status_days'])->toBe(40)
        ->and(array_sum($s['status_age']['assigned']) + array_sum($s['status_age']['unassigned']))->toBe(3)
        ->and($s['users']['labels'])->toBe([$this->user->name])
        ->and($s['users']['buckets']['5 a 8 dias'])->toBe([1])
        ->and($s['critical'][0]['note'])->toBe('4000070001')
        ->and($s['critical'][0]['deadline_tone'])->toBe('red')
        ->and(collect($s['critical'])->last()['days_left'])->toBeNull()
        ->and(collect($s['critical'])->firstWhere('note', '4000070002')['assigned_user'])->toBe($this->user->name);
});

it('a rubrica Acompanhamento só entra quando pedida', function () {
    dashNote('4000070010', ['rubrica' => 'Acompanhamento']);
    dashNote('4000070011');

    $a = app(ActivityDashboardAnalytics::class);

    expect($a->snapshot($this->service, ['type' => '2'])['summary']['total'])->toBe(1)
        ->and($a->snapshot($this->service, ['type' => '2', 'tracking' => true])['summary']['total'])->toBe(2);
});

it('o recorte por grupo e rubrica vale para a pilha e vem da tela, e limpa junto dos filtros', function () {
    dashNote('4000070020', ['group1' => 'AAA']);
    dashNote('4000070021', ['group1' => 'BBB']);
    dashNote('4000070022', ['group1' => 'BBB', 'rubrica' => 'Outra']);

    $a = app(ActivityDashboardAnalytics::class);

    expect($a->snapshot($this->service, ['type' => '2'])['summary']['total'])->toBe(3)
        ->and($a->snapshot($this->service, ['type' => '2', 'scope' => ['group1' => ['BBB']]])['summary']['total'])->toBe(2)
        ->and($a->snapshot($this->service, ['type' => '2', 'scope' => ['group1' => ['BBB'], 'rubrica' => ['Mercado']]])['summary']['total'])->toBe(1)
        ->and($a->snapshot($this->service, ['type' => '2', 'scope' => ['inexistente' => ['x']]])['summary']['total'])->toBe(3);

    Livewire::test(Dashboard::class, ['service' => $this->service->uuid])
        ->call('applyListFilters', ['group1' => ['BBB'], 'foo' => ['x']])
        ->assertSet('filters', ['group1' => ['BBB']])
        ->assertDispatchedBrowserEvent('my-panel:charts')
        ->call('clearFilters')
        ->assertSet('filters', [])
        ->assertDispatchedBrowserEvent('ops-filters');
});

it('o relatório do Excel traz a pilha crítica completa, além das 15 da tela', function () {
    foreach (range(1, 20) as $i) {
        dashNote('40000800' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), ['days_left' => 20 - $i]);
    }

    $a = app(ActivityDashboardAnalytics::class);

    expect($a->snapshot($this->service, ['type' => '2'])['critical'])->toHaveCount(15)
        ->and($a->report($this->service, ['type' => '2'])['critical_all'])->toHaveCount(20);
});

it('a view do dashboard não carrega CSS, JS nem cor literal e não usa o gráfico antigo', function () {
    $view = file_get_contents(resource_path('views/livewire/dispatchs/dashboard.blade.php'));

    expect($view)->not->toContain('<style')->not->toContain('x-grafico.apex')->not->toMatch('/text-bg-|#[0-9a-fA-F]{3,6}\b|style="[^"]*(color|background)/');
});

it('Pagamento, Publicação e Fiscalização usam a pilha própria; as demais, as regras de status', function () {
    foreach (['pagamento' => true, 'publicacao' => true, 'fiscalizacao' => true, 'analises' => false] as $folder => $own) {
        $service = Service::create(['service' => ucfirst($folder), 'folder' => $folder]);

        expect(ActivityDashboardAnalytics::for($service))->toBeInstanceOf($own ? \App\Services\Dispatch\Dashboard\ReportPileDashboardAnalytics::class : ActivityDashboardAnalytics::class);
    }
});

it('o perfil de cada atividade descreve o prazo parametrizado e conta desde o informe', function () {
    $analytics = app(\App\Services\Dispatch\Dashboard\ReportPileDashboardAnalytics::class);

    $publication = $analytics->profile(Service::create(['service' => 'Publicação', 'folder' => 'publicacao']));
    $payment     = $analytics->profile(Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']));
    $supervision = $analytics->profile(Service::create(['service' => 'Fiscalização', 'folder' => 'fiscalizacao']));

    expect($publication['deadline']['title'])->toBe('Prazo de publicação')
        ->and($publication['deadline']['rule'])->toContain('2 dias corridos após a entrega do informe')
        ->and($payment['deadline']['title'])->toBe('Prazo da medição')
        ->and($supervision['deadline']['title'])->toBe('Prazo de entrega da ADS')
        ->and($supervision['unit'])->toBe('informes')
        ->and($publication['time']['axis'])->toBe('Dias desde o informe');
});

it('publicação vence 2 dias após a entrega do informe, em dias corridos ou úteis', function () {
    $policy = new \App\Services\Publication\PublicationDeadlinePolicy();

    expect($policy->dueAt(Carbon\Carbon::parse('2026-10-29 15:00'))->toDateString())->toBe('2026-10-31');

    config(['sicode.rules.' . \App\Support\SicodeRules::ruleset() . '.publication.deadline.business_days' => true]);

    // Quinta + 2 dias úteis = segunda (sábado e domingo não contam).
    expect((new \App\Services\Publication\PublicationDeadlinePolicy())->dueAt(Carbon\Carbon::parse('2026-10-29 15:00'))->toDateString())->toBe('2026-11-02');
});

it('conta os dias que faltam pelo calendário e marca vencido com negativo', function () {
    $policy = new \App\Services\Publication\PublicationDeadlinePolicy();
    $ref    = Carbon\Carbon::parse('2026-10-31 10:00');

    expect($policy->remainingDays(Carbon\Carbon::parse('2026-10-31 23:59'), $ref))->toBe(0)
        ->and($policy->remainingDays(Carbon\Carbon::parse('2026-11-02 23:59'), $ref))->toBe(2)
        ->and($policy->remainingDays(Carbon\Carbon::parse('2026-10-29 23:59'), $ref))->toBe(-2);
});
