<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Nette\Utils\Strings;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daysviabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viability_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->nullable();
            $table->integer('days')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daysviabs');
    }
};
