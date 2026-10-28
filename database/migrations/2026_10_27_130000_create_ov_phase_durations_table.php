<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Fases de projeto da OV derivadas de historic_notes: 27 (levantamento) e 28 (desenho).
     * Uma linha por nota e fase (a última passagem), só quando a fase já terminou.
     */
    public function up(): void
    {
        Schema::create('ov_phase_durations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->unsignedSmallInteger('phase');
            $table->dateTime('started_at');
            $table->dateTime('ended_at');
            $table->decimal('days', 8, 2);
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->unsignedBigInteger('production_id')->nullable();
            $table->timestamps();

            $table->unique(['note_id', 'phase']);
            $table->index(['phase', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ov_phase_durations');
    }
};
