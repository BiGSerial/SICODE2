<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('project_review_findings', 'origin')) {
            Schema::table('project_review_findings', function (Blueprint $table) {
                $table->enum('origin', ['LEVANTAMENTO', 'PROJETO', 'AMBOS'])->nullable()->after('item_id');
            });
        }

        if (!Schema::hasColumn('project_review_findings', 'action_type')) {
            Schema::table('project_review_findings', function (Blueprint $table) {
                $table->enum('action_type', ['FALTA', 'ADICIONAR', 'REMOVER'])->nullable()->after('origin');
            });
        }

        if (!Schema::hasColumn('project_review_findings', 'quantity')) {
            Schema::table('project_review_findings', function (Blueprint $table) {
                $table->unsignedInteger('quantity')->nullable()->after('action_type');
            });
        }

        // Permite criar estrutura de subcategoria sem item vinculado.
        DB::statement('ALTER TABLE project_review_findings MODIFY item_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE project_review_findings MODIFY item_id BIGINT UNSIGNED NOT NULL');

        if (Schema::hasColumn('project_review_findings', 'action_type')) {
            Schema::table('project_review_findings', function (Blueprint $table) {
                $table->dropColumn('action_type');
            });
        }

        if (Schema::hasColumn('project_review_findings', 'quantity')) {
            Schema::table('project_review_findings', function (Blueprint $table) {
                $table->dropColumn('quantity');
            });
        }
    }
};
