<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        $currentTimestamp = DB::getDriverName() === 'sqlsrv' ? 'CURRENT_TIMESTAMP' : 'NOW()';
        $createView = DB::getDriverName() === 'sqlsrv' ? 'CREATE OR ALTER VIEW' : 'CREATE OR REPLACE VIEW';

        DB::unprepared("
            {$createView} user_visibility_current AS
            -- visão nativa (closure)
            SELECT uc.ancestor_id AS viewer_id,
                   uc.descendant_id
            FROM user_closure uc
            UNION
            -- visão delegada (vigente)
            SELECT d.delegate_id AS viewer_id,
                   uc.descendant_id
            FROM user_delegations d
            JOIN user_closure uc ON uc.ancestor_id = d.principal_id
            WHERE {$currentTimestamp} >= d.valid_from
              AND (d.valid_to IS NULL OR {$currentTimestamp} <= d.valid_to)
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS user_visibility_current');
    }
};
