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
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viability_id')->constrained('viabilities')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users');
            $table->string('reason')->nullable();
            $table->text('description')->nullable();
            $table->integer('changes')->nullable();
            $table->string('responsible')->nullable();
            $table->boolean('rejected')->default(0);
            $table->boolean('approved')->default(0);
            $table->json('historic')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
