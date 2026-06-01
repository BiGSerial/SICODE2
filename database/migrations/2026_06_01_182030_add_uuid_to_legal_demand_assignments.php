<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_demand_assignments', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        // Popula registros existentes
        DB::table('legal_demand_assignments')
            ->whereNull('uuid')
            ->orderBy('id')
            ->each(function (object $row): void {
                DB::table('legal_demand_assignments')
                    ->where('id', $row->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });

        // Torna NOT NULL após popular
        Schema::table('legal_demand_assignments', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('legal_demand_assignments', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
