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
        Schema::table('viabilities', function (Blueprint $table) {
            Schema::table('viabilities', function (Blueprint $table) {
                // Remover a chave estrangeira (constraint)
                $table->dropForeign(['order_id']);

                // Tornar a coluna 'order_id' nullable
                $table->unsignedBigInteger('order_id')->nullable()->change();

            });
        });

        DB::statement('ALTER TABLE viabilities MODIFY COLUMN note_id BIGINT UNSIGNED NULL AFTER order_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('viabilities', function (Blueprint $table) {
            // Reverter 'order_id' para NOT NULL
            $table->unsignedBigInteger('order_id')->nullable(false)->change();

            // Restaurar a chave estrangeira
            $table->foreign('order_id')->references('id')->on('orders');
        });
    }
};
