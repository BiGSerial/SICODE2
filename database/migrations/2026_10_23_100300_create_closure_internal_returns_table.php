<?php

use App\Support\Database\GuardedTableRecreator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * RI (Retorno Interno) enviado ao Desenho: vínculo entre a NOTA do Encerramento e o Reclaim criado no fluxo existente.
 * O RI da Nota vale para todas as Ordens dela. TABELA NOVA do módulo (recriável se divergente, via GuardedTableRecreator).
 *
 *  - `reclaim_id` é só índice, SEM FK: `reclaims` é compartilhada com Viabilidade/Desenho e não pode ser tocada nem depender de nós.
 *  - `closed_at` é preenchido quando o RI retorna ou é cancelado. `active_note_id` (coluna gerada) só tem valor enquanto
 *    `closed_at IS NULL`; o índice único nela garante UM RI ativo por Nota no próprio banco (o RI só vai ao Desenho, então
 *    "por Nota" equivale a "por Nota e serviço"). Mesmo padrão de `open_state_id` em closure_order_state_periods: o MariaDB
 *    recusa texto (CONCAT, literal ou coluna char) em coluna gerada, por isso só inteiro e IS NULL. NULL não conflita, então
 *    RI encerrados nunca bloqueiam um novo ciclo.
 */
return new class () extends Migration {
    private const TABLE = 'closure_internal_returns';

    private const ACTIVE_UNIQUE = 'closure_internal_return_active_unique';

    public function up(): void
    {
        $needsCreate = GuardedTableRecreator::prepare(
            self::TABLE,
            ['id', 'note_id', 'reclaim_id', 'design_service_id', 'status', 'cycle', 'message', 'sent_by', 'sent_at', 'returned_at',
                'cancelled_by', 'cancelled_at', 'cancel_reason', 'closed_at', 'active_note_id', 'created_at', 'updated_at'],
            [self::ACTIVE_UNIQUE],
            [self::TABLE],
        );

        if (!$needsCreate) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->restrictOnDelete();
            $table->unsignedBigInteger('reclaim_id')->nullable();
            $table->char('design_service_id', 36);
            $table->string('status', 20)->default('SENT');
            $table->unsignedInteger('cycle')->default(1);
            $table->text('message')->nullable();
            $table->foreignUuid('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at');
            $table->timestamp('returned_at')->nullable();
            $table->foreignUuid('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('active_note_id')
                ->nullable()
                ->storedAs('CASE WHEN `closed_at` IS NULL THEN `note_id` ELSE NULL END');
            $table->timestamps();

            $table->unique('active_note_id', self::ACTIVE_UNIQUE);
            $table->index('reclaim_id', 'closure_internal_return_reclaim_index');
            $table->index(['note_id', 'status'], 'closure_internal_return_note_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
