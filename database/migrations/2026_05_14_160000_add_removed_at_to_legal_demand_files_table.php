<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('legal_demand_files', 'removed_at')) {
            Schema::table('legal_demand_files', function (Blueprint $table) {
                $table->timestamp('removed_at')->nullable();
                $table->index('removed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('legal_demand_files', 'removed_at')) {
            Schema::table('legal_demand_files', function (Blueprint $table) {
                $table->dropIndex(['removed_at']);
                $table->dropColumn('removed_at');
            });
        }
    }
};
