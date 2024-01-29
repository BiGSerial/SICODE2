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
        Schema::table('auxiliar_services', function (Blueprint $table) {
            $table->string('column_search')->nullable();
            $table->string('condition')->nullable();
            $table->boolean('exclusion')->default(false);
            $table->string('value')->nullable();
            $table->string('column_search2')->nullable();
            $table->string('condition2')->nullable();
            $table->boolean('exclusion2')->default(false);
            $table->string('value2')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auxiliar_services', function (Blueprint $table) {
            $table->dropColumn('column_search');
            $table->dropColumn('condition');
            $table->dropColumn('exclusion');
            $table->dropColumn('value');
            $table->dropColumn('column_search2');
            $table->dropColumn('condition2');
            $table->dropColumn('exclusion2');
            $table->dropColumn('value2');
        });
    }
};
