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
        Schema::create('address_cads', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('note_id')->nullable();
            $table->bigInteger('production_id')->nullable();
            $table->bigInteger('analise_id')->nullable();
            $table->string('address')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('cod')->nullable();
            $table->boolean('exist')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('address_cads');
    }
};
