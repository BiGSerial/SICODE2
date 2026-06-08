<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_demand_comments', function (Blueprint $table) {
            if (!Schema::hasColumn('legal_demand_comments', 'deleted_at')) {
                $table->softDeletes();
                $table->index(['legal_demand_id', 'deleted_at'], 'idx_legal_comments_demand_deleted');
            }
        });
    }

    public function down(): void
    {
        Schema::table('legal_demand_comments', function (Blueprint $table) {
            if (Schema::hasColumn('legal_demand_comments', 'deleted_at')) {
                $table->dropIndex('idx_legal_comments_demand_deleted');
                $table->dropSoftDeletes();
            }
        });
    }
};
