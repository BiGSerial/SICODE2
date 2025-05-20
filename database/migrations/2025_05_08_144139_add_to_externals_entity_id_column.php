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
        Schema::table('externals', function (Blueprint $table) {
            if (!Schema::hasColumn('externals', 'entity_id')) {
                $table->foreignId('entity_id')->after('id')->nullable()->constrained('entities');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('externals', function (Blueprint $table) {
            if (Schema::hasColumn('externals', 'entity_id')) {
                $table->dropForeign(['entity_id']);
                $table->dropColumn('entity_id');
            }
        });
    }
};
