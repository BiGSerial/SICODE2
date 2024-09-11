<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->boolean('responsible')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Verificar e remover a chave estrangeira associada a company_id
            if (Schema::hasColumn('users', 'company_id')) {
                $table->dropForeign(['company_id']); // Remove a chave estrangeira
                $table->dropColumn('company_id');    // Depois, remove a coluna
            }

            if (Schema::hasColumn('users', 'responsible')) {
                $table->dropColumn('responsible');   // Remove a coluna responsible se ela existir
            }
        });
    }
};
