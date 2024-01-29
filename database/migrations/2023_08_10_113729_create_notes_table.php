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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('note');
            $table->string('created_by')->nullable();
            $table->timestamp('dt_created')->nullable();
            $table->timestamp('dt_status')->nullable();
            $table->string('user')->nullable();
            $table->decimal('value', 15, 2)->nullable();
            $table->string('currency', 5)->nullable();
            $table->string('eq_venda', 10)->nullable();
            $table->string('numPedido')->nullable();
            $table->string('client')->nullable();
            $table->string('group1')->nullable();
            $table->string('group2')->nullable();
            $table->string('group3')->nullable();
            $table->string('group4')->nullable();
            $table->string('group5')->nullable();
            $table->integer('pze')->nullable();
            $table->integer('num_material')->nullable();
            $table->string('material')->nullable();
            $table->string('nexp')->nullable();
            $table->string('lexp')->nullable();
            $table->string('pep')->nullable();
            $table->string('nstats')->nullable();
            $table->string('status')->nullable();
            $table->integer('days')->nullable();
            $table->string('transaction')->nullable();
            $table->string('validar_prazo')->nullable();
            $table->string('rubrica')->nullable();
            $table->integer('pze_tratado')->nullable();
            $table->integer('days_stat')->nullable();
            $table->string('pze_parecer')->nullable();
            $table->bigInteger('days_left')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
