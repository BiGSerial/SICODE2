<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        DB::statement("
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
        ");
    }

    public function down(): void
    {
        //
    }
};
