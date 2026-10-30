<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pep_balance_alerts')) {
            return;
        }

        Schema::create('pep_balance_alerts', function (Blueprint $table): void {
            $table->id();
            $table->string('informe_type', 20);
            $table->unsignedBigInteger('informe_id');
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->uuid('company_id')->nullable()->index();
            $table->string('scope', 30)->nullable();
            $table->string('region')->nullable()->index();
            $table->string('reason', 20);
            $table->json('balances')->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->timestamp('covered_since')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->foreignUuid('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('handled_via', 20)->nullable();
            $table->text('handled_notes')->nullable();
            $table->timestamps();

            $table->unique(['informe_type', 'informe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pep_balance_alerts');
    }
};
