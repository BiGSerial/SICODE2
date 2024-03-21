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
        Schema::create('notetimelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->reference('id')->on('notes')->onDelete('cascade');
            $table->uuid('service_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('info')->nullable();
            $table->integer('status')->default(1);
            $table->boolean('system')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notetimelines');
    }
};
