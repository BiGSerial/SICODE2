<?php

use App\Models\{Company, Note, Order, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Schema};

uses(RefreshDatabase::class);

/** Migrations do Encerramento que tocam as tabelas operacionais, na ordem de execução. */
const CLOSURE_UPGRADE_MIGRATIONS = [
    '2026_10_06_143000_create_closure_order_operational_state_tables',
    '2026_10_07_090000_add_priority_to_closure_order_states_table',
    '2026_10_09_090000_create_closure_issue_tables',
    '2026_10_16_100000_add_meta_freeze_and_closure_settings',
    '2026_10_16_100000_create_closure_work_messages_table',
    '2026_10_16_100100_rename_closure_waiting_return_to_waiting_partner',
    '2026_10_16_100200_seed_closure_issue_taxonomy',
];

function closureRunUpgradeMigrations(): void
{
    foreach (CLOSURE_UPGRADE_MIGRATIONS as $name) {
        (require base_path("database/migrations/{$name}.php"))->up();
    }
}

function closureDropOperationalTables(): void
{
    Schema::disableForeignKeyConstraints();

    foreach ([
        'closure_work_messages', 'closure_order_state_periods', 'closure_issue_events', 'closure_issues',
        'closure_issue_subcategories', 'closure_issue_categories', 'closure_order_events', 'closure_order_states',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::enableForeignKeyConstraints();
}

/** Recria EXATAMENTE o esquema antigo (migrations de 2026-09-02, já aplicadas em QA/produção). */
function closureCreateLegacySchema(): void
{
    closureDropOperationalTables();

    Schema::create('closure_order_states', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->unique()->constrained('orders');
        $table->string('status')->default('AVAILABLE');
        $table->foreignUuid('assigned_user_id')->nullable()->constrained('users');
        $table->timestamp('assigned_at')->nullable();
        $table->timestamps();
        $table->index('status');
        $table->index('assigned_user_id');
    });
    Schema::table('closure_order_states', function (Blueprint $table) {
        $table->string('current_location_type')->nullable()->after('assigned_at');
        $table->foreignUuid('current_location_company_id')->nullable()->after('current_location_type')->constrained('companies');
    });
    Schema::create('closure_order_events', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->constrained('orders');
        $table->foreignUuid('actor_id')->nullable()->constrained('users');
        $table->string('type');
        $table->json('meta')->nullable();
        $table->timestamps();
        $table->index(['order_id', 'created_at'], 'closure_order_events_order_created_idx');
    });
    Schema::create('closure_issues', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->constrained('orders');
        $table->string('type');
        $table->string('reason')->nullable();
        $table->string('status')->default('OPEN');
        $table->string('owner_type')->nullable();
        $table->foreignUuid('owner_company_id')->nullable()->constrained('companies');
        $table->foreignUuid('owner_user_id')->nullable()->constrained('users');
        $table->foreignUuid('opened_by')->constrained('users');
        $table->timestamp('opened_at');
        $table->timestamp('requested_at')->nullable();
        $table->timestamp('remind_at')->nullable();
        $table->text('description')->nullable();
        $table->unsignedInteger('rework_count')->default(0);
        $table->timestamp('resolved_at')->nullable();
        $table->foreignUuid('resolved_by')->nullable()->constrained('users');
        $table->timestamps();
        $table->index('order_id');
        $table->index('status');
    });
    Schema::create('closure_issue_events', function (Blueprint $table) {
        $table->id();
        $table->foreignId('closure_issue_id')->constrained('closure_issues')->cascadeOnDelete();
        $table->foreignUuid('actor_id')->nullable()->constrained('users');
        $table->string('type');
        $table->json('meta')->nullable();
        $table->timestamps();
        $table->index(['closure_issue_id', 'created_at'], 'closure_issue_events_issue_created_idx');
    });
}

function closureLegacyFixture(): array
{
    $company = Company::query()->create(['name' => 'Legado ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()]);
    $user    = User::factory()->create(['company_id' => $company->id]);
    $note    = Note::query()->create(['note' => 'LEGACY-' . fake()->unique()->numerify('######'), 'type_note' => 1]);
    $orders  = collect(range(1, 6))->map(fn () => Order::query()->create([
        'note_id' => $note->id,
        'ordem'   => fake()->unique()->numerify('300#########'),
    ]));

    $now = now()->subDays(5)->toDateTimeString();
    $row = fn ($order, $status, array $extra = []) => array_merge([
        'order_id' => $order->id, 'status' => $status, 'created_at' => $now, 'updated_at' => $now,
    ], $extra);

    foreach ([
        $row($orders[0], 'AVAILABLE'),
        $row($orders[1], 'ASSIGNED', ['assigned_user_id' => $user->id, 'assigned_at' => $now, 'current_location_type' => 'PARTNER', 'current_location_company_id' => $company->id]),
        $row($orders[2], 'IN_PROGRESS', ['assigned_user_id' => $user->id, 'assigned_at' => $now]),
        $row($orders[3], 'WAITING'),
        $row($orders[4], 'READY'),
        $row($orders[5], 'CLOSED'),
    ] as $state) {
        DB::table('closure_order_states')->insert($state);
    }

    DB::table('closure_order_events')->insert([
        ['order_id' => $orders[2]->id, 'actor_id' => $user->id, 'type' => 'ASSUMED', 'meta' => '[]', 'created_at' => $now, 'updated_at' => $now],
        ['order_id' => $orders[2]->id, 'actor_id' => $user->id, 'type' => 'STARTED_PROGRESS', 'meta' => '[]', 'created_at' => $now, 'updated_at' => $now],
        ['order_id' => $orders[1]->id, 'actor_id' => $user->id, 'type' => 'LOCATION_CHANGED', 'meta' => json_encode(['location_type' => 'PARTNER', 'company_id' => $company->id]), 'created_at' => $now, 'updated_at' => $now],
    ]);

    $issue = fn ($order, $type, $status, array $extra = []) => array_merge([
        'order_id'  => $order->id, 'type' => $type, 'reason' => 'Baixa de Material', 'status' => $status, 'opened_by' => $user->id,
        'opened_at' => $now, 'description' => 'pendência legada', 'created_at' => $now, 'updated_at' => $now,
    ], $extra);

    DB::table('closure_issues')->insert($issue($orders[1], 'Trafo', 'WAITING_PARTNER', ['owner_type' => 'PARTNER', 'owner_company_id' => $company->id, 'requested_at' => $now, 'rework_count' => 1]));
    DB::table('closure_issues')->insert($issue($orders[1], 'MATERIAL', 'OPEN'));

    DB::table('closure_issue_events')->insert([
        ['closure_issue_id' => 1, 'actor_id' => $user->id, 'type' => 'OPENED', 'meta' => json_encode(['type' => 'Trafo']), 'created_at' => $now, 'updated_at' => $now],
        ['closure_issue_id' => 1, 'actor_id' => $user->id, 'type' => 'REQUESTED', 'meta' => null, 'created_at' => $now, 'updated_at' => $now],
        ['closure_issue_id' => 2, 'actor_id' => $user->id, 'type' => 'OPENED', 'meta' => '[]', 'created_at' => $now, 'updated_at' => $now],
    ]);

    return compact('company', 'user', 'note', 'orders');
}

function closureRestoreCurrentSchema(?array $fixture): void
{
    closureDropOperationalTables();
    closureRunUpgradeMigrations();

    if ($fixture === null) {
        return;
    }

    Schema::disableForeignKeyConstraints();
    Order::query()->whereIn('id', $fixture['orders']->pluck('id'))->delete();
    Note::query()->whereKey($fixture['note']->id)->delete();
    User::query()->whereKey($fixture['user']->id)->forceDelete();
    Company::query()->whereKey($fixture['company']->id)->delete();
    Schema::enableForeignKeyConstraints();
}

it('replaces the legacy Fase 2-4 closure schema by the new one (legacy data discarded on purpose) and never drops new data', function () {
    $fixture = null;
    closureCreateLegacySchema();

    try {
        $fixture = closureLegacyFixture();

        expect(DB::table('closure_order_states')->count())->toBe(6)
            ->and(DB::table('closure_issues')->count())->toBe(2);

        closureRunUpgradeMigrations();

        // Esquema novo; dados legados descartados de propósito (atividade ainda não está em uso).
        expect(Schema::hasColumn('closure_order_states', 'operational_status'))->toBeTrue()
            ->and(Schema::hasColumn('closure_order_states', 'status'))->toBeFalse()
            ->and(Schema::hasColumn('closure_order_states', 'priority'))->toBeTrue()
            ->and(Schema::hasColumn('closure_order_states', 'closed_meta_type'))->toBeTrue()
            ->and(Schema::hasColumn('closure_order_events', 'closure_order_state_id'))->toBeTrue()
            ->and(Schema::hasColumn('closure_issues', 'closure_issue_category_id'))->toBeTrue()
            ->and(Schema::hasColumn('closure_issues', 'type'))->toBeFalse()
            ->and(Schema::hasColumn('closure_issue_events', 'origin'))->toBeTrue()
            ->and(Schema::hasTable('closure_order_state_periods'))->toBeTrue()
            ->and(Schema::hasTable('closure_settings'))->toBeTrue()
            ->and(DB::table('closure_order_states')->count())->toBe(0)
            ->and(DB::table('closure_order_events')->count())->toBe(0)
            ->and(DB::table('closure_issues')->count())->toBe(0)
            ->and(DB::table('closure_issue_events')->count())->toBe(0)
            ->and(DB::table('closure_issue_categories')->where('slug', 'outros')->count())->toBe(1);

        // Linha criada no esquema novo sobrevive a nova execução (idempotência: nunca dropa esquema novo).
        $order = $fixture['orders'][0];
        DB::table('closure_order_states')->insert([
            'order_id' => $order->id, 'production_id' => DB::table('productions')->insertGetId([
                'note_id'    => $fixture['note']->id, 'service_id' => DB::table('services')->value('uuid'),
                'company_id' => $fixture['company']->id,
            ]), 'operational_status' => 'AVAILABLE', 'created_at' => now(), 'updated_at' => now(),
        ]);

        closureRunUpgradeMigrations();

        expect(DB::table('closure_order_states')->count())->toBe(1)
            ->and(DB::table('closure_issue_categories')->where('slug', 'outros')->count())->toBe(1);

        DB::table('closure_order_states')->delete();
        DB::table('productions')->where('note_id', $fixture['note']->id)->delete();
    } finally {
        closureRestoreCurrentSchema($fixture);
    }

    expect(Schema::hasColumn('closure_order_states', 'operational_status'))->toBeTrue()
        ->and(DB::table('closure_issue_categories')->where('slug', 'outros')->exists())->toBeTrue();
});
