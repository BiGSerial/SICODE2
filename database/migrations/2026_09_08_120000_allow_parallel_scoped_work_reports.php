<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->indexExists('work_reports', 'uq_work_reports_single_active_note')) {
            Schema::table('work_reports', function (Blueprint $table) {
                $table->dropUnique('uq_work_reports_single_active_note');
            });
        }

        if (Schema::hasColumn('work_reports', 'active_note_id')) {
            Schema::table('work_reports', function (Blueprint $table) {
                $table->dropColumn('active_note_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('work_reports', 'active_note_id')) {
                $table->unsignedBigInteger('active_note_id')
                    ->storedAs('CASE WHEN canceled = 0 THEN note_id ELSE NULL END')
                    ->after('note_id');
            }
        });

        if (!$this->indexExists('work_reports', 'uq_work_reports_single_active_note')) {
            Schema::table('work_reports', function (Blueprint $table) {
                $table->unique('active_note_id', 'uq_work_reports_single_active_note');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlsrv') {
            return (bool) DB::selectOne("
                SELECT 1
                FROM sys.indexes AS idx
                INNER JOIN sys.tables AS tbl ON idx.object_id = tbl.object_id
                INNER JOIN sys.schemas AS scm ON tbl.schema_id = scm.schema_id
                WHERE tbl.name = ?
                  AND scm.name = SCHEMA_NAME()
                  AND idx.name = ?
            ", [$table, $index]);
        }

        $row = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND index_name = ?
            LIMIT 1
        ", [$table, $index]);

        return (bool) $row;
    }
};
