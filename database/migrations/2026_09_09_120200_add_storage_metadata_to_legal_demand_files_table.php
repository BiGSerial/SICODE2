<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('legal_demand_files', function (Blueprint $table) {
            if (!Schema::hasColumn('legal_demand_files', 'disk')) {
                $table->string('disk', 50)->nullable()->after('path');
            }

            if (!Schema::hasColumn('legal_demand_files', 'sha256')) {
                $table->string('sha256', 64)->nullable()->after('size');
            }
        });
    }

    public function down(): void
    {
        Schema::table('legal_demand_files', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('legal_demand_files', 'disk')) {
                $columns[] = 'disk';
            }

            if (Schema::hasColumn('legal_demand_files', 'sha256')) {
                $columns[] = 'sha256';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
