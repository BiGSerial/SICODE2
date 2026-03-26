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
        if (!Schema::hasColumn('med_protests', 'statMedida')) {
            Schema::table('med_protests', function (Blueprint $table) {
                $table->string('statMedida', 50)->nullable()->after('statusSist');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('med_protests', 'statMedida')) {
            Schema::table('med_protests', function (Blueprint $table) {
                $table->dropColumn('statMedida');
            });
        }
    }
};
