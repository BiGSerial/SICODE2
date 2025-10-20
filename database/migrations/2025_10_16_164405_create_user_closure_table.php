<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('user_closure');

        Schema::create('user_closure', function (Blueprint $t) {
            $t->uuid('ancestor_id');
            $t->uuid('descendant_id');
            $t->unsignedInteger('depth'); // 0 = si mesmo; 1 = direto; 2+ = níveis

            // PK por par (sem histórico nesta versão)
            $t->primary(['ancestor_id','descendant_id']);

            // FKs explícitas (MariaDB aceita FK em char(36))
            $t->foreign('ancestor_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('descendant_id')->references('id')->on('users')->cascadeOnDelete();

            // Índices auxiliares
            $t->index(['ancestor_id','depth'], 'uc_ancestor_depth_idx');
            $t->index(['descendant_id','depth'], 'uc_descendant_depth_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_closure');
    }
};
