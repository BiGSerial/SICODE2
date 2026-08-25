<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_demand_note_activity_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->cascadeOnDelete();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->string('activity_type', 64);
            $table->unsignedBigInteger('activity_id');
            $table->uuid('linked_by')->nullable();
            $table->foreign('linked_by')->references('id')->on('users');
            $table->timestamp('linked_at')->nullable();
            $table->timestamp('unlinked_at')->nullable();
            $table->text('unlink_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['legal_demand_id', 'note_id', 'activity_type'], 'idx_legal_link_demand_note_type');
            $table->index(['activity_type', 'activity_id'], 'idx_legal_link_activity');
            $table->index(['note_id', 'unlinked_at'], 'idx_legal_link_note_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_demand_note_activity_links');
    }
};

