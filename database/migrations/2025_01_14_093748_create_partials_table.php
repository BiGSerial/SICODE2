<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('partials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignUuid('company_id')->nullable();
            $table->foreignUuid('user_id')->nullable();
            $table->text('observation')->nullable();
            $table->text('engineer_info')->nullable();
            $table->string('responsible')->nullable();
            $table->decimal('value')->nullable();
            $table->boolean('allow')->default(false);
            $table->boolean('deny')->default(false);
            $table->boolean('payment')->default(false);
            $table->boolean('supervision')->default(false);
            $table->boolean('complete')->default(false);
            $table->uuid('engineer_id')->nullable();
            $table->uuid('supervision_id')->nullable();
            $table->uuid('payment_id')->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->timestamp('payment_at')->nullable();
            $table->timestamp('supervision_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        // Agora, você pode excluir a tabela
        Schema::dropIfExists('partials');


    }
};
