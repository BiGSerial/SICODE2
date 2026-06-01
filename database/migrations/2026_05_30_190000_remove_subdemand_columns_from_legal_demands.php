<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_demands', function (Blueprint $table) {
            if (Schema::hasColumn('legal_demands', 'parent_demand_id')) {
                $table->dropConstrainedForeignId('parent_demand_id');
            }

            if (Schema::hasColumn('legal_demands', 'is_manual_sub_demand')) {
                $table->dropColumn('is_manual_sub_demand');
            }
        });
    }

    public function down(): void
    {
        Schema::table('legal_demands', function (Blueprint $table) {
            if (!Schema::hasColumn('legal_demands', 'parent_demand_id')) {
                $table->foreignId('parent_demand_id')
                    ->nullable()
                    ->after('legal_case_id')
                    ->constrained('legal_demands')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('legal_demands', 'is_manual_sub_demand')) {
                $table->boolean('is_manual_sub_demand')
                    ->default(false)
                    ->after('parent_demand_id');
                $table->index('is_manual_sub_demand', 'idx_legal_demands_manual_sub');
            }
        });
    }
};

