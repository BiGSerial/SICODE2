<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('application_api_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_api_token_id')->nullable()->constrained('application_api_tokens')->nullOnDelete();
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('endpoint', 255);
            $table->string('method', 10);
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('payload_hash', 64)->nullable();
            $table->unsignedInteger('records_received')->default(0);
            $table->unsignedInteger('records_created')->default(0);
            $table->unsignedInteger('records_updated')->default(0);
            $table->unsignedInteger('operations_created')->default(0);
            $table->unsignedInteger('operations_updated')->default(0);
            $table->text('error_message')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();

            $table->index(['application_api_token_id', 'created_at'], 'application_api_audits_token_created_index');
            $table->index(['http_status', 'created_at'], 'application_api_audits_status_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_api_audits');
    }
};
