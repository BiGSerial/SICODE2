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
        if (Schema::hasColumn('externals', 'status')) {
            Schema::table('externals', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        Schema::table('externals', function (Blueprint $table) {
            $table->string('status')->nullable()->after('entidade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('externals', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
