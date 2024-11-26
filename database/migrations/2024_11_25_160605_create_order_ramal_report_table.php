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
        Schema::create('order_ramal_report', function (Blueprint $table) {
            $table->id(); // Opcional
            $table->unsignedBigInteger('ramal_report_id');
            $table->unsignedBigInteger('order_id');
            $table->timestamps(); // Opcional

            // Definindo as chaves estrangeiras
            $table->foreign('ramal_report_id')->references('id')->on('ramal_reports')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();

            // Índice único para evitar duplicidade
            $table->unique(['ramal_report_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_ramal_report');
    }
};
