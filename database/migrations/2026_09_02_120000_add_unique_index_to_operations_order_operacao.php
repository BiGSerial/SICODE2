<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private string $indexName = 'operations_order_id_operacao_unique';

    public function up(): void
    {
        DB::statement("
            DELETE older
            FROM operations older
            INNER JOIN operations newer
                ON newer.order_id = older.order_id
                AND newer.operacao <=> older.operacao
                AND newer.id > older.id
            WHERE older.operacao IS NOT NULL
        ");

        Schema::table('operations', function (Blueprint $table) {
            $table->unique(['order_id', 'operacao'], $this->indexName);
        });
    }

    public function down(): void
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->dropUnique($this->indexName);
        });
    }
};
