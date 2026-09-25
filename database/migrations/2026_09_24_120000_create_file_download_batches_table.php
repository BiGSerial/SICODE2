<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('file_download_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('queued');
            $table->json('file_ids');
            $table->string('output_pattern')->nullable();
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->string('download_name')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_download_batches');
    }
};
