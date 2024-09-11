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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('rdMunicipio', 100)->nullable();
            $table->string('gpm', 100)->nullable();
            $table->string('cidade', 255)->nullable();
            $table->string('municipio', 255)->nullable();
            $table->string('respExpansao', 255)->nullable();
            $table->string('respPreventiva', 255)->nullable();
            $table->string('cenCusto', 255)->nullable();
            $table->string('baseConstrucao', 255)->nullable();
            $table->string('centrlizador', 255)->nullable();
            $table->string('centro', 255)->nullable();
            $table->string('regiao', 255)->nullable();
            $table->string('regional', 255)->nullable();
            $table->string('codIbge', 45)->nullable();
            $table->string('centroHana', 255)->nullable();

            $table->timestamps(); // created_at e updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
