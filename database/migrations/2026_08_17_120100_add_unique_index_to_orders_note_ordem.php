<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if ($this->hasDuplicateOrders()) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!$this->indexExists('orders', 'uniq_note_ordem')) {
                $table->unique(['note_id', 'ordem'], 'uniq_note_ordem');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if ($this->indexExists('orders', 'uniq_note_ordem')) {
                $table->dropUnique('uniq_note_ordem');
            }
        });
    }

    private function hasDuplicateOrders(): bool
    {
        return DB::table('orders')
            ->select('note_id', 'ordem')
            ->groupBy('note_id', 'ordem')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlsrv') {
            return (bool) DB::selectOne(
                'SELECT 1
                 FROM sys.indexes AS idx
                 INNER JOIN sys.tables AS tbl ON idx.object_id = tbl.object_id
                 INNER JOIN sys.schemas AS scm ON tbl.schema_id = scm.schema_id
                 WHERE tbl.name = ?
                   AND scm.name = SCHEMA_NAME()
                   AND idx.name = ?',
                [$table, $index]
            );
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
