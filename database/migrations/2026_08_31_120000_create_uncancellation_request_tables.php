<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('uncancellation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->string('scope');
            $table->foreignUuid('requested_by')->constrained('users');
            $table->text('description')->nullable();
            $table->string('status')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users');
            $table->timestamp('assigned_at')->nullable();
            $table->foreignUuid('closed_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->string('closure_type')->nullable();
            $table->text('closure_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('note_id');
            $table->index('requested_by');
            $table->index('assigned_to');
        });

        Schema::create('uncancellation_request_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uncancellation_request_id')->constrained('uncancellation_requests')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders');
            $table->timestamps();

            $table->unique(['uncancellation_request_id', 'order_id'], 'uncxl_req_order_unique');
        });

        Schema::create('uncancellation_request_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uncancellation_request_id')->constrained('uncancellation_requests')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users');
            $table->string('event');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['uncancellation_request_id', 'created_at'], 'uncxl_req_events_request_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uncancellation_request_events');
        Schema::dropIfExists('uncancellation_request_orders');
        Schema::dropIfExists('uncancellation_requests');
    }
};
