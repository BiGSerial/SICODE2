<?php

use App\Support\Database\GuardedTableRecreator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Transferência de atividade entre usuários do Encerramento, SEM aprovação da gestão: quem envia escolhe o destinatário,
 * o destinatário aceita ou rejeita e quem enviou pode cancelar enquanto estiver pendente.
 * TABELAS NOVAS do módulo (closure_transfer_requests e closure_transfer_request_items): se já existirem em formato divergente são
 * recriadas via GuardedTableRecreator (só tabela `closure_*` da lista fixa, e só sem FK de tabela de fora do módulo).
 * Nenhuma tabela de outra atividade é tocada.
 */
return new class () extends Migration {
    private const REQUESTS = 'closure_transfer_requests';

    private const ITEMS = 'closure_transfer_request_items';

    public function up(): void
    {
        $owned = [self::REQUESTS, self::ITEMS];

        $needsRequests = GuardedTableRecreator::prepare(
            self::REQUESTS,
            ['id', 'from_user_id', 'to_user_id', 'status', 'reason', 'decision_note', 'decided_at', 'created_at', 'updated_at'],
            ['closure_transfer_requests_to_status_index'],
            $owned,
        );
        $needsItems = GuardedTableRecreator::prepare(
            self::ITEMS,
            ['id', 'closure_transfer_request_id', 'closure_order_state_id', 'result', 'result_reason', 'created_at', 'updated_at'],
            ['closure_transfer_items_unique'],
            $owned,
        );

        if ($needsRequests) {
            Schema::create(self::REQUESTS, function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('from_user_id')->constrained('users');
                $table->foreignUuid('to_user_id')->constrained('users');
                $table->string('status', 20)->default('PENDING');
                $table->text('reason');
                $table->text('decision_note')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();

                $table->index(['to_user_id', 'status'], 'closure_transfer_requests_to_status_index');
                $table->index(['from_user_id', 'status'], 'closure_transfer_requests_from_status_index');
            });
        }

        if ($needsItems) {
            Schema::create(self::ITEMS, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('closure_transfer_request_id');
                $table->foreign('closure_transfer_request_id', 'closure_transfer_items_request_fk')->references('id')->on(self::REQUESTS)->cascadeOnDelete();
                $table->unsignedBigInteger('closure_order_state_id');
                $table->foreign('closure_order_state_id', 'closure_transfer_items_state_fk')->references('id')->on('closure_order_states');
                $table->string('result', 20)->nullable();
                $table->string('result_reason')->nullable();
                $table->timestamps();

                $table->unique(['closure_transfer_request_id', 'closure_order_state_id'], 'closure_transfer_items_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(self::ITEMS);
        Schema::dropIfExists(self::REQUESTS);
    }
};
