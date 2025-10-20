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
        Schema::create('user_delegations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('principal_id')->constrained('users')->cascadeOnDelete(); // titular
            $t->foreignUuid('delegate_id')->constrained('users')->cascadeOnDelete();  // cobridor
            $t->timestamp('valid_from')->useCurrent();
            $t->timestamp('valid_to')->nullable();
            $t->text('reason')->nullable();
            $t->timestamps();

            $t->index(['delegate_id','valid_from','valid_to'], 'ud_delegate_window_idx');
            $t->index(['principal_id','valid_from','valid_to'], 'ud_principal_window_idx');
            $t->unique(['principal_id','delegate_id','valid_from'], 'ud_unique_window');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_delegations');
    }
};
