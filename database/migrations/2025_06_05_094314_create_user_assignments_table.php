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
        Schema::create('user_assignments', function (Blueprint $table) {
            $table->id();
            $table->morphs('assignable');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('service_id')->nullable()->constrained('services', 'uuid')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->boolean('completed')->default(false);
            $table->boolean('responsible')->default(false);
            $table->boolean('user')->default(false);
            $table->boolean('monitoring')->default(false);
            $table->boolean('transfered')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_assignments');
    }
};
