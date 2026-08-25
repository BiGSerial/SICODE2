<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        $this->dropIndexIfExists('ads_requests', 'ads_requests_sqlserver_id_unique');

        DB::statement('CREATE UNIQUE INDEX ads_requests_sqlserver_id_unique ON ads_requests (sqlserver_id) WHERE sqlserver_id IS NOT NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        $this->dropIndexIfExists('ads_requests', 'ads_requests_sqlserver_id_unique');

        DB::statement('CREATE UNIQUE INDEX ads_requests_sqlserver_id_unique ON ads_requests (sqlserver_id)');
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        $exists = DB::selectOne(
            'SELECT 1 AS found FROM sys.indexes WHERE name = ? AND object_id = OBJECT_ID(?)',
            [$index, 'dbo.' . $table]
        );

        if ($exists) {
            DB::statement("DROP INDEX {$index} ON dbo.{$table}");
        }
    }
};
