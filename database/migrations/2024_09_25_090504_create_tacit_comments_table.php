<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tacit_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viability_id')->constrained('viabilities')->cascadeOnDelete();
            $table->uuid('user_id')->nullable();
            $table->uuid('responsible_id')->nullable();
            $table->text('justification')->nullable();
            $table->text('response')->nullable();
            $table->dateTime('justified_at')->nullable();
            $table->dateTime('answered_at')->nullable();
            $table->boolean('granted')->default(false);
            $table->boolean('dismissed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tacit_comments');
    }
};
