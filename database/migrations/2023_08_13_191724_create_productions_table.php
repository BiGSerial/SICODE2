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
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->reference('id')->on('notes')->onDelete('cascade');
            $table->foreignUuid('service_id')->reference('id')->on('services');
            $table->foreignUuid('user_id')->reference('id')->on('users')->nullable();
            $table->foreignUuid('company_id')->constrained('companies')->onDelete('cascade');
            $table->uuid('dispatch_by')->nullable();
            $table->uuid('att_by')->nullable();
            $table->timestamp('dt_note')->nullable();
            $table->string('status_note')->nullable();
            $table->timestamp('dispatch_at')->nullable();
            $table->timestamp('att_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->bigInteger('stopped')->nullable();
            $table->string('odi')->nullable();
            $table->string('odd')->nullable();
            $table->string('ods')->nullable();
            $table->integer('postes_u')->nullable();
            $table->integer('postes_l')->nullable();
            $table->boolean('completed')->default(false);
            $table->boolean('confirmed')->default(false);
            $table->boolean('returned')->default(false);
            $table->boolean('priority')->default(false);
            $table->integer('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};
