<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('project_review_findings') || !Schema::hasColumn('project_review_findings', 'point_label')) {
            return;
        }

        Schema::table('project_review_findings', function (Blueprint $table) {
            if ($this->hasIndex('project_review_findings', 'project_review_findings_unique_item_origin_action')) {
                $table->dropUnique('project_review_findings_unique_item_origin_action');
            }

            if (!$this->hasIndex('project_review_findings', 'prf_unique_item_origin_action_point')) {
                $table->unique(
                    ['cycle_id', 'subcategory_id', 'item_id', 'origin', 'action_type', 'point_label'],
                    'prf_unique_item_origin_action_point'
                );
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('project_review_findings')) {
            return;
        }

        Schema::table('project_review_findings', function (Blueprint $table) {
            if ($this->hasIndex('project_review_findings', 'prf_unique_item_origin_action_point')) {
                $table->dropUnique('prf_unique_item_origin_action_point');
            }

            if (!$this->hasIndex('project_review_findings', 'project_review_findings_unique_item_origin_action')) {
                $table->unique(
                    ['cycle_id', 'subcategory_id', 'item_id', 'origin', 'action_type'],
                    'project_review_findings_unique_item_origin_action'
                );
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $dbName = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND index_name = ?
             LIMIT 1',
            [$dbName, $table, $indexName]
        );

        return (bool) $row;
    }
};

