<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        match (DB::getDriverName()) {
            'sqlsrv' => DB::statement("
                UPDATE r
                SET
                    r.completed = 1,
                    r.completed_at = COALESCE(p.completed_at, CURRENT_TIMESTAMP),
                    r.updated_at = CURRENT_TIMESTAMP
                FROM reclaims AS r
                INNER JOIN services AS s ON s.uuid = r.service_id
                INNER JOIN productions AS p ON p.id = r.production_id
                WHERE r.completed = 0
                    AND s.service = 'Desenho'
                    AND p.completed = 1
            "),
            'pgsql' => DB::statement("
                UPDATE reclaims AS r
                SET
                    completed = 1,
                    completed_at = COALESCE(p.completed_at, CURRENT_TIMESTAMP),
                    updated_at = CURRENT_TIMESTAMP
                FROM services AS s, productions AS p
                WHERE s.uuid = r.service_id
                    AND p.id = r.production_id
                    AND r.completed = 0
                    AND s.service = 'Desenho'
                    AND p.completed = 1
            "),
            default => DB::statement("
                UPDATE reclaims r
                INNER JOIN services s ON s.uuid = r.service_id
                INNER JOIN productions p ON p.id = r.production_id
                SET
                    r.completed = 1,
                    r.completed_at = COALESCE(p.completed_at, NOW()),
                    r.updated_at = NOW()
                WHERE r.completed = 0
                    AND s.service = 'Desenho'
                    AND p.completed = 1
            "),
        };
    }

    public function down(): void
    {
        //
    }
};
