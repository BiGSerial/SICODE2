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
        Schema::table('med_protests', function (Blueprint $table) {
            $table->string('result', 30)->nullable()->after('needsConfirmation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('med_protests', function (Blueprint $table) {
            $table->dropColumn('result');
        });
    }
};
