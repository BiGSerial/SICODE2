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
        Schema::create('viability_approval_reclaim', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viability_approval_id')->constrained('viability_approvals')->cascadeOnDelete();
            $table->foreignId('reclaim_id')->constrained('reclaims')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('viability_approval_reclaim');
    }
};
