<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if ($this->hasIndex('external_poolpayments', 'external_poolpayments_pool_id_unique')) {
            Schema::table('external_poolpayments', function (Blueprint $table) {
                $table->dropUnique('external_poolpayments_pool_id_unique');
            });
        }

        Schema::table('external_poolpayments', function (Blueprint $table) {
            $table->string('pool_id', 30)
                ->nullable()
                ->comment('ID numérico legado ou código alfanumérico da solicitação')
                ->change();
        });

        if (!$this->hasIndex('external_poolpayments', 'external_poolpayments_pool_id_unique')) {
            Schema::table('external_poolpayments', function (Blueprint $table) {
                $table->unique('pool_id', 'external_poolpayments_pool_id_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('external_poolpayments', 'external_poolpayments_pool_id_unique')) {
            Schema::table('external_poolpayments', function (Blueprint $table) {
                $table->dropUnique('external_poolpayments_pool_id_unique');
            });
        }

        Schema::table('external_poolpayments', function (Blueprint $table) {
            $table->unsignedBigInteger('pool_id')
                ->nullable()
                ->comment('ID da Solicitação / PoolId')
                ->change();
        });

        if (!$this->hasIndex('external_poolpayments', 'external_poolpayments_pool_id_unique')) {
            Schema::table('external_poolpayments', function (Blueprint $table) {
                $table->unique('pool_id', 'external_poolpayments_pool_id_unique');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
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
                [$table, $indexName]
            );
        }

        $dbName = DB::getDatabaseName();

        return (bool) DB::selectOne(
            'SELECT 1
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND index_name = ?
             LIMIT 1',
            [$dbName, $table, $indexName]
        );
    }
};
