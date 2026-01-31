<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cancellation_request_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancellation_request_id')->constrained('cancellation_requests')->cascadeOnDelete();
            $table->foreignUuid('actor_id')->nullable()->constrained('users');
            $table->string('type');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['cancellation_request_id', 'created_at'], 'cxl_req_events_req_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_request_events');
    }
};
