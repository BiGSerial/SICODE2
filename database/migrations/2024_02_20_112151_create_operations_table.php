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
        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('operacao')->nullable();
            $table->string('descOperacao')->nullable();
            $table->dateTime('inicioPlanejado')->nullable();
            $table->dateTime('fimPlanejado')->nullable();
            $table->dateTime('inicioReal')->nullable();
            $table->dateTime('fimReal')->nullable();
            $table->string('status')->nullable();
            $table->string('notaOv')->nullable();
            $table->string('cenPlan')->nullable();
            $table->string('cenTrab')->nullable();
            $table->text('txtCenTrab')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operations');
    }
};
