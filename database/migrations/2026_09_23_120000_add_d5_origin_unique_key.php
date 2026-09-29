<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('five_notes', function (Blueprint $table): void {
            if (!Schema::hasColumn('five_notes', 'd5_origin_key')) {
                $table->string('d5_origin_key', 80)->nullable();
            }
        });

        DB::statement("
            UPDATE five_notes
            SET d5_origin_key = CASE
                WHEN work_report_id IS NULL THEN CONCAT('note:', note_id)
                ELSE CONCAT('work_report:', work_report_id)
            END
            WHERE d5_origin_key IS NULL
        ");

        Schema::table('five_notes', function (Blueprint $table): void {
            $table->unique('d5_origin_key', 'five_notes_origin_unique');
        });
    }

    public function down(): void
    {
        Schema::table('five_notes', function (Blueprint $table): void {
            if (Schema::hasColumn('five_notes', 'd5_origin_key')) {
                $table->dropUnique('five_notes_origin_unique');
                $table->dropColumn('d5_origin_key');
            }
        });
    }
};
