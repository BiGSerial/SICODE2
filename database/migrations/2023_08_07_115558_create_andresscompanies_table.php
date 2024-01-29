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
        Schema::create('andresscompanies', function (Blueprint $table) {
            $table->id();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('uf')->nullable();
            $table->string('complement')->nullable();
            $table->foreignUuid('company_id')->references('id')->on('companies');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('andresscompanies');
    }
};
