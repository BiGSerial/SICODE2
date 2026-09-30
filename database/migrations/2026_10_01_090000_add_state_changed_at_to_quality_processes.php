<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('quality_processes', function (Blueprint $table) {
            // Desde quando o processo está no estado atual (base do "parado há X" do N1/N2).
            $table->timestamp('state_changed_at')->nullable()->after('state');
        });

        DB::table('quality_processes')->update(['state_changed_at' => DB::raw('COALESCE(updated_at, dispatched_at)')]);
    }

    public function down(): void
    {
        Schema::table('quality_processes', fn (Blueprint $table) => $table->dropColumn('state_changed_at'));
    }
};
