<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('application_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('created_by_user_id')->constrained('users');
            $table->string('name', 120);
            $table->string('token_prefix', 24)->unique();
            $table->string('token_hash', 64)->unique();
            $table->boolean('active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->ipAddress('last_used_ip')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['active', 'token_hash'], 'application_api_tokens_active_hash_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_api_tokens');
    }
};
