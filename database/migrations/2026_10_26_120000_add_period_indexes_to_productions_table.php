<?php

use App\Support\Database\GuardedTableRecreator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Índices de período em productions (~400 mil linhas), usados pelo "Meu painel" e pelo "Painel da equipe": sem eles cada
 * consulta por `completed_at`/`att_at` varre a tabela inteira (7 a 10 s cada; medido 11,5 s → 0,00 s com o índice).
 * MIGRATION ADITIVA: só ADD de índice, cada um com hasIndex; nenhuma coluna ou dado é tocado.
 */
return new class () extends Migration {
    private const COMPLETED = 'idx_prod_completed_completed_at';

    private const ATT = 'idx_prod_att_at';

    public function up(): void
    {
        if (!GuardedTableRecreator::hasIndex('productions', self::COMPLETED)) {
            Schema::table('productions', fn (Blueprint $table) => $table->index(['completed', 'completed_at'], self::COMPLETED));
        }

        if (!GuardedTableRecreator::hasIndex('productions', self::ATT)) {
            Schema::table('productions', fn (Blueprint $table) => $table->index('att_at', self::ATT));
        }
    }

    public function down(): void
    {
        foreach ([self::COMPLETED, self::ATT] as $index) {
            if (GuardedTableRecreator::hasIndex('productions', $index)) {
                Schema::table('productions', fn (Blueprint $table) => $table->dropIndex($index));
            }
        }
    }
};
