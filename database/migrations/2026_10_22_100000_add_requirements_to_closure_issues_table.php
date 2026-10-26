<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lista de exigências que o operador faz à parceira numa pendência (itens de texto, em ordem). */
return new class () extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('closure_issues', 'requirements')) {
            Schema::table('closure_issues', function (Blueprint $table) {
                $table->json('requirements')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('closure_issues', 'requirements')) {
            Schema::table('closure_issues', function (Blueprint $table) {
                $table->dropColumn('requirements');
            });
        }
    }
};
