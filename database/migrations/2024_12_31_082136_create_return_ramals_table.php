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
        Schema::create('return_ramals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ramal_report_id');
            $table->uuid('service_id');
            $table->uuid('user_id');
            $table->string('category', 255);
            $table->text('text_obs')->nullable();
            $table->timestamps();


            $table->foreign('ramal_report_id')->references('id')->on('ramal_reports')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_ramals');
    }
};
