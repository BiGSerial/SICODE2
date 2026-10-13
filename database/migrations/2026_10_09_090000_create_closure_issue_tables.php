<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('closure_issue_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('display_order')->nullable();
            $table->timestamps();
        });

        Schema::create('closure_issue_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('closure_issue_category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('requires_description')->default(false);
            $table->unsignedInteger('display_order')->nullable();
            $table->timestamps();

            $table->unique(['closure_issue_category_id', 'slug'], 'closure_issue_subcategory_category_slug_unique');
        });

        Schema::create('closure_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('closure_order_state_id')->constrained()->restrictOnDelete();
            $table->foreignId('closure_issue_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('closure_issue_subcategory_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('destination_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignUuid('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status');
            $table->unsignedInteger('cycle')->default(1);
            $table->boolean('blocking')->default(true);
            $table->text('description')->nullable();
            $table->json('block_state_snapshot')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['closure_order_state_id', 'status'], 'closure_issue_state_status_index');
            $table->index(['destination_company_id', 'status'], 'closure_issue_destination_status_index');
            $table->index(['closure_issue_category_id', 'closure_issue_subcategory_id'], 'closure_issue_taxonomy_index');
        });

        Schema::create('closure_issue_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('closure_issue_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('origin');
            $table->string('before_status')->nullable();
            $table->string('after_status')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['closure_issue_id', 'created_at'], 'closure_issue_event_issue_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closure_issue_events');
        Schema::dropIfExists('closure_issues');
        Schema::dropIfExists('closure_issue_subcategories');
        Schema::dropIfExists('closure_issue_categories');
    }
};
