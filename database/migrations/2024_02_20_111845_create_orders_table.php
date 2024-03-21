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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->onDelete('cascade');
            $table->string('ordem');
            $table->string('descricao')->nullable();
            $table->string('locInstalacao')->nullable();
            $table->string('cenPlan')->nullable();
            $table->string('prioridade')->nullable();
            $table->string('statusSist')->nullable();
            $table->string('statusUser')->nullable();
            $table->string('cenTrab')->nullable();
            $table->string('gpm')->nullable();
            $table->decimal('custPlanejado', 15, 2)->nullable();
            $table->decimal('custRealizado', 15, 2)->nullable();
            $table->string('modifPor')->nullable();
            $table->string('pep')->nullable();
            $table->string('conjunto')->nullable();
            $table->string('denConjunto')->nullable();
            $table->timestamp('dtEntrada')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
