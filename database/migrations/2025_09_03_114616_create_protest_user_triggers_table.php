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
        Schema::dropIfExists('protest_user_triggers');

        Schema::create('protest_user_triggers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('protest_user_id');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete(); // usuário da "cadeia" (UUID)
            $table->timestamps();


            $table->unique(['protest_user_id','user_id']);
            $table->foreign('protest_user_id')->references('id')->on('protest_users')->onDelete('cascade');
            // FK user via foreignUuid acima
        });

    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('protest_user_triggers');
    }
};
