<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_demand_subdemand_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_demand_subdemand_id')->constrained('legal_demand_subdemands')->cascadeOnDelete();
            $table->string('event_type', 60);
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->string('actor_role', 40)->nullable();
            $table->text('reason')->nullable();
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['legal_demand_subdemand_id', 'occurred_at'], 'idx_legal_subdemand_events_subdemand_occurred');
            $table->index(['event_type', 'occurred_at'], 'idx_legal_subdemand_events_type_occurred');
            $table->index(['actor_user_id', 'occurred_at'], 'idx_legal_subdemand_events_actor_occurred');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_demand_subdemand_events');
    }
};
