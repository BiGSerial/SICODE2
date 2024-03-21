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
        Schema::table('analises', function (Blueprint $table) {
            $table->string('alimentador')->nullable();
            $table->string('comprador')->nullable();
            $table->string('matricula')->nullable();
            $table->decimal('area')->nullable();
            $table->string('documento')->nullable();
            $table->string('endereco')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analises', function (Blueprint $table) {
            $table->dropColumn('alimentador', 'comprador', 'matricula', 'area', 'endereco');
        });
    }
};
