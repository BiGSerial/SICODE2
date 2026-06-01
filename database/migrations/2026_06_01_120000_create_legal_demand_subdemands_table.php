<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_demand_subdemands', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->cascadeOnDelete();
            $table->uuid('assigned_to_user_id')->nullable();
            $table->string('assigned_area_name', 120)->nullable();
            $table->string('status', 40)->default('aberta');
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('resolution')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->string('status_contract_version', 24)->default('v1');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('assigned_to_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['legal_demand_id', 'status'], 'idx_legal_subdemands_demand_status');
            $table->index(['status', 'deadline_at'], 'idx_legal_subdemands_status_deadline');
            $table->index(['assigned_to_user_id', 'status'], 'idx_legal_subdemands_assignee_status');
            $table->index(['assigned_area_name', 'status'], 'idx_legal_subdemands_area_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_demand_subdemands');
    }
};
