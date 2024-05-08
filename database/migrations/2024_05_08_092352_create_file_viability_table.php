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
        Schema::create('file_viability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('viability_id');
            $table->unsignedBigInteger('file_id');
            $table->timestamps();

            $table->foreign('viability_id')->references('id')->on('viabilities')->onDelete('cascade');
            $table->foreign('file_id')->references('id')->on('files')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_viability');
    }
};
