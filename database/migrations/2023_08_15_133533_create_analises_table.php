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
        Schema::create('analises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->reference('id')->on('productions')->onDelete('cascade');
            $table->string('ninst')->nullable();
            $table->string('nMedidor')->nullable();
            $table->string('patrimonio')->nullable();
            $table->string('lat')->nullable();
            $table->string('lon')->nullable();
            $table->decimal('carga_ini')->nullable();
            $table->decimal('carga_fim')->nullable();
            $table->decimal('queda')->nullable();
            $table->decimal('queda_max')->nullable();
            $table->decimal('queda_cliente')->nullable();
            $table->integer('vao')->nullable();
            $table->string('restricao')->nullable();
            $table->string('motivo')->nullable();
            $table->string('conclusion')->nullable();
            $table->text('info')->nullable();
            $table->text('card')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analises');
    }
};
