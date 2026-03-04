<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            // Permite historico de registros por nota, mas garante apenas 1 ativo (canceled = false).
            $table->unsignedBigInteger('active_note_id')
                ->nullable()
                ->storedAs('CASE WHEN canceled = 0 THEN note_id ELSE NULL END')
                ->after('note_id');

            $table->unique('active_note_id', 'uq_work_reports_single_active_note');
            $table->index(['note_id', 'canceled'], 'idx_wr_note_canceled');
        });
    }

    public function down(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            $table->dropUnique('uq_work_reports_single_active_note');
            $table->dropIndex('idx_wr_note_canceled');
            $table->dropColumn('active_note_id');
        });
    }
};

