<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE project_review_cycles MODIFY decision ENUM('PENDING','APPROVED','APPROVED_WITH_REMARKS','REJECTED','FINALIZED') NOT NULL DEFAULT 'PENDING'");
    }

    public function down(): void
    {
        // Ciclos já finalizados voltam como aprovados para o enum antigo não rejeitar os registros.
        DB::table('project_review_cycles')->where('decision', 'FINALIZED')->update(['decision' => 'APPROVED']);

        DB::statement("ALTER TABLE project_review_cycles MODIFY decision ENUM('PENDING','APPROVED','APPROVED_WITH_REMARKS','REJECTED') NOT NULL DEFAULT 'PENDING'");
    }
};
