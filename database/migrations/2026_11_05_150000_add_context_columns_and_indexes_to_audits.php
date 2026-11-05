<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `audits` guarda o snapshot completo do model em JSON (`before`/`after`) e só tinha o índice da PK: filtrar por nota/atividade/registro
 * lia o JSON de 1,2 mi de linhas (~10 s). As colunas abaixo são extraídas do JSON na gravação (`Audit::creating`) e no backfill
 * (`php artisan audits:backfill-context`); os índices servem a tela de auditoria. O JSON continua sendo a fonte de verdade.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->unsignedBigInteger('note_id')->nullable();
            $table->string('service_id', 36)->nullable();
            $table->string('record_id', 40)->nullable();
            $table->unsignedSmallInteger('changes_count')->nullable();
            $table->text('changed_fields')->nullable();
        });

        Schema::table('audits', function (Blueprint $table) {
            $table->index('created_at', 'audits_created_idx');
            $table->index(['note_id', 'created_at'], 'audits_note_idx');
            $table->index(['service_id', 'created_at'], 'audits_service_idx');
            $table->index(['record_id', 'created_at'], 'audits_record_idx');
            $table->index(['user_id', 'created_at'], 'audits_user_idx');
            $table->index(['model_class', 'created_at'], 'audits_model_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropIndex('audits_created_idx');
            $table->dropIndex('audits_note_idx');
            $table->dropIndex('audits_service_idx');
            $table->dropIndex('audits_record_idx');
            $table->dropIndex('audits_user_idx');
            $table->dropIndex('audits_model_idx');
        });

        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn(['note_id', 'service_id', 'record_id', 'changes_count', 'changed_fields']);
        });
    }
};
