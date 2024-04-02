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
        Schema::create('reclaims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->onDelete('cascade');
            $table->uuid('service_id')->nullable();
            $table->unsignedBigInteger('production_id')->nullable();
            $table->boolean('completed')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('viabilities', function (Blueprint $table) {
            $table->unsignedBigInteger('reclaims_id')->nullable();

        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclaims');

        Schema::table('viabilities', function (Blueprint $table) {
            $table->dropColumn('reclaims_id');

        });

    }
};
