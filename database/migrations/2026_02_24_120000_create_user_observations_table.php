<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('user_observations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('observer_id')->constrained('users')->cascadeOnDelete();
            $t->foreignUuid('target_id')->constrained('users')->cascadeOnDelete();
            $t->string('mode', 20)->default('subtree'); // subtree | node_only
            $t->timestamp('valid_from')->useCurrent();
            $t->timestamp('valid_to')->nullable();
            $t->text('reason')->nullable();
            $t->timestamps();

            $t->index(['observer_id', 'valid_from', 'valid_to'], 'uo_observer_window_idx');
            $t->index(['target_id', 'valid_from', 'valid_to'], 'uo_target_window_idx');
            $t->unique(['observer_id', 'target_id', 'mode', 'valid_from'], 'uo_unique_window');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_observations');
    }
};
