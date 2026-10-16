<?php

use App\Models\Closure\{ClosureIssue, ClosureIssueCategory, ClosureIssueSubcategory, ClosureOrderState, ClosurePriorityRequest};
use App\Models\{Company, Note, Operation, Order, Production, Service, User};
use App\Services\Closure\Analytics\ClosureDispatcherAnalytics;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow('2026-10-16 10:00:00'));
afterEach(fn () => Carbon::setTestNow());

/** fimReal por tipo na data de teste 2026-10-16. */
function analyticsFim(string $type): string
{
    return ['P' => '2026-06-10', 'M' => '2026-09-10', 'F' => '2026-10-05'][$type];
}

function analyticsCompany(string $name = 'Parceira'): Company
{
    return Company::query()->create(['name' => $name . ' ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()]);
}

/** Cria Nota + Ordem + Production + estado; $type null = sem 0020 (sem tipo). */
function analyticsOrder(?User $user, string $status, ?string $type, ?Company $company = null, array $state = [], ?Note $note = null): ClosureOrderState
{
    $service = Service::query()->firstOrCreate(
        ['folder' => 'encerramento'],
        ['service' => 'Encerramento', 'project' => true, 'construction' => true, 'icon' => 'ri-checkbox-circle-line']
    );
    $company ??= analyticsCompany();
    $note ??= Note::query()->create(['note' => (string) fake()->unique()->numberBetween(1000000, 9999999), 'type_note' => 1]);
    $order = Order::query()->create(['note_id' => $note->id, 'ordem' => (string) fake()->unique()->numberBetween(100000000, 999999999), 'statusSist' => 'LIB CNPA', 'canceled' => false]);

    if ($type !== null) {
        Operation::query()->create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'CONF', 'fimReal' => analyticsFim($type)]);
    }

    $production = Production::query()->firstOrCreate(
        ['note_id' => $note->id, 'service_id' => $service->uuid],
        ['company_id' => $company->id]
    );

    return ClosureOrderState::query()->create(array_merge([
        'order_id'                  => $order->id,
        'production_id'             => $production->id,
        'operational_status'        => $status,
        'assigned_user_id'          => $user?->id,
        'current_holder_type'       => 'PARTNER_COMPANY',
        'current_holder_company_id' => $company->id,
        'entered_current_status_at' => now()->subDay(),
    ], $state));
}

function analyticsIssue(ClosureOrderState $state, ?User $opener, Company $partner, string $status, array $attrs = []): ClosureIssue
{
    $category    = ClosureIssueCategory::query()->firstOrCreate(['name' => 'Cat An']);
    $subcategory = ClosureIssueSubcategory::query()->firstOrCreate(['closure_issue_category_id' => $category->id, 'name' => 'Sub An']);

    return ClosureIssue::query()->create(array_merge([
        'order_id'                     => $state->order_id,
        'closure_order_state_id'       => $state->id,
        'closure_issue_category_id'    => $category->id,
        'closure_issue_subcategory_id' => $subcategory->id,
        'destination_company_id'       => $partner->id,
        'opened_by'                    => $opener?->id,
        'status'                       => $status,
        'cycle'                        => 1,
        'blocking'                     => false,
        'opened_at'                    => now()->subDays(30),
    ], $attrs));
}

it('counts the matrix per user, type and status with controlled data', function () {
    $a = User::factory()->create(['name' => 'Ana']);
    $b = User::factory()->create(['name' => 'Bruno']);

    analyticsOrder($a, 'IN_PROGRESS', 'P');
    analyticsOrder($a, 'ASSIGNED', 'M');
    analyticsOrder($a, 'PAUSED', 'M');
    analyticsOrder($a, 'IN_PROGRESS', 'F');
    analyticsOrder($b, 'WAITING_PARTNER', 'P');
    analyticsOrder(null, 'AVAILABLE', 'P');
    analyticsOrder($a, 'CLOSED', 'M', null, ['closed_meta_type' => 'M', 'closed_meta_period' => '2026-10']);
    analyticsOrder($b, 'CANCELLED', 'P');

    $matrix = app(ClosureDispatcherAnalytics::class)->matrix();
    $rows   = collect($matrix['rows'])->keyBy('name');

    expect($matrix['totals']['total'])->toBe(6)
        ->and($matrix['totals']['types'])->toBe(['P' => 3, 'M' => 2, 'F' => 1, 'N' => 0])
        ->and($rows['Ana']['total'])->toBe(4)
        ->and($rows['Ana']['types'])->toBe(['P' => 1, 'M' => 2, 'F' => 1, 'N' => 0])
        ->and($rows['Ana']['status']['IN_PROGRESS'])->toBe(2)
        ->and($rows['Ana']['cells']['IN_PROGRESS']['P'])->toBe(1)
        ->and($rows['Ana']['cells']['IN_PROGRESS']['F'])->toBe(1)
        ->and($rows['Ana']['cells']['PAUSED']['M'])->toBe(1)
        ->and($rows['Bruno']['status']['WAITING_PARTNER'])->toBe(1)
        ->and($rows['Bruno']['total'])->toBe(1)
        ->and($rows['Sem responsável (pilha)']['status']['AVAILABLE'])->toBe(1)
        ->and($matrix['rows'][0]['name'])->toBe('Ana');
});

it('uses the frozen type for finished orders and keeps untyped ones in the total', function () {
    $a = User::factory()->create(['name' => 'Ana']);

    // fimReal diz P, mas o tipo congelado ao finalizar é M.
    analyticsOrder($a, 'FINISHED', 'P', null, ['closed_meta_type' => 'M', 'closed_meta_period' => '2026-10']);
    analyticsOrder($a, 'WAITING', null);

    $row = app(ClosureDispatcherAnalytics::class)->matrix()['rows'][0];

    expect($row['total'])->toBe(2)
        ->and($row['types'])->toBe(['P' => 0, 'M' => 1, 'F' => 0, 'N' => 1])
        ->and($row['status']['FINISHED'])->toBe(1)
        ->and($row['status']['WAITING'])->toBe(1);
});

it('applies global filters for type, company and period', function () {
    $a       = User::factory()->create(['name' => 'Ana']);
    $partner = analyticsCompany('Alfa');
    $other   = analyticsCompany('Beta');

    analyticsOrder($a, 'IN_PROGRESS', 'P', $partner);
    analyticsOrder($a, 'IN_PROGRESS', 'M', $partner);
    analyticsOrder($a, 'ASSIGNED', 'P', $other, ['entered_current_status_at' => now()->subDays(20)]);

    $analytics = app(ClosureDispatcherAnalytics::class);

    expect($analytics->matrix(['type' => 'P'])['totals']['total'])->toBe(2)
        ->and($analytics->matrix(['company_id' => $partner->id])['totals']['total'])->toBe(2)
        ->and($analytics->matrix(['company_id' => $other->id, 'type' => 'M'])['totals']['total'])->toBe(0)
        ->and($analytics->matrix(['from' => now()->subDays(5)->toDateString()])['totals']['total'])->toBe(2)
        ->and($analytics->matrix(['to' => now()->subDays(10)->toDateString()])['totals']['total'])->toBe(1);
});

it('computes the summary cards and the meta percentage of the cycle', function () {
    $a = User::factory()->create();

    analyticsOrder($a, 'IN_PROGRESS', 'M');
    analyticsOrder($a, 'PAUSED', 'M');
    analyticsOrder($a, 'WAITING_PARTNER', 'P');
    analyticsOrder($a, 'FINISHED', 'F');
    analyticsOrder($a, 'WAITING', 'F');
    analyticsOrder($a, 'CLOSED', 'M', null, ['closed_meta_type' => 'M', 'closed_meta_period' => '2026-10']);
    analyticsOrder($a, 'CLOSED', 'M', null, ['closed_meta_type' => 'M', 'closed_meta_period' => '2026-09']);

    $summary = app(ClosureDispatcherAnalytics::class)->summary();

    expect($summary['total'])->toBe(5)
        ->and($summary['types'])->toBe(['P' => 1, 'M' => 2, 'F' => 2, 'N' => 0])
        ->and($summary['meta_closed'])->toBe(1)
        ->and($summary['meta_total'])->toBe(3)
        ->and($summary['meta_percent'])->toBe(33.3)
        ->and($summary['waiting_partner'])->toBe(1)
        ->and($summary['paused'])->toBe(1)
        ->and($summary['finished'])->toBe(1)
        ->and($summary['waiting'])->toBe(1);
});

it('aggregates the partner and user interaction from issues', function () {
    $a       = User::factory()->create(['name' => 'Ana']);
    $b       = User::factory()->create(['name' => 'Bruno']);
    $partner = analyticsCompany('Parceira Z');
    $state   = analyticsOrder($a, 'WAITING_PARTNER', 'M', $partner);

    analyticsIssue($state, $a, $partner, 'REQUESTED', ['requested_at' => now()->subHours(2)]);
    analyticsIssue($state, $b, $partner, 'REQUESTED', ['requested_at' => now()->subHours(4)]);
    analyticsIssue($state, $a, $partner, 'RESPONDED', ['requested_at' => now()->subHours(10), 'responded_at' => now()->subHours(8), 'cycle' => 2]);
    $returned = analyticsIssue($state, $b, $partner, 'RETURNED', ['requested_at' => now()->subHours(20), 'responded_at' => now()->subHours(19), 'cycle' => 2]);
    analyticsIssue($state, $b, $partner, 'RESOLVED', ['requested_at' => now()->subHours(5), 'responded_at' => now()->subHours(4), 'resolved_at' => now()->subHours(3)]);

    DB::table('closure_issue_events')->insert([
        'closure_issue_id' => $returned->id, 'type' => 'RETURNED_FOR_CORRECTION', 'origin' => 'INTERNAL',
        'created_at'       => now()->subHour(),
    ]);

    $interaction = app(ClosureDispatcherAnalytics::class)->partnerInteraction();
    $p           = $interaction['partners']->firstWhere('company_id', $partner->id);
    $users       = $interaction['users']->keyBy('name');

    expect($p['open'])->toBe(4)
        ->and($p['awaiting'])->toBe(3)
        ->and($p['avg_wait'])->toBe(8400)   // (2h + 4h + 1h) / 3
        ->and($p['max_wait'])->toBe(14400)
        ->and($p['returns'])->toBe(2)
        ->and($p['responded'])->toBe(3)
        ->and($p['avg_response'])->toBe(4800) // (2h + 1h + 1h) / 3
        ->and($users['Ana']['sent'])->toBe(2)
        ->and($users['Ana']['awaiting'])->toBe(1)
        ->and($users['Ana']['avg_response'])->toBe(7200)
        ->and($users['Bruno']['sent'])->toBe(3)
        ->and($users['Bruno']['awaiting'])->toBe(2)
        ->and($users['Bruno']['responded'])->toBe(2);
});

it('lists critical works by old passive orders and stuck issues', function () {
    $a       = User::factory()->create();
    $partner = analyticsCompany();

    $old = analyticsOrder($a, 'IN_PROGRESS', 'P', $partner, ['entered_current_status_at' => now()->subDays(10)]);
    analyticsOrder($a, 'IN_PROGRESS', 'P', $partner, ['entered_current_status_at' => now()->subDays(2)]);
    analyticsOrder($a, 'IN_PROGRESS', 'M', $partner, ['entered_current_status_at' => now()->subDays(30)]); // M não conta
    $stuck = analyticsOrder($a, 'WAITING_PARTNER', 'M', $partner, ['entered_current_status_at' => now()->subHour()]);
    analyticsIssue($stuck, $a, $partner, 'REQUESTED', ['requested_at' => now()->subDays(9)]);

    $works = app(ClosureDispatcherAnalytics::class)->criticalWorks(7)->keyBy('note_id');

    expect($works)->toHaveCount(2)
        ->and($works[$old->Order->note_id]['stale_passive'])->toBe(1)
        ->and($works[$old->Order->note_id]['oldest_days'])->toBe(10)
        ->and($works[$old->Order->note_id]['stuck_issues'])->toBe(0)
        ->and($works[$stuck->Order->note_id]['stuck_issues'])->toBe(1)
        ->and($works[$stuck->Order->note_id]['stuck_days'])->toBe(9)
        ->and(app(ClosureDispatcherAnalytics::class)->criticalWorks(9))->toHaveCount(1);
});

it('returns the orders of a clicked cell and only pending priority requests', function () {
    $a = User::factory()->create(['name' => 'Ana']);
    $b = User::factory()->create(['name' => 'Bruno']);

    analyticsOrder($a, 'IN_PROGRESS', 'P');
    analyticsOrder($a, 'IN_PROGRESS', 'M');
    analyticsOrder($b, 'PAUSED', 'P');
    analyticsOrder(null, 'AVAILABLE', 'F');

    $analytics = app(ClosureDispatcherAnalytics::class);

    expect($analytics->orders([], [])->count())->toBe(4)
        ->and($analytics->orders([], ['user' => (string) $a->id])->count())->toBe(2)
        ->and($analytics->orders([], ['user' => (string) $a->id, 'type' => 'P'])->count())->toBe(1)
        ->and($analytics->orders([], ['status' => 'PAUSED'])->first()->responsible_name)->toBe('Bruno')
        ->and($analytics->orders([], ['user' => ClosureDispatcherAnalytics::NO_USER])->first()->meta_type)->toBe('F');

    $note = Note::query()->create(['note' => '777', 'type_note' => 1]);
    ClosurePriorityRequest::query()->create(['note_id' => $note->id, 'requested_by' => $a->id, 'reason' => 'x', 'status' => 'PENDING']);
    ClosurePriorityRequest::query()->create(['note_id' => $note->id, 'requested_by' => $a->id, 'reason' => 'y', 'status' => 'DECLINED']);

    expect($analytics->pendingPriorityRequests())->toHaveCount(1);
});
