<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('five_notes', function (Blueprint $table): void {
            if (!Schema::hasColumn('five_notes', 'work_report_id')) {
                $table->foreignId('work_report_id')
                    ->nullable()
                    ->after('note_id')
                    ->constrained('work_reports')
                    ->nullOnDelete();
            }
        });

        // D5s vinculados a informes podem coexistir para a mesma Note.
        Schema::table('five_notes', function (Blueprint $table): void {
            // A FK de note_id precisa continuar indexada depois da remoção
            // da unicidade herdada da relação legada.
            $table->index('note_id', 'five_notes_note_id_idx');
            $table->dropUnique(['note_id']);
        });

        Schema::table('five_notes', function (Blueprint $table): void {
            $table->unique('work_report_id', 'five_notes_work_report_id_unique');
            $table->index(['note_id', 'work_report_id'], 'five_notes_note_work_report_idx');
        });
    }

    public function down(): void
    {
        Schema::table('five_notes', function (Blueprint $table): void {
            $table->dropUnique('five_notes_work_report_id_unique');
            $table->dropIndex('five_notes_note_work_report_idx');
            $table->dropIndex('five_notes_note_id_idx');
            $table->dropForeign(['work_report_id']);
            $table->dropColumn('work_report_id');
            $table->unique('note_id');
        });
    }
};
