<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('quality_members', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('role', 2);
            $table->boolean('active')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'company_id', 'role'], 'quality_members_unique');
            $table->index(['company_id', 'role', 'active']);
        });

        // Mesmo formato de `auxiliar_services`: regras de inclusão/exclusão por coluna da Nota (RuleBuilder).
        Schema::create('quality_pool_rules', function (Blueprint $table) {
            $table->id();
            $table->string('column_search')->nullable();
            $table->string('condition')->nullable();
            $table->boolean('exclusion')->default(false);
            $table->string('value')->nullable();
            $table->string('column_search2')->nullable();
            $table->string('condition2')->nullable();
            $table->boolean('exclusion2')->default(false);
            $table->string('value2')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // A Production só nasce quando o N1 despacha ao desenhista: o processo passa a existir antes dela.
        DB::statement('ALTER TABLE quality_processes MODIFY production_id BIGINT UNSIGNED NULL, MODIFY original_designer_id CHAR(36) NULL');
        DB::statement('ALTER TABLE quality_events MODIFY production_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE quality_rejections MODIFY production_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE quality_sap_requests MODIFY production_id BIGINT UNSIGNED NULL');

        Schema::table('quality_processes', function (Blueprint $table) {
            $table->unique('note_id', 'quality_processes_note_unique');
        });

        Schema::table('quality_stages', function (Blueprint $table) {
            $table->foreignId('production_id')->nullable()->after('quality_process_id')->constrained('productions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quality_stages', fn (Blueprint $table) => $table->dropConstrainedForeignId('production_id'));
        Schema::table('quality_processes', fn (Blueprint $table) => $table->dropUnique('quality_processes_note_unique'));
        Schema::dropIfExists('quality_pool_rules');
        Schema::dropIfExists('quality_members');
    }
};
