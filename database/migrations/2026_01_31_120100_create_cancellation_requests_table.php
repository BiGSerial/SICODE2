<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cancellation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->string('scope');
            $table->foreignId('category_id')->constrained('cancellation_categories');
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
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_requests');
    }
};
