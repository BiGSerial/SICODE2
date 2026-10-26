<?php

use App\Models\{Note};
use App\Support\Database\GuardedTableRecreator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Schema};

// Migrations da Fase 1 (caixas de pendência + RI). Regras: docs/encerramento/CAIXAS_PENDENCIAS_E_RI_CHECKLIST.md §2.1.
// Rodam no banco de teste (sicode_teste): sempre com -e DB_DATABASE=sicode_teste.

uses(RefreshDatabase::class);

const INBOX_MIGRATIONS = [
    'issues'  => '2026_10_23_100000_add_area_routing_to_closure_issues_table',
    'states'  => '2026_10_23_100100_add_status_user_tracking_to_closure_order_states_table',
    'routes'  => '2026_10_23_100200_create_closure_status_user_routes_table',
    'returns' => '2026_10_23_100300_create_closure_internal_returns_table',
];

function inboxMigration(string $key): object
{
    return require base_path('database/migrations/' . INBOX_MIGRATIONS[$key] . '.php');
}

function inboxNote(): Note
{
    return Note::query()->create(['note' => 'RI-' . fake()->unique()->numerify('######'), 'type_note' => 1]);
}

function inboxReturnRow(int $noteId, string $status = 'SENT', string $service = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'): array
{
    // closed_at é o que libera o índice único de RI ativo: o service o preenche ao retornar/cancelar.
    return [
        'note_id'           => $noteId,
        'design_service_id' => $service,
        'status'            => $status,
        'sent_at'           => now(),
        'closed_at'         => $status === 'SENT' ? null : now(),
        'created_at'        => now(),
        'updated_at'        => now(),
    ];
}

it('adds the area columns to closure_issues additively and keeps existing rows as partner issues', function () {
    expect(Schema::hasColumns('closure_issues', ['origin_area', 'destination_area', 'kind', 'source']))->toBeTrue()
        ->and(Schema::hasColumns('closure_issues', ['blocking', 'requirements', 'destination_company_id', 'status', 'cycle']))->toBeTrue()
        ->and(GuardedTableRecreator::hasIndex('closure_issues', 'closure_issue_destination_area_status_index'))->toBeTrue();

    $defaults = collect(DB::select('SHOW COLUMNS FROM closure_issues'))->keyBy('Field');

    expect($defaults['origin_area']->Default)->toBe('CLOSURE')
        ->and($defaults['destination_area']->Default)->toBe('PARTNER')
        ->and($defaults['kind']->Default)->toBe('ISSUE')
        ->and($defaults['source']->Default)->toBe('MANUAL');
});

it('is idempotent on closure_issues and closure_order_states (runs twice without error or data loss)', function () {
    inboxMigration('issues')->up();
    inboxMigration('issues')->up();
    inboxMigration('states')->up();
    inboxMigration('states')->up();

    expect(Schema::hasColumns('closure_issues', ['origin_area', 'destination_area', 'kind', 'source']))->toBeTrue()
        ->and(Schema::hasColumns('closure_order_states', ['status_user_seen', 'status_user_changed_at', 'status_user_estimated']))->toBeTrue();
});

it('removes only its own columns on down and never the existing ones', function () {
    inboxMigration('issues')->down();
    inboxMigration('states')->down();

    expect(Schema::hasColumn('closure_issues', 'destination_area'))->toBeFalse()
        ->and(Schema::hasColumn('closure_issues', 'kind'))->toBeFalse()
        ->and(Schema::hasColumn('closure_order_states', 'status_user_seen'))->toBeFalse()
        // nada que já existia foi removido
        ->and(Schema::hasColumns('closure_issues', ['blocking', 'requirements', 'destination_company_id', 'opened_at']))->toBeTrue()
        ->and(Schema::hasColumns('closure_order_states', ['operational_status', 'priority', 'assigned_user_id', 'entered_current_status_at']))->toBeTrue();

    inboxMigration('issues')->up();
    inboxMigration('states')->up();

    expect(Schema::hasColumn('closure_issues', 'destination_area'))->toBeTrue();
});

it('creates the status user routes table with a unique prefix', function () {
    expect(Schema::hasTable('closure_status_user_routes'))->toBeTrue();

    DB::table('closure_status_user_routes')->insert(['area' => 'MEASUREMENT', 'status_user_prefix' => 'JAND INVE', 'created_at' => now(), 'updated_at' => now()]);

    expect(fn () => DB::table('closure_status_user_routes')->insert(['area' => 'SUPERVISION', 'status_user_prefix' => 'JAND INVE', 'created_at' => now(), 'updated_at' => now()]))
        ->toThrow(QueryException::class);
});

it('does not touch a new table that already has the expected shape', function () {
    DB::table('closure_status_user_routes')->insert(['area' => 'MEASUREMENT', 'status_user_prefix' => 'ABCD EFGH', 'created_at' => now(), 'updated_at' => now()]);

    inboxMigration('routes')->up();

    expect(DB::table('closure_status_user_routes')->where('status_user_prefix', 'ABCD EFGH')->exists())->toBeTrue();
});

it('recreates a new module table that exists with a divergent shape', function () {
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('closure_status_user_routes');
    Schema::enableForeignKeyConstraints();
    Schema::create('closure_status_user_routes', function ($table) {
        $table->id();
        $table->string('legacy_only');
    });
    DB::table('closure_status_user_routes')->insert(['legacy_only' => 'x']);

    inboxMigration('routes')->up();

    expect(Schema::hasColumns('closure_status_user_routes', ['area', 'status_user_prefix', 'created_by']))->toBeTrue()
        ->and(Schema::hasColumn('closure_status_user_routes', 'legacy_only'))->toBeFalse()
        ->and(DB::table('closure_status_user_routes')->count())->toBe(0);
});

it('allows only one active internal return per note, enforced by the database', function () {
    $note = inboxNote();

    DB::table('closure_internal_returns')->insert(inboxReturnRow($note->id));

    expect(fn () => DB::table('closure_internal_returns')->insert(inboxReturnRow($note->id)))->toThrow(QueryException::class);

    // outra Nota, ou RI já encerrado, não conflitam
    DB::table('closure_internal_returns')->insert(inboxReturnRow(inboxNote()->id));
    DB::table('closure_internal_returns')->insert(inboxReturnRow($note->id, 'RETURNED'));
    DB::table('closure_internal_returns')->insert(inboxReturnRow($note->id, 'CANCELLED'));

    expect(DB::table('closure_internal_returns')->where('note_id', $note->id)->count())->toBe(3);

    // após o retorno, um novo RI (ciclo seguinte) pode ser enviado
    DB::table('closure_internal_returns')->where('note_id', $note->id)->where('status', 'SENT')->update(['status' => 'RETURNED', 'closed_at' => now()]);
    DB::table('closure_internal_returns')->insert(inboxReturnRow($note->id));

    expect(DB::table('closure_internal_returns')->where('note_id', $note->id)->where('status', 'SENT')->count())->toBe(1);
});

it('recreates a divergent internal returns table', function () {
    Schema::dropIfExists('closure_internal_returns');
    Schema::create('closure_internal_returns', function ($table) {
        $table->id();
        $table->unsignedBigInteger('note_id');
    });

    inboxMigration('returns')->up();

    expect(Schema::hasColumns('closure_internal_returns', ['reclaim_id', 'design_service_id', 'closed_at', 'active_note_id', 'cancel_reason']))->toBeTrue()
        ->and(GuardedTableRecreator::hasIndex('closure_internal_returns', 'closure_internal_return_active_unique'))->toBeTrue();
});

it('aborts and drops nothing when a table outside the module references the one to recreate', function () {
    $note = inboxNote();
    DB::table('closure_internal_returns')->insert(inboxReturnRow($note->id));
    $rowId = (int) DB::table('closure_internal_returns')->value('id');

    // Torna a tabela "divergente" (falta uma coluna esperada) e cria um dependente EXTERNO ao módulo.
    Schema::table('closure_internal_returns', fn ($table) => $table->dropColumn('cancel_reason'));
    Schema::create('zz_external_dependent', function ($table) {
        $table->id();
        $table->unsignedBigInteger('internal_return_id');
        $table->foreign('internal_return_id')->references('id')->on('closure_internal_returns');
    });

    try {
        expect(fn () => inboxMigration('returns')->up())->toThrow(RuntimeException::class, 'fora do módulo');
        expect(DB::table('closure_internal_returns')->where('id', $rowId)->exists())->toBeTrue();
    } finally {
        Schema::dropIfExists('zz_external_dependent');
    }
});

it('refuses to recreate tables that are not module-owned', function () {
    expect(fn () => GuardedTableRecreator::assertOwned('orders', ['orders']))->toThrow(RuntimeException::class)
        ->and(fn () => GuardedTableRecreator::assertOwned('reclaims', ['closure_status_user_routes']))->toThrow(RuntimeException::class)
        ->and(fn () => GuardedTableRecreator::assertOwned('closure_issues', ['closure_status_user_routes']))->toThrow(RuntimeException::class);

    GuardedTableRecreator::assertOwned('closure_internal_returns', ['closure_internal_returns']);

    expect(true)->toBeTrue();
});

it('never touches tables of other activities (reclaims keeps its shape and has no FK to the module)', function () {
    $before = Schema::getColumnListing('reclaims');

    foreach (INBOX_MIGRATIONS as $key => $_) {
        inboxMigration($key)->up();
    }

    expect(Schema::getColumnListing('reclaims'))->toBe($before)
        ->and(Schema::getColumnListing('orders'))->toContain('statusUser');
});
