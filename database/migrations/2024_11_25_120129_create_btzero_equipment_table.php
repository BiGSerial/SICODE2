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
        Schema::create('btzero_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ramal_report_id')->constrained('ramal_reports')->cascadeOnDelete();
            $table->string('type')->nullable();
            $table->boolean('installed')->default(false);
            $table->string('patrimony')->nullable();
            $table->string('fases')->nullable();
            $table->string('pole')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('btzero_equipment');
    }
};
