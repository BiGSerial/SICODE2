<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Data de entrada da Ordem no status usuário atual (orders.statusUser), para a caixa "Em acompanhamento" mostrar "no status
 * desde…". MIGRATION ADITIVA: closure_order_states NUNCA é recriada. A tabela `orders` (que guarda o statusUser) não é tocada.
 * A rotina `closure:sync-status-user` preenche; nas Ordens já presentes na primeira execução a data é a do primeiro registro
 * e `status_user_estimated` fica true.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('closure_order_states')) {
            return;
        }

        Schema::table('closure_order_states', function (Blueprint $table) {
            if (!Schema::hasColumn('closure_order_states', 'status_user_seen')) {
                $table->string('status_user_seen', 120)->nullable();
            }

            if (!Schema::hasColumn('closure_order_states', 'status_user_changed_at')) {
                $table->timestamp('status_user_changed_at')->nullable();
            }

            if (!Schema::hasColumn('closure_order_states', 'status_user_estimated')) {
                $table->boolean('status_user_estimated')->default(false);
            }
        });
    }

    /** Remove apenas o que esta migration adicionou (e só se existir). */
    public function down(): void
    {
        if (!Schema::hasTable('closure_order_states')) {
            return;
        }

        foreach (['status_user_estimated', 'status_user_changed_at', 'status_user_seen'] as $column) {
            if (Schema::hasColumn('closure_order_states', $column)) {
                Schema::table('closure_order_states', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
