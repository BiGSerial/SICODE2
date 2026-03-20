<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('project_review_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('productions')->cascadeOnDelete();
            $table->foreignId('cycle_id')->constrained('project_review_cycles')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('payload');
            $table->timestamps();

            $table->unique(['production_id', 'cycle_id', 'user_id'], 'project_review_drafts_unique_user_cycle');
            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_review_drafts');
    }
};

