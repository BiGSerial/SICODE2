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
        Schema::create('justifies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viability_id')->constrained()->onDelete('cascade');
            $table->text('justify');
            $table->text('answer')->nullable();
            $table->boolean('grant')->default(false);
            $table->boolean('dismiss')->default(false);
            $table->dateTime('answer_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('justifies');
    }
};
