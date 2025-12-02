<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('med_protests', function (Blueprint $table) {
            $table->integer('protest_type')->nullable()->default(1);
        });

        \Illuminate\Support\Facades\DB::table('med_protests')
            ->whereNull('protest_type')
            ->update(['protest_type' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('med_protests', function (Blueprint $table) {
            $table->dropColumn('protest_type');
        });
    }
};
