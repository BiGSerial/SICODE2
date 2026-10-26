<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            $table->timestamp('ads_due_at')->nullable()->after('informed_at');
            $table->index(['ads_due_at', 'canceled', 'rejected'], 'work_reports_ads_due_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            $table->dropIndex('work_reports_ads_due_at_index');
            $table->dropColumn('ads_due_at');
        });
    }
};
