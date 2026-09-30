<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('quality_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->unique()->constrained('productions')->cascadeOnDelete();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignUuid('company_id')->constrained('companies');
            $table->foreignUuid('original_designer_id')->constrained('users');
            $table->string('status', 24)->default('ACTIVE');
            $table->string('current_stage', 24)->nullable();
            $table->foreignUuid('dispatched_by')->constrained('users');
            $table->timestamp('dispatched_at');
            $table->foreignUuid('completed_by')->nullable()->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status', 'current_stage']);
        });

        Schema::create('quality_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_process_id')->constrained('quality_processes')->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('level', 16);
            $table->string('status', 24)->default('PENDING');
            $table->unsignedInteger('round_number')->default(1);
            $table->foreignUuid('assigned_user_id')->nullable()->constrained('users');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('observation')->nullable();
            $table->json('submission_data')->nullable();
            $table->timestamps();
            $table->unique(['quality_process_id', 'type', 'level', 'round_number'], 'quality_stage_round_level_unique');
            $table->index(['type', 'level', 'status']);
        });

        Schema::create('quality_rejection_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('quality_rejection_categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code')->nullable()->unique();
            $table->boolean('active')->default(true);
            $table->boolean('applies_project')->default(true);
            $table->boolean('applies_budget')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['parent_id', 'name']);
        });

        Schema::create('quality_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quality_rejections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_process_id')->constrained('quality_processes')->cascadeOnDelete();
            $table->foreignId('quality_stage_id')->constrained('quality_stages')->cascadeOnDelete();
            $table->foreignId('production_id')->constrained('productions')->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('level', 16);
            $table->unsignedInteger('round_number');
            $table->foreignUuid('author_id')->constrained('users');
            $table->foreignUuid('designer_id')->constrained('users');
            $table->foreignUuid('company_id')->constrained('companies');
            $table->text('observation')->nullable();
            $table->timestamps();
            $table->index(['quality_process_id', 'type', 'round_number']);
        });

        Schema::create('quality_rejection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_rejection_id')->constrained('quality_rejections')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('quality_rejection_categories');
            $table->foreignId('subcategory_id')->nullable()->constrained('quality_rejection_categories')->nullOnDelete();
            $table->text('observation')->nullable();
            $table->timestamps();
        });

        Schema::create('quality_stage_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_stage_id')->constrained('quality_stages')->cascadeOnDelete();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->string('checksum', 128);
            $table->foreignUuid('submitted_by')->constrained('users');
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['quality_stage_id', 'file_id']);
            $table->index(['file_id', 'checksum']);
        });

        Schema::create('quality_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_process_id')->constrained('quality_processes')->cascadeOnDelete();
            $table->foreignId('quality_stage_id')->nullable()->constrained('quality_stages')->nullOnDelete();
            $table->foreignId('production_id')->constrained('productions')->cascadeOnDelete();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignUuid('designer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32);
            $table->string('stage_type', 16)->nullable();
            $table->string('stage_level', 16)->nullable();
            $table->unsignedInteger('round_number')->nullable();
            $table->text('observation')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['quality_process_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_events');
        Schema::dropIfExists('quality_stage_files');
        Schema::dropIfExists('quality_rejection_items');
        Schema::dropIfExists('quality_rejections');
        Schema::dropIfExists('quality_rejection_categories');
        Schema::dropIfExists('quality_stages');
        Schema::dropIfExists('quality_processes');
    }
};
