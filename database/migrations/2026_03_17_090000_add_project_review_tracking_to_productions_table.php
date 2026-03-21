<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            if (!Schema::hasColumn('productions', 'project_review_rejections_count')) {
                $table->unsignedInteger('project_review_rejections_count')->default(0)->after('status');
            }

            if (!Schema::hasColumn('productions', 'project_review_last_cycle_id')) {
                $table->unsignedBigInteger('project_review_last_cycle_id')->nullable()->after('project_review_rejections_count');
                $table->foreign('project_review_last_cycle_id', 'fk_prod_last_project_review_cycle')
                    ->references('id')
                    ->on('project_review_cycles')
                    ->nullOnDelete();
            }

            $table->index(['status', 'completed'], 'idx_productions_status_completed_review');
            $table->index(['user_id', 'status'], 'idx_productions_user_status_review');
        });
    }

    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            if (Schema::hasColumn('productions', 'project_review_last_cycle_id')) {
                $table->dropForeign('fk_prod_last_project_review_cycle');
                $table->dropColumn('project_review_last_cycle_id');
            }

            if (Schema::hasColumn('productions', 'project_review_rejections_count')) {
                $table->dropColumn('project_review_rejections_count');
            }

            $table->dropIndex('idx_productions_status_completed_review');
            $table->dropIndex('idx_productions_user_status_review');
        });
    }
};
