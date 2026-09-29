<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('five_notes', 'answered_by_user_id')) {
            Schema::table('five_notes', function (Blueprint $table): void {
                $table->foreignUuid('answered_by_user_id')
                    ->nullable()
                    ->after('name')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('five_notes', 'answered_by_user_id')) {
            Schema::table('five_notes', function (Blueprint $table): void {
                $table->dropForeign(['answered_by_user_id']);
                $table->dropColumn('answered_by_user_id');
            });
        }
    }
};
