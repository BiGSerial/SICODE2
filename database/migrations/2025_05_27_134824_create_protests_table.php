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
        Schema::create('protests', function (Blueprint $table) {
            $table->id();
            $table->string('nota')->unique();
            $table->string('tipoNota');
            $table->string('txtGrpCodificacao')->nullable();

            $table->date('dtAberturaNota')->nullable();
            $table->date('dtConclusaoDesej')->nullable();


            $table->string('cenPlan')->nullable();
            $table->string('cidade')->nullable();
            $table->string('statUsuar')->nullable();

            $table->text('descCausa')->nullable();
            $table->text('descSubCausa')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('protests');
    }
};
