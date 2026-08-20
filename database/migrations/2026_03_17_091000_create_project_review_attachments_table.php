<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('project_review_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files');
            $table->foreignId('production_id')->constrained('productions')->cascadeOnDelete();
            $table->foreignId('cycle_id')->nullable()->constrained('project_review_cycles');
            $table->foreignId('finding_id')->nullable()->constrained('project_review_findings');
            $table->foreignId('message_id')->nullable()->constrained('project_review_messages');
            $table->foreignUuid('user_id')->nullable()->constrained('users');
            $table->string('context', 30)->default('cycle'); // cycle|finding|message
            $table->timestamps();

            $table->index(['production_id', 'context']);
            $table->index(['cycle_id', 'created_at']);
            $table->index(['message_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_review_attachments');
    }
};
