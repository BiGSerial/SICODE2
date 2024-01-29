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
        Schema::create('prodtransfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->reference('id')->on('productions')->onDelete('cascade');
            $table->foreignUuid('service_id')->reference('uuid')->on('services')->onDelete('cascade');
            $table->uuid('from');
            $table->uuid('to');
            $table->string('info')->nullable();
            $table->integer('status')->default(1);
            $table->boolean('read_to')->default(false);
            $table->boolean('read_from')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prodtransfers');
    }
};
