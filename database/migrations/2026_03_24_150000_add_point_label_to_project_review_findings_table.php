<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_review_findings')) {
            return;
        }

        Schema::table('project_review_findings', function (Blueprint $table) {
            if (!Schema::hasColumn('project_review_findings', 'point_label')) {
                $table->string('point_label', 120)->nullable()->after('cycle_id');
                $table->index(['cycle_id', 'point_label'], 'prf_cycle_point_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('project_review_findings')) {
            return;
        }

        Schema::table('project_review_findings', function (Blueprint $table) {
            if (Schema::hasColumn('project_review_findings', 'point_label')) {
                $table->dropIndex('prf_cycle_point_idx');
                $table->dropColumn('point_label');
            }
        });
    }
};

