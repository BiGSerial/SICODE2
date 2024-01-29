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
        Schema::create('priorities', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('production_id')->nullable();
            $table->bigInteger('note_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('service_id')->nullable();
            $table->text('prioridade')->nullable();
            $table->boolean('global')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('priorities');
    }
};
