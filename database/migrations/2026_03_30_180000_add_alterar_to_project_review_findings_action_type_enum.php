<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_review_findings') || !Schema::hasColumn('project_review_findings', 'action_type')) {
            return;
        }

        $driver = DB::getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("
            ALTER TABLE project_review_findings
            MODIFY action_type ENUM('FALTA', 'ADICIONAR', 'REMOVER', 'ALTERAR') NULL
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('project_review_findings') || !Schema::hasColumn('project_review_findings', 'action_type')) {
            return;
        }

        $driver = DB::getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::table('project_review_findings')
            ->where('action_type', 'ALTERAR')
            ->update(['action_type' => 'REMOVER']);

        DB::statement("
            ALTER TABLE project_review_findings
            MODIFY action_type ENUM('FALTA', 'ADICIONAR', 'REMOVER') NULL
        ");
    }
};

