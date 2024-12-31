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
        Schema::table('ramal_reports', function (Blueprint $table) {
            $table->boolean('rejected')->default(false);
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('informed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ramal_reports', function (Blueprint $table) {
            $table->dropColumn('rejected');
            $table->dropColumn('rejected_at');
            $table->dropColumn('informed_at');
        });
    }
};
