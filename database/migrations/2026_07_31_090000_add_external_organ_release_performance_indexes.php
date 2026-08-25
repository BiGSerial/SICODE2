<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('external_organ_releases', function (Blueprint $table) {
            if (!$this->indexExists('external_organ_releases', 'idx_eor_pending_export_created')) {
                $table->index(['released_at', 'exported_at', 'created_at', 'id'], 'idx_eor_pending_export_created');
            }
        });

        Schema::table('notes', function (Blueprint $table) {
            if (!$this->indexExists('notes', 'idx_notes_type_nstats_dt_note')) {
                $table->index(['type_note', 'nstats', 'dt_status', 'note'], 'idx_notes_type_nstats_dt_note');
            }
        });

        Schema::table('analises', function (Blueprint $table) {
            if (!$this->indexExists('analises', 'idx_analises_production_id')) {
                $table->index(['production_id'], 'idx_analises_production_id');
            }
        });

        Schema::table('project_review_orders', function (Blueprint $table) {
            if (!$this->indexExists('project_review_orders', 'idx_pro_orders_cycle_client')) {
                $table->index(['cycle_id', 'client_cost'], 'idx_pro_orders_cycle_client');
            }

            if (!$this->indexExists('project_review_orders', 'idx_pro_orders_cycle_company_client')) {
                $table->index(['cycle_id', 'company_cost', 'client_cost'], 'idx_pro_orders_cycle_company_client');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_review_orders', function (Blueprint $table) {
            if ($this->indexExists('project_review_orders', 'idx_pro_orders_cycle_company_client')) {
                $table->dropIndex('idx_pro_orders_cycle_company_client');
            }

            if ($this->indexExists('project_review_orders', 'idx_pro_orders_cycle_client')) {
                $table->dropIndex('idx_pro_orders_cycle_client');
            }
        });

        Schema::table('analises', function (Blueprint $table) {
            if ($this->indexExists('analises', 'idx_analises_production_id')) {
                $table->dropIndex('idx_analises_production_id');
            }
        });

        Schema::table('notes', function (Blueprint $table) {
            if ($this->indexExists('notes', 'idx_notes_type_nstats_dt_note')) {
                $table->dropIndex('idx_notes_type_nstats_dt_note');
            }
        });

        Schema::table('external_organ_releases', function (Blueprint $table) {
            if ($this->indexExists('external_organ_releases', 'idx_eor_pending_export_created')) {
                $table->dropIndex('idx_eor_pending_export_created');
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
