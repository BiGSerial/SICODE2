<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('protest_jobs', function (Blueprint $table) {
            $table->boolean('confirmed')->nullable()->default(false)->after('is_advance');
            $table->dateTime('confirmed_at')->nullable()->after('confirmed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('protest_jobs', function (Blueprint $table) {
            $table->dropColumn('confirmed');
            $table->dropColumn('confirmed_at');
        });
    }
};
