<?php

use App\Http\Livewire\Closure\Dispatch\Dashboard;
use App\Models\Closure\{ClosureInternalReturn, ClosureIssue, ClosureIssueEvent, ClosurePartnerSignal};
use App\Models\{Company, Service, User};
use App\Services\Closure\Analytics\{ClosureDashboardReport, ClosureManagementAnalytics, ClosureManagementCharts};
use App\Services\Closure\ClosureArea;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

// Painel gerencial: principais problemas, por parceira, por atividade e o comparador mensal (melhoramos ou pioramos?).

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-11-15 10:00:00');
    \Illuminate\Support\Facades\Cache::flush();
});
afterEach(fn () => Carbon::setTestNow());

function mgmtIssue(array $f, array $attrs = []): ClosureIssue
{
    $taxonomy = $attrs['taxonomy'] ?? closureIssueTaxonomy();
    unset($attrs['taxonomy']);

    return ClosureIssue::query()->create(array_merge([
        'order_id'                     => $f['order']->id,
        'closure_order_state_id'       => $f['state']->id,
        'origin_area'                  => ClosureArea::CLOSURE,
        'closure_issue_category_id'    => $taxonomy['category']->id,
        'closure_issue_subcategory_id' => $taxonomy['subcategory']->id,
        'destination_area'             => ClosureArea::PARTNER,
        'destination_company_id'       => $f['company']->id,
        'status'                       => ClosureIssue::STATUS_REQUESTED,
        'opened_by'                    => $f['dispatcher']->id,
        'description'                  => 'x',
        'opened_at'                    => '2026-11-05 09:00:00',
        'requested_at'                 => '2026-11-05 09:00:00',
    ], $attrs));
}

function mgmtService(): ClosureManagementAnalytics
{
    return app(ClosureManagementAnalytics::class);
}

it('judges each indicator by its direction and treats small moves as stable', function () {
    expect(ClosureManagementAnalytics::verdict('issues_opened', 10, 14)['key'])->toBe('worse')
        ->and(ClosureManagementAnalytics::verdict('issues_opened', 10, 6)['key'])->toBe('better')
        ->and(ClosureManagementAnalytics::verdict('issues_resolved', 10, 14)['key'])->toBe('better')
        ->and(ClosureManagementAnalytics::verdict('issues_resolved', 10, 6)['key'])->toBe('worse')
        ->and(ClosureManagementAnalytics::verdict('response_days', 4.0, 4.1)['key'])->toBe('stable')
        ->and(ClosureManagementAnalytics::verdict('dispatched', 10, 20)['key'])->toBe('changed')
        ->and(ClosureManagementAnalytics::verdict('issues_opened', null, 3)['key'])->toBe('na')
        ->and(ClosureManagementAnalytics::verdict('issues_opened', 0, 3)['percent'])->toBe(100.0)
        ->and(ClosureManagementAnalytics::verdict('issues_opened', 0, 0)['key'])->toBe('na')
        ->and(ClosureManagementAnalytics::verdict('issues_opened', 10, 10)['key'])->toBe('stable');
});

it('compares the reference month with the previous one and says whether we improved', function () {
    $f = closureScreenFixture();

    // Outubro: 2 abertas, 2 resolvidas. Novembro: 5 abertas, 1 resolvida => piorou.
    foreach ([['2026-10-03', 'RESOLVED'], ['2026-10-10', 'RESOLVED']] as [$date, $status]) {
        mgmtIssue($f, ['opened_at' => $date . ' 09:00:00', 'status' => $status, 'resolved_at' => $date . ' 17:00:00']);
    }

    foreach (range(1, 4) as $day) {
        mgmtIssue($f, ['opened_at' => "2026-11-0{$day} 09:00:00"]);
    }
    mgmtIssue($f, ['opened_at' => '2026-11-06 09:00:00', 'status' => 'RESOLVED', 'resolved_at' => '2026-11-07 09:00:00']);

    $cmp  = mgmtService()->comparator(Carbon::parse('2026-11-30'));
    $rows = collect($cmp['rows'])->keyBy('key');

    expect(array_column($cmp['months'], 'key'))->toBe(['2026-06', '2026-07', '2026-08', '2026-09', '2026-10', '2026-11'])
        ->and($rows['issues_opened']['values'])->toBe([0, 0, 0, 0, 2, 5])
        ->and($rows['issues_opened']['verdict']['key'])->toBe('worse')
        ->and($rows['issues_resolved']['values'])->toBe([0, 0, 0, 0, 2, 1])
        ->and($rows['issues_resolved']['verdict']['key'])->toBe('worse')
        ->and($cmp['overall'])->toBe('worse')
        ->and($cmp['partial'])->toBeTrue()
        ->and($cmp['reference'])->toBe('novembro/2026');
});

it('averages the partner response time and counts corrections per month', function () {
    $f = closureScreenFixture();

    $a = mgmtIssue($f, ['opened_at' => '2026-10-01 08:00:00', 'requested_at' => '2026-10-01 08:00:00', 'responded_at' => '2026-10-03 08:00:00', 'status' => 'RESPONDED']);
    mgmtIssue($f, ['opened_at' => '2026-11-01 08:00:00', 'requested_at' => '2026-11-01 08:00:00', 'responded_at' => '2026-11-02 08:00:00', 'status' => 'RESPONDED']);
    ClosureIssueEvent::query()->create(['closure_issue_id' => $a->id, 'actor_id' => $f['dispatcher']->id, 'type' => 'RETURNED_FOR_CORRECTION', 'origin' => 'INTERNAL', 'created_at' => '2026-11-04 10:00:00']);

    $rows = collect(mgmtService()->comparator(Carbon::parse('2026-11-30'))['rows'])->keyBy('key');

    expect($rows['response_days']['values'])->toBe([null, null, null, null, 2.0, 1.0])
        ->and($rows['response_days']['verdict']['key'])->toBe('better')
        ->and($rows['returns']['values'][5])->toBe(1)
        ->and($rows['returns']['verdict']['key'])->toBe('worse');
});

it('ranks the main problems across issue reasons, internal returns and blocked deposits', function () {
    $f = closureScreenFixture();

    $same = closureIssueTaxonomy();

    foreach (range(1, 3) as $i) {
        mgmtIssue($f, ['opened_at' => "2026-11-0{$i} 09:00:00", 'taxonomy' => $same]);
    }
    mgmtIssue($f, ['destination_area' => ClosureArea::MEASUREMENT, 'destination_company_id' => null, 'opened_at' => '2026-11-04 09:00:00']);

    $design = Service::query()->firstOrCreate(['folder' => 'desenho'], ['service' => 'Desenho', 'project' => true, 'construction' => true, 'icon' => 'ri-pencil-line']);
    ClosureInternalReturn::query()->create(['note_id' => $f['order']->note_id, 'design_service_id' => $design->uuid, 'status' => 'SENT', 'cycle' => 1, 'message' => 'x', 'sent_by' => $f['dispatcher']->id, 'sent_at' => '2026-11-05 09:00:00']);

    foreach ([['ACTIVE', 7, '2026-11-06 09:00:00'], ['RELEASED', 2, '2026-09-06 09:00:00']] as [$status, $affected, $at]) {
        (new ClosurePartnerSignal())->forceFill(['company_id' => $f['company']->id, 'type' => ClosurePartnerSignal::TYPE_DEPOSIT_BLOCKED, 'origin' => 'PARTNER', 'deposit_code' => '5503', 'reason' => 'Teste', 'deposit_label' => 'Depósito 5503', 'status' => $status, 'affected_count' => $affected, 'created_at' => $at])->save();
    }

    $p = mgmtService()->problems(Carbon::parse('2026-11-01')->startOfDay(), Carbon::parse('2026-11-30')->endOfDay());

    expect($p['total'])->toBe(6)
        ->and($p['ranking'][0]['total'])->toBe(3)
        ->and($p['ranking'][0]['source'])->toBe('Pendência · Parceiras')
        ->and(collect($p['ranking'])->pluck('source')->all())->toContain('Pendência · Medição', 'RI · Desenho', 'Depósito bloqueado')
        ->and($p['deposits'][0]['total'])->toBe(1)
        ->and($p['deposits'][0]['affected'])->toBe(7);
});

it('breaks problems down by partner with response time, corrections and main reason', function () {
    $f      = closureScreenFixture();
    $second = Company::query()->create(['name' => 'Outra Empreiteira', 'email' => 'o@x.com']);

    mgmtIssue($f, ['opened_at' => '2026-11-01 08:00:00', 'requested_at' => '2026-11-01 08:00:00', 'responded_at' => '2026-11-03 08:00:00', 'status' => 'RESPONDED']);
    mgmtIssue($f, ['opened_at' => '2026-11-02 08:00:00']);
    $other = mgmtIssue($f, ['destination_company_id' => $second->id, 'opened_at' => '2026-11-02 08:00:00']);
    ClosureIssueEvent::query()->create(['closure_issue_id' => $other->id, 'actor_id' => $f['dispatcher']->id, 'type' => 'RETURNED_FOR_CORRECTION', 'origin' => 'INTERNAL', 'created_at' => '2026-11-05 10:00:00']);

    $rows = collect(mgmtService()->partners(Carbon::parse('2026-11-01')->startOfDay(), Carbon::parse('2026-11-30')->endOfDay()))->keyBy('company_id');

    expect($rows)->toHaveCount(2)
        ->and($rows[(string) $f['company']->id]['received'])->toBe(2)
        ->and($rows[(string) $f['company']->id]['open'])->toBe(2)
        ->and($rows[(string) $f['company']->id]['awaiting'])->toBe(1)
        ->and($rows[(string) $f['company']->id]['avg_days'])->toBe(2.0)
        ->and($rows[(string) $second->id]['returns'])->toBe(1)
        ->and($rows[(string) $f['company']->id]['top_reason'])->not->toBeNull();
});

it('breaks problems down by activity: partners, Medição, Fiscalização and Desenho (RI)', function () {
    $f = closureScreenFixture();

    mgmtIssue($f, ['opened_at' => '2026-11-02 08:00:00']);
    mgmtIssue($f, ['opened_at' => '2026-10-20 08:00:00']);
    mgmtIssue($f, ['destination_area' => ClosureArea::SUPERVISION, 'destination_company_id' => null, 'opened_at' => '2026-11-03 08:00:00']);

    $design = Service::query()->firstOrCreate(['folder' => 'desenho'], ['service' => 'Desenho', 'project' => true, 'construction' => true, 'icon' => 'ri-pencil-line']);
    ClosureInternalReturn::query()->create(['note_id' => $f['order']->note_id, 'design_service_id' => $design->uuid, 'status' => 'RETURNED', 'cycle' => 1, 'message' => 'x', 'sent_by' => $f['dispatcher']->id, 'sent_at' => '2026-11-01 08:00:00', 'returned_at' => '2026-11-04 08:00:00', 'closed_at' => '2026-11-04 08:00:00']);

    $rows = collect(mgmtService()->activities(Carbon::parse('2026-11-01')->startOfDay(), Carbon::parse('2026-11-30')->endOfDay()))->keyBy('code');

    expect(array_keys($rows->all()))->toBe(['PARTNER', 'MEASUREMENT', 'SUPERVISION', 'DESIGN'])
        ->and($rows['PARTNER']['received'])->toBe(1)
        ->and($rows['PARTNER']['received_prev'])->toBe(1)
        ->and($rows['SUPERVISION']['received'])->toBe(1)
        ->and($rows['MEASUREMENT']['received'])->toBe(0)
        ->and($rows['DESIGN']['received'])->toBe(1)
        ->and($rows['DESIGN']['resolved'])->toBe(1)
        ->and($rows['DESIGN']['avg_days'])->toBe(3.0);
});

it('keeps the chart contract: fixed dataset counts and in-place friendly payloads', function () {
    $f = closureScreenFixture();
    mgmtIssue($f);

    $management = mgmtService()->snapshot(['from' => '2026-11-01', 'to' => '2026-11-30']);
    $matrix     = ['rows' => [], 'totals' => ['status' => array_fill_keys(\App\Services\Closure\Analytics\ClosureDispatcherAnalytics::MATRIX_STATUSES, 0), 'total' => 0]];
    $charts     = ClosureManagementCharts::all($management, $matrix);

    expect(array_keys($charts))->toBe(['trend', 'partners', 'activities', 'users', 'status'])
        ->and($charts['trend']['data']['datasets'])->toHaveCount(3)
        ->and($charts['partners']['data']['datasets'])->toHaveCount(3)
        ->and($charts['activities']['data']['datasets'])->toHaveCount(3)
        ->and($charts['users']['data']['datasets'])->toHaveCount(4)
        ->and($charts['status']['data']['datasets'])->toHaveCount(1)
        ->and($charts['trend']['data']['labels'])->toHaveCount(ClosureManagementAnalytics::MONTHS);
});

it('renders the management sections for the dispatcher and keeps filters valid', function () {
    $f = closureScreenFixture();
    mgmtIssue($f);

    $dispatcher = User::factory()->create(['closure_manager' => true, 'closure_operator' => false, 'admin' => false, 'superadm' => false, 'company_id' => $f['dispatcher']->company_id]);

    Livewire::actingAs($dispatcher)->test(Dashboard::class)
        ->assertSee('Melhoramos ou pioramos?')
        ->assertSee('Principais problemas')
        ->assertSee('Problemas por parceira')
        ->assertSee('Problemas por atividade')
        ->assertSee('Comparador mensal')
        ->set('periodFrom', '2026-11-30')->set('periodTo', '2026-11-01')
        ->assertSet('periodFrom', '2026-11-01')->assertSet('periodTo', '2026-11-30')
        ->call('setPreset', 'prev')
        ->assertSet('periodFrom', '2026-10-01')->assertSet('periodTo', '2026-10-31')
        ->assertDispatchedBrowserEvent('my-panel:charts');
});

it('puts the management blocks in the Excel report with the same numbers as the screen', function () {
    $f = closureScreenFixture();
    mgmtIssue($f);

    $report = app(ClosureDashboardReport::class)->build(['period_from' => '2026-11-01', 'period_to' => '2026-11-30']);
    $m      = $report['management'];

    expect($m['period'])->toBe('01/11/2026 a 30/11/2026')
        ->and($m['months'])->toHaveCount(6)
        ->and($m['comparator'][0][0])->toBe('Ordens despachadas')
        ->and(count($m['comparator'][0]))->toBe(1 + 6 + 3)
        ->and($m['partners'][0][1])->toBe(1)
        ->and($m['activities'])->toHaveCount(4)
        ->and($report['filters'])->toHaveKey('Período (problemas, parceiras, atividades)');
});
