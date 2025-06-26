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
        Schema::create('med_protests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protest_id')->constrained('protests')->onDelete('cascade');
            $table->integer('med_id')->unsigned()->nullable();
            $table->string('statusSist', 50)->nullable();
            $table->string('codMedida', 50)->nullable();
            $table->string('txtCodCodificacao')->nullable();
            $table->string('txtCodMedida')->nullable();
            $table->date('dtCriacaoMedida')->nullable();
            $table->date('dtFimMedidaDesej')->nullable();
            $table->date('dtFimMedida')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->boolean('needsEvidence')->nullable()->default(false);
            $table->boolean('needsConfirmation')->nullable()->default(false);

            $table->timestamps();

            $table->unique(['protest_id', 'med_id'], 'med_protests_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('med_protests');
    }
};
