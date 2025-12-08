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
        Schema::table('five_notes', function (Blueprint $table) {
            $table->boolean('isPassive')->default(false)->after('is_archived');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('five_notes', function (Blueprint $table) {
            $table->dropColumn('isPassive');
        });
    }
};
