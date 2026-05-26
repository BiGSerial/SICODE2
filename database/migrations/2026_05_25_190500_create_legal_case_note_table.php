<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_case_note', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->uuid('linked_by')->nullable();
            $table->foreign('linked_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('linked_at')->nullable();
            $table->text('context')->nullable();
            $table->timestamps();

            $table->unique(['legal_case_id', 'note_id'], 'uq_legal_case_note_case_note');
            $table->index('linked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_case_note');
    }
};
