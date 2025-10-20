<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            // Se seu users.id já é uuid (char(36)), use:
            $t->foreignUuid('manager_id')
              ->nullable()
              ->after('id')
              ->constrained('users')
              ->nullOnDelete();

            $t->index('manager_id', 'users_manager_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('manager_id');
            $t->dropIndex('users_manager_id_idx');
        });
    }
};
