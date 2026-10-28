<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Status do evento que encerra a fase (o "acima imediato"). Permite à tela expurgar fases por esse status (ex.: 99) na consulta,
     * em vez de decidir na hora de montar a tabela. Preenchido por sicode:rebuild_ov_phases.
     */
    public function up(): void
    {
        Schema::table('ov_phase_durations', function (Blueprint $table) {
            $table->unsignedSmallInteger('next_stat')->nullable()->after('days');
        });
    }

    public function down(): void
    {
        Schema::table('ov_phase_durations', function (Blueprint $table) {
            $table->dropColumn('next_stat');
        });
    }
};
