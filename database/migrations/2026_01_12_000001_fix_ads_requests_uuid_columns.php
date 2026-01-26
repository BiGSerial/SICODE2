<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $this->dropForeignIfExists('ads_requests', 'requested_by');
        $this->dropForeignIfExists('ads_requests', 'company_id');

        DB::statement('ALTER TABLE ads_requests MODIFY requested_by CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE ads_requests MODIFY company_id CHAR(36) NOT NULL');

        Schema::table('ads_requests', function (Blueprint $table) {
            $table->foreign('requested_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $this->dropForeignIfExists('ads_requests', 'requested_by');
        $this->dropForeignIfExists('ads_requests', 'company_id');

        DB::statement('ALTER TABLE ads_requests MODIFY requested_by CHAR(26) NOT NULL');
        DB::statement('ALTER TABLE ads_requests MODIFY company_id CHAR(26) NOT NULL');
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        $database = DB::getDatabaseName();
        $constraints = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$database, $table, $column]
        );

        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
        }
    }
};
