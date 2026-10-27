<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Recria historic_notes como histórico de status da base OV (type_note = 2).
     * A tabela antiga (old/new por mudança) não era lida por nenhuma tela.
     */
    public function up(): void
    {
        Schema::dropIfExists('historic_notes');

        Schema::create('historic_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->unsignedInteger('id_num');
            $table->integer('num_stat');
            $table->dateTime('dt_stats');
            $table->string('considerar_prazo', 50)->nullable();
            $table->integer('pze')->nullable();
            $table->string('status')->nullable();
            $table->string('transicao_usuario', 100)->nullable();
            $table->timestamps();

            // Chave do upsert; o prefixo note_id também atende a leitura por nota.
            $table->unique(['note_id', 'id_num', 'num_stat'], 'historic_notes_event_unique');
            $table->index('dt_stats');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historic_notes');

        Schema::create('historic_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->references('id')->on('notes')->onDelete('cascade');
            $table->timestamp('old_date')->nullable();
            $table->integer('old_stat')->nullable();
            $table->timestamp('new_date')->nullable();
            $table->integer('new_stat')->nullable();
            $table->timestamps();
        });
    }
};
