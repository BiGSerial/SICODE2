<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_demand_comments', function (Blueprint $table) {
            if (!Schema::hasColumn('legal_demand_comments', 'legal_demand_subdemand_id')) {
                $table->foreignId('legal_demand_subdemand_id')
                    ->nullable()
                    ->after('assignment_id')
                    ->constrained('legal_demand_subdemands');
                $table->index(['legal_demand_subdemand_id', 'created_at'], 'idx_legal_comments_subdemand_created');
            }
        });

        Schema::table('legal_demand_files', function (Blueprint $table) {
            if (!Schema::hasColumn('legal_demand_files', 'legal_demand_subdemand_id')) {
                $table->foreignId('legal_demand_subdemand_id')
                    ->nullable()
                    ->after('assignment_id')
                    ->constrained('legal_demand_subdemands');
                $table->index(['legal_demand_subdemand_id', 'created_at'], 'idx_legal_files_subdemand_created');
            }
        });
    }

    public function down(): void
    {
        Schema::table('legal_demand_files', function (Blueprint $table) {
            if (Schema::hasColumn('legal_demand_files', 'legal_demand_subdemand_id')) {
                $table->dropForeign(['legal_demand_subdemand_id']);
                $table->dropIndex('idx_legal_files_subdemand_created');
                $table->dropColumn('legal_demand_subdemand_id');
            }
        });

        Schema::table('legal_demand_comments', function (Blueprint $table) {
            if (Schema::hasColumn('legal_demand_comments', 'legal_demand_subdemand_id')) {
                $table->dropForeign(['legal_demand_subdemand_id']);
                $table->dropIndex('idx_legal_comments_subdemand_created');
                $table->dropColumn('legal_demand_subdemand_id');
            }
        });
    }
};
