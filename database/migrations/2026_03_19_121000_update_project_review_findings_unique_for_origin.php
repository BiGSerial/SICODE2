<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Em alguns ambientes, o FK de cycle_id reaproveita o índice unique antigo.
        // Criamos índice dedicado para cycle_id antes de trocar o unique.
        if (!$this->hasIndex('project_review_findings', 'project_review_findings_cycle_id_idx')) {
            Schema::table('project_review_findings', function (Blueprint $table) {
                $table->index('cycle_id', 'project_review_findings_cycle_id_idx');
            });
        }

        Schema::table('project_review_findings', function (Blueprint $table) {
            if ($this->hasIndex('project_review_findings', 'project_review_findings_unique_item')) {
                $table->dropUnique('project_review_findings_unique_item');
            }

            if (!$this->hasIndex('project_review_findings', 'project_review_findings_unique_item_origin')) {
                $table->unique(
                    ['cycle_id', 'subcategory_id', 'item_id', 'origin'],
                    'project_review_findings_unique_item_origin'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_review_findings', function (Blueprint $table) {
            if ($this->hasIndex('project_review_findings', 'project_review_findings_unique_item_origin')) {
                $table->dropUnique('project_review_findings_unique_item_origin');
            }
            if (!$this->hasIndex('project_review_findings', 'project_review_findings_unique_item')) {
                $table->unique(['cycle_id', 'subcategory_id', 'item_id'], 'project_review_findings_unique_item');
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
