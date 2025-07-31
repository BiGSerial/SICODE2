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
        Schema::table('protests', function (Blueprint $table) {
            $table->string('codecodf')->nullable()->after('tipoNota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('protests', function (Blueprint $table) {
            $table->dropColumn('codecodf');
        });
    }
};
