<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('legal_demand_assignments', 'uuid')) {
            Schema::table('legal_demand_assignments', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });
        }

        // Popula registros existentes
        DB::table('legal_demand_assignments')
            ->whereNull('uuid')
            ->orderBy('id')
            ->each(function (object $row): void {
                DB::table('legal_demand_assignments')
                    ->where('id', $row->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });

        if ($this->hasIndex('legal_demand_assignments', 'legal_demand_assignments_uuid_unique')) {
            Schema::table('legal_demand_assignments', function (Blueprint $table) {
                $table->dropUnique('legal_demand_assignments_uuid_unique');
            });
        }

        // Torna NOT NULL após popular
        Schema::table('legal_demand_assignments', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        if (!$this->hasIndex('legal_demand_assignments', 'legal_demand_assignments_uuid_unique')) {
            Schema::table('legal_demand_assignments', function (Blueprint $table) {
                $table->unique('uuid', 'legal_demand_assignments_uuid_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('legal_demand_assignments', 'legal_demand_assignments_uuid_unique')) {
            Schema::table('legal_demand_assignments', function (Blueprint $table) {
                $table->dropUnique('legal_demand_assignments_uuid_unique');
            });
        }

        Schema::table('legal_demand_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('legal_demand_assignments', 'uuid')) {
                $table->dropColumn('uuid');
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlsrv') {
            return (bool) DB::selectOne(
                'SELECT 1
                 FROM sys.indexes AS idx
                 INNER JOIN sys.tables AS tbl ON idx.object_id = tbl.object_id
                 INNER JOIN sys.schemas AS scm ON tbl.schema_id = scm.schema_id
                 WHERE tbl.name = ?
                   AND scm.name = SCHEMA_NAME()
                   AND idx.name = ?',
                [$table, $indexName]
            );
        }

        $dbName = DB::getDatabaseName();

        return (bool) DB::selectOne(
            'SELECT 1
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND index_name = ?
             LIMIT 1',
            [$dbName, $table, $indexName]
        );
    }
};
