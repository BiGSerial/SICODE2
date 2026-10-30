<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('services', 'partner_own_stack')) {
            Schema::table('services', function (Blueprint $table): void {
                // true = o parceiro tem pilha própria nesta atividade (comportamento histórico).
                $table->boolean('partner_own_stack')->default(true)->after('canReturn');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('services', 'partner_own_stack')) {
            Schema::table('services', function (Blueprint $table): void {
                $table->dropColumn('partner_own_stack');
            });
        }
    }
};
