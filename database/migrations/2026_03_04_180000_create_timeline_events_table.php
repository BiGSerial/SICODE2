<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeline_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('five_note_id')->constrained('five_notes')->cascadeOnDelete();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();

            $table->string('event_type', 80);
            $table->string('from_stage', 80)->nullable();
            $table->string('to_stage', 80)->nullable();

            $table->foreignUuid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 50)->nullable();

            $table->foreignUuid('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('owner_role', 50)->nullable();

            $table->foreignUuid('service_id')->nullable()->constrained('services', 'uuid')->nullOnDelete();
            $table->foreignId('production_id')->nullable()->constrained('productions')->nullOnDelete();

            $table->timestamp('occurred_at');
            $table->boolean('inferred')->default(false);
            $table->text('reason')->nullable();
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['five_note_id', 'occurred_at'], 'idx_timeline_events_five_occurred');
            $table->index(['event_type', 'occurred_at'], 'idx_timeline_events_type_occurred');
            $table->index(['owner_user_id', 'occurred_at'], 'idx_timeline_events_owner_occurred');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeline_events');
    }
};

