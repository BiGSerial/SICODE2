<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('work_reports', 'current_status_key')) {
                $table->string('current_status_key', 80)->nullable()->after('selected_final_scopes');
            }

            if (!Schema::hasColumn('work_reports', 'current_status_label')) {
                $table->string('current_status_label', 120)->nullable()->after('current_status_key');
            }

            if (!Schema::hasColumn('work_reports', 'current_status_class')) {
                $table->string('current_status_class', 80)->nullable()->after('current_status_label');
            }

            if (!Schema::hasColumn('work_reports', 'current_status_updated_at')) {
                $table->timestamp('current_status_updated_at')->nullable()->after('current_status_class');
            }

            $table->index('current_status_key', 'work_reports_current_status_key_idx');
        });
    }

    public function down(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            $table->dropIndex('work_reports_current_status_key_idx');
            $table->dropColumn([
                'current_status_key',
                'current_status_label',
                'current_status_class',
                'current_status_updated_at',
            ]);
        });
    }
};
