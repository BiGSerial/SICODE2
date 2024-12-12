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
        Schema::create('operation_resps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete()->nullable();
            $table->foreignId('note_id')->nullable();
            $table->string('operacao', 20)->nullable();
            $table->string('confFinal', 10)->nullable();
            $table->date('fimReal')->nullable();
            $table->date('fimLancado')->nullable();
            $table->string('cenTrab', 50)->nullable();
            $table->string('txtCenTrab', 100)->nullable();
            $table->string('matriculaResp', 20)->nullable();
            $table->string('nomeResp', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_resps');
    }
};
