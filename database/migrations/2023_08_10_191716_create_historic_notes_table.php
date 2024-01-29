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
        Schema::create('historic_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->references('id')->on('notes')->onDelete('cascade');
            $table->timestamp('old_date')->nullable();
            $table->integer('old_stat')->nullable();
            $table->timestamp('new_date')->nullable();
            $table->integer('new_stat')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historic_notes');
    }
};
