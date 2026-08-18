<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if ($this->hasDuplicateNotes()) {
            return;
        }

        Schema::table('notes', function (Blueprint $table) {
            if (!$this->indexExists('notes', 'notes_note_unique')) {
                $table->unique('note', 'notes_note_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            if ($this->indexExists('notes', 'notes_note_unique')) {
                $table->dropUnique('notes_note_unique');
            }
        });
    }

    private function hasDuplicateNotes(): bool
    {
        return DB::table('notes')
            ->select('note')
            ->groupBy('note')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
