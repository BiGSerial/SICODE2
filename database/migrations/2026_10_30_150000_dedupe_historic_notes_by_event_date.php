<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class () extends Migration {
    /**
     * O mesmo evento de status (nota + status + data/hora) voltava com outro id_num (sequencial da origem) e virava linha
     * duplicada. A chave passa a incluir a data do evento; id_num fica só como informação.
     */
    public function up(): void
    {
        if (!Schema::hasTable('historic_notes')) {
            return;
        }

        $groups = DB::table('historic_notes')
            ->select('note_id', 'num_stat', 'dt_stats')
            ->groupBy('note_id', 'num_stat', 'dt_stats')
            ->havingRaw('COUNT(*) > 1')
            ->cursor();

        foreach ($groups as $group) {
            // Fica a linha com usuário da transição; depois a de maior id_num (a mais recente da origem).
            $ids = DB::table('historic_notes')
                ->where('note_id', $group->note_id)
                ->where('num_stat', $group->num_stat)
                ->where('dt_stats', $group->dt_stats)
                ->orderByRaw('transicao_usuario IS NULL')
                ->orderByDesc('id_num')
                ->orderByDesc('id')
                ->pluck('id')
                ->all();

            DB::table('historic_notes')->whereIn('id', array_slice($ids, 1))->delete();
        }

        // A nova chave entra antes de a antiga sair: o FK de note_id precisa sempre de um índice com ele na frente.
        Schema::table('historic_notes', function (Blueprint $table): void {
            $table->unique(['note_id', 'num_stat', 'dt_stats'], 'historic_notes_event_date_unique');
        });

        Schema::table('historic_notes', function (Blueprint $table): void {
            $table->dropUnique('historic_notes_event_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('historic_notes')) {
            return;
        }

        Schema::table('historic_notes', function (Blueprint $table): void {
            $table->unique(['note_id', 'id_num', 'num_stat'], 'historic_notes_event_unique');
        });

        Schema::table('historic_notes', function (Blueprint $table): void {
            $table->dropUnique('historic_notes_event_date_unique');
        });
    }
};
