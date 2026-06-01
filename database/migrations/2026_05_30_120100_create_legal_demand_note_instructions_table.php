<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_demand_note_instructions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->cascadeOnDelete();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->text('instruction');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['legal_demand_id', 'note_id', 'active'], 'idx_legal_instr_demand_note_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_demand_note_instructions');
    }
};

