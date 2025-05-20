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
        Schema::table('reclaims', function (Blueprint $table) {
            if (!Schema::hasColumn('reclaims', 'subcategory_id')) {
                $table->foreignId('subcategory_id')
                    ->nullable()
                    ->constrained('subcategories')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reclaims', function (Blueprint $table) {
            if (Schema::hasColumn('reclaims', 'subcategory_id')) {
            if (Schema::hasColumn('reclaims', 'subcategory_id') && Schema::hasForeignKey('reclaims', 'reclaims_subcategory_id_foreign')) {
                $table->dropForeign(['subcategory_id']);
            }
            $table->dropColumn('subcategory_id');
            }
        });
    }
};
