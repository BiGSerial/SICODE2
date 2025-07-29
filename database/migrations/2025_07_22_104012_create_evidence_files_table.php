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
        Schema::create('evidence_files', function (Blueprint $table) {
            $table->id();
            // Quem enviou
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // Para que registro esta evidência pertence
            $table->morphs('evidenciable'); // evidenciable_type, evidenciable_id (indexados)

            // Dados do arquivo
            $table->string('original_name');              // Nome original
            $table->string('stored_name');                // Nome salvo (ex: hash + ext)
            $table->string('disk', 30)->default('public');// Disco do Storage
            $table->string('path');                       // Caminho dentro do disco
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable(); // bytes
            $table->string('extension', 5)->nullable();
            $table->string('sha256', 64)->nullable();       // hash opcional p/ deduplicar

            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidence_files');
    }
};
