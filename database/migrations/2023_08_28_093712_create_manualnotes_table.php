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
        Schema::create('manualnotes', function (Blueprint $table) {
            $table->id();
            $table->string('note')->nullable();
            $table->integer('status')->nullable();
            $table->uuid('service_id')->reference('uuid')->on('services')->onDelete('cascade');
            $table->uuid('user_id')->reference('id')->on('users')->onDelete('cascade');
            $table->string('solicitante')->nullable();
            $table->string('setor')->nullable();
            $table->timestamp('finish_at')->nullable();
            $table->boolean('completed')->default(false);
            $table->boolean('confirmed')->default(false);
            $table->boolean('cancel')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manualnotes');
    }
};
