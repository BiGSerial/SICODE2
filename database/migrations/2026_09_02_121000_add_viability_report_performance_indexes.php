<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('viabilities', function (Blueprint $table) {
            if (!$this->indexExists('viabilities', 'idx_viab_report_sended_id')) {
                $table->index(['sended_at', 'id'], 'idx_viab_report_sended_id');
            }

            if (!$this->indexExists('viabilities', 'idx_viab_report_hired_id')) {
                $table->index(['hired_at', 'id'], 'idx_viab_report_hired_id');
            }

            if (!$this->indexExists('viabilities', 'idx_viab_report_completed_id')) {
                $table->index(['completed_at', 'id'], 'idx_viab_report_completed_id');
            }
        });

        Schema::table('order_viability', function (Blueprint $table) {
            if (!$this->indexExists('order_viability', 'idx_order_viability_viab_order')) {
                $table->index(['viability_id', 'order_id'], 'idx_order_viability_viab_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_viability', function (Blueprint $table) {
            if ($this->indexExists('order_viability', 'idx_order_viability_viab_order')) {
                $table->dropIndex('idx_order_viability_viab_order');
            }
        });

        Schema::table('viabilities', function (Blueprint $table) {
            if ($this->indexExists('viabilities', 'idx_viab_report_completed_id')) {
                $table->dropIndex('idx_viab_report_completed_id');
            }

            if ($this->indexExists('viabilities', 'idx_viab_report_hired_id')) {
                $table->dropIndex('idx_viab_report_hired_id');
            }

            if ($this->indexExists('viabilities', 'idx_viab_report_sended_id')) {
                $table->dropIndex('idx_viab_report_sended_id');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
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
                [$table, $index]
            );
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
