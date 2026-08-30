<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('work_reports', 'selected_final_scopes')) {
                $table->json('selected_final_scopes')->nullable()->after('acceptance_meta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            if (Schema::hasColumn('work_reports', 'selected_final_scopes')) {
                $table->dropColumn('selected_final_scopes');
            }
        });
    }
};
