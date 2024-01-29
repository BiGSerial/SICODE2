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
        Schema::table('productions', function (Blueprint $table) {
            $table->integer('postes_c')->nullable();
            $table->boolean('eo')->default(false);
            $table->boolean('iproject')->default(false);
            $table->boolean('cadastro')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn('postes_c');
            $table->dropColumn('eo');
            $table->dropColumn('iproject');
            $table->dropColumn('cadastro');
        });
    }
};
