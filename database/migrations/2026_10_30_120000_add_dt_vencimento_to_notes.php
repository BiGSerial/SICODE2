<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('notes', 'dt_vencimento')) {
            Schema::table('notes', function (Blueprint $table): void {
                $table->date('dt_vencimento')->nullable()->after('days_left');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notes', 'dt_vencimento')) {
            Schema::table('notes', function (Blueprint $table): void {
                $table->dropColumn('dt_vencimento');
            });
        }
    }
};
