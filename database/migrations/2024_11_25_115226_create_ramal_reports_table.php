<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ramal_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignUuid('company_id')->nullable();
            $table->foreignUuid('user_id')->nullable();
            $table->date('date')->nullable();
            $table->boolean('equipment')->nullable()->default(false);
            $table->boolean('connection')->nullable()->default(false);
            $table->text('observation')->nullable();
            $table->boolean('retry')->nullable()->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ramal_reports');
    }
};
