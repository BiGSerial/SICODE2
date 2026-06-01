<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_demands', function (Blueprint $table) {
            $table->unsignedInteger('subdemand_open_count')->default(0)->after('risk_level');
            $table->unsignedInteger('subdemand_overdue_count')->default(0)->after('subdemand_open_count');
            $table->unsignedInteger('subdemand_completed_count')->default(0)->after('subdemand_overdue_count');
            $table->unsignedBigInteger('subdemand_avg_resolution_seconds')->nullable()->after('subdemand_completed_count');
            $table->string('subdemand_sla_status', 30)->nullable()->after('subdemand_avg_resolution_seconds');
            $table->string('subdemand_criticality', 30)->nullable()->after('subdemand_sla_status');

            $table->index(['subdemand_criticality', 'subdemand_sla_status'], 'idx_legal_demands_subdemand_criticality');
        });
    }

    public function down(): void
    {
        Schema::table('legal_demands', function (Blueprint $table) {
            $table->dropIndex('idx_legal_demands_subdemand_criticality');
            $table->dropColumn([
                'subdemand_open_count',
                'subdemand_overdue_count',
                'subdemand_completed_count',
                'subdemand_avg_resolution_seconds',
                'subdemand_sla_status',
                'subdemand_criticality',
            ]);
        });
    }
};

