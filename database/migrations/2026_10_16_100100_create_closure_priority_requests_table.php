<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('closure_priority_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->restrictOnDelete();
            $table->foreignUuid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->string('status', 20)->default('PENDING');
            $table->foreignUuid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->index(['note_id', 'status'], 'closure_priority_request_note_status_index');
            $table->index(['status', 'created_at'], 'closure_priority_request_status_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closure_priority_requests');
    }
};
