<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('partials', function (Blueprint $table) {
            $table->string('scope', 20)->default('general')->after('note_id');
            $table->index(['note_id', 'scope'], 'partials_note_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::table('partials', function (Blueprint $table) {
            $table->dropIndex('partials_note_scope_idx');
            $table->dropColumn('scope');
        });
    }
};
