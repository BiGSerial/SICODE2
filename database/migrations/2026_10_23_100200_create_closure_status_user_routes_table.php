<?php

use App\Support\Database\GuardedTableRecreator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Mapa de status usuário por serviço: quais prefixos de orders.statusUser pertencem à caixa da Medição ou da Fiscalização.
 * Um prefixo pertence a UM serviço (índice único). TABELA NOVA do módulo: se já existir em formato divergente é recriada
 * (o Encerramento ainda não está em uso) — via GuardedTableRecreator, que só descarta tabela `closure_*` da lista fixa e aborta
 * se alguma tabela de fora do módulo tiver FK para ela. Nenhuma tabela de outra atividade é tocada.
 */
return new class () extends Migration {
    private const TABLE = 'closure_status_user_routes';

    private const UNIQUE = 'closure_status_user_route_prefix_unique';

    public function up(): void
    {
        $needsCreate = GuardedTableRecreator::prepare(
            self::TABLE,
            ['id', 'area', 'status_user_prefix', 'created_by', 'created_at', 'updated_at'],
            [self::UNIQUE],
            [self::TABLE],
        );

        if (!$needsCreate) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('area', 20);
            $table->string('status_user_prefix', 60);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('status_user_prefix', self::UNIQUE);
            $table->index('area', 'closure_status_user_route_area_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
