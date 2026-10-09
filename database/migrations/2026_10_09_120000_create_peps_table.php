<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('peps', function (Blueprint $table) {
            // O identificador SAP é a chave natural recebida da base PEP.
            $table->string('objeto', 50)->primary();
            $table->integer('ano')->nullable()->index();
            $table->decimal('orcamento', 18, 2)->nullable();
            $table->decimal('real', 18, 2)->nullable();
            $table->decimal('compromisso', 18, 2)->nullable();
            $table->decimal('plan_res_ord', 18, 2)->nullable();
            $table->decimal('disposto', 18, 2)->nullable();
            $table->decimal('disponivel', 18, 2)->nullable()->index();
            $table->string('regiao', 2)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peps');
    }
};
