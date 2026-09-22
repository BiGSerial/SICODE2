<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('user_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('region', 255);
            $table->timestamps();

            $table->unique(['user_id', 'region'], 'user_regions_user_region_unique');
            $table->index('region', 'user_regions_region_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_regions');
    }
};
