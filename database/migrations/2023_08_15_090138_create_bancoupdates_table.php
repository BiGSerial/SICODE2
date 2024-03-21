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
        Schema::create('bancoupdates', function (Blueprint $table) {
            $table->id();
            $table->timestamp('last_update')->nullable();
            $table->integer('error')->nullable();
            $table->bigInteger('inserts')->nullable();
            $table->bigInteger('updates')->nullable();
            $table->text('info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bancoupdates');
    }
};
