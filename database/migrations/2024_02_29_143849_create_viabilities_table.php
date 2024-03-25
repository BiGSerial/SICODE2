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
        Schema::create('viabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignUuid('company_id')->constrained('companies');
            $table->foreignUuid('user_id')->constrained('users');
            $table->uuid('engineer_id')->nullable();
            $table->dateTime('init_at')->nullable();
            $table->dateTime('sended_at')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->dateTime('tacit_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->boolean('tacit')->default(false);
            $table->boolean('completed')->default(false);
            $table->boolean('canceled')->default(false);
            $table->boolean('rejected')->default(false);
            $table->boolean('approved')->default(false);
            $table->boolean('engineer')->default(false);
            $table->dateTime('engineer_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('viabilities');
    }
};
