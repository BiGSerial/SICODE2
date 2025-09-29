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
        Schema::table('external_reclaim', function (Blueprint $table) {
            $table->boolean('completed')->default(false)->after('reclaim_id');
            $table->timestamp('completed_at')->nullable()->after('completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_reclaim', function (Blueprint $table) {
            $table->dropColumn('completed');
            $table->dropColumn('completed_at');
        });
    }
};
