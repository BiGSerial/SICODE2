<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('closure_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('closure_cycle_id')->constrained('closure_cycles');
            $table->foreignId('order_id')->unique()->constrained('orders');
            $table->foreignId('note_id')->constrained('notes');
            $table->string('entry_rule');
            $table->json('entry_reference')->nullable();
            $table->string('snapshot_status_sist')->nullable();
            $table->timestamp('frozen_at');
            $table->timestamps();

            $table->index('closure_cycle_id');
            $table->index('note_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closure_targets');
    }
};
