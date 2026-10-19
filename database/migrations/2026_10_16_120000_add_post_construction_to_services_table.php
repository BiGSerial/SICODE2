<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class () extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('services', 'post_construction')) {
            Schema::table('services', function (Blueprint $table) {
                $table->boolean('post_construction')->default(false)->after('construction');
            });
        }

        // Encerramento é atividade de Pós obra: sai de Projeto/Construção do menu ATIVIDADES.
        DB::table('services')->where('folder', 'encerramento')->update([
            'project'           => false,
            'construction'      => false,
            'post_construction' => true,
            'updated_at'        => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('services')->where('folder', 'encerramento')->update([
            'project'      => true,
            'construction' => true,
        ]);

        if (Schema::hasColumn('services', 'post_construction')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('post_construction');
            });
        }
    }
};
