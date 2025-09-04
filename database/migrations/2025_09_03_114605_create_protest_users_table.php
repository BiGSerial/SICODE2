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
        Schema::dropIfExists('protest_users');

        Schema::create('protest_users', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete(); // usuário "trigger" principal (UUID)
            $table->boolean('default')->default(false); // se é o usuário padrão para novos protestos
            $table->timestamps();


            $table->unique(['user_id']);

            // FK movida para foreignUuid('user_id') acima (UUID) //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('protest_users');
    }
};
