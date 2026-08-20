<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('external_organ_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignId('production_id')->nullable()->constrained('productions');
            $table->unsignedSmallInteger('detected_nstats')->nullable();
            $table->dateTime('detected_dt_status')->nullable();
            $table->dateTime('exported_at')->nullable();
            $table->foreignUuid('exported_by')->nullable()->constrained('users');
            $table->dateTime('released_at')->nullable();
            $table->dateTime('release_dt_status')->nullable();
            $table->dateTime('release_detected_at')->nullable();
            $table->unsignedSmallInteger('release_nstats')->nullable();
            $table->foreignUuid('released_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['note_id', 'production_id'], 'external_organ_releases_note_production_unique');
            $table->index(['released_at', 'exported_at']);
            $table->index(['detected_nstats', 'release_nstats']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_organ_releases');
    }
};
