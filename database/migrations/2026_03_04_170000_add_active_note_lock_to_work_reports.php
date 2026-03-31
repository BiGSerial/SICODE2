<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('work_reports', 'active_note_id')) {
                $table->unsignedBigInteger('active_note_id')
                    ->storedAs('CASE WHEN canceled = 0 THEN note_id ELSE NULL END')
                    ->after('note_id');
            }
        });

        // índices fora do closure pra evitar "Blueprint stale" em alguns drivers
        if (!$this->indexExists('work_reports', 'uq_work_reports_single_active_note')) {
            Schema::table('work_reports', function (Blueprint $table) {
                $table->unique('active_note_id', 'uq_work_reports_single_active_note');
            });
        }

        if (!$this->indexExists('work_reports', 'idx_wr_note_canceled')) {
            Schema::table('work_reports', function (Blueprint $table) {
                $table->index(['note_id', 'canceled'], 'idx_wr_note_canceled');
            });
        }
    }

    public function down(): void
    {
        // drop unique se existir
        if ($this->indexExists('work_reports', 'uq_work_reports_single_active_note')) {
            Schema::table('work_reports', function (Blueprint $table) {
                $table->dropUnique('uq_work_reports_single_active_note');
            });
        }

        // NÃO dropar idx_wr_note_canceled (FK pode depender em alguns ambientes)
        // if ($this->indexExists('work_reports', 'idx_wr_note_canceled')) {
        //     Schema::table('work_reports', function (Blueprint $table) {
        //         $table->dropIndex('idx_wr_note_canceled');
        //     });
        // }

        // drop coluna se existir
        if (Schema::hasColumn('work_reports', 'active_note_id')) {
            Schema::table('work_reports', function (Blueprint $table) {
                $table->dropColumn('active_note_id');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $row = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND index_name = ?
            LIMIT 1
        ", [$table, $index]);

        return (bool) $row;
    }
};