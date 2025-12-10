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
        if (Schema::hasColumn('protests', 'type_note')) {
            Schema::table('protests', function (Blueprint $table) {
            $table->dropColumn('type_note');
            });
        }


        Schema::table('protests', function (Blueprint $table) {
            $table->string('type')->nullable()->after('resume');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('protests', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
