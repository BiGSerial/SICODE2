<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wall_screens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('wall_screen_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wall_screen_id')->constrained('wall_screens')->cascadeOnDelete();
            $table->uuid('service_id');
            $table->uuid('previous_service_id')->nullable();
            $table->boolean('enabled')->default(true);
            $table->boolean('use_rule_builder')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['wall_screen_id', 'enabled', 'display_order']);
            $table->index(['service_id']);
            $table->index(['previous_service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wall_screen_services');
        Schema::dropIfExists('wall_screens');
    }
};
