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
        Schema::create('viability_rehiring_audits', function (Blueprint $table) {
            $table->id();

            // Alvo da auditoria (ajuste aqui se viabilities.id for UUID)
            $table->foreignId('viability_id')->constrained()->cascadeOnDelete();

            // === USUÁRIOS SÃO UUIDs ===
            $table->foreignUuid('acted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('old_engineer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('new_engineer_id')->nullable()->constrained('users')->nullOnDelete();

            // Empresas (assumindo companies.id inteiro; ver variação abaixo se for UUID)
            $table->foreignUuid('old_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignUuid('new_company_id')->nullable()->constrained('companies')->nullOnDelete();

            // Flags do processo
            $table->boolean('was_newsend')->default(false);
            $table->boolean('was_rehiring')->default(false);

            // Datas
            $table->timestamp('old_sended_at')->nullable();
            $table->timestamp('new_sended_at')->nullable();

            // Situação de days antes da mudança
            $table->boolean('had_days_before')->default(false);
            $table->unsignedInteger('days_count_before')->default(0);

            // Campo livre para evolução futura
            $table->json('meta')->nullable();

            $table->timestamps();

            // Índices úteis
            $table->index(['viability_id', 'was_newsend']);
            $table->index('acted_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('viability_rehiring_audits');
    }
};
