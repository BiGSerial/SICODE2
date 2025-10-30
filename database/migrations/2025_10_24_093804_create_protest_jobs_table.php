<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('protest_jobs', function (Blueprint $table) {
            // PK inteira
            $table->bigIncrements('id');

            // Relações inteiras (para protests/med_protests)
            $table->foreignId('protest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('med_protest_id')->constrained()->cascadeOnDelete();

            // Relações com users (UUID)
            $table->foreignUuid('created_by')->constrained('users');
            $table->foreignUuid('owner_id')->constrained('users');
            $table->foreignUuid('closed_by')->nullable()->constrained('users');

            // Status & prioridade
            $table->enum('status', [
                'opened','assigned','in_progress','waiting','done','canceled','reopened'
            ])->index();
            $table->enum('priority', ['low','normal','high','urgent'])->default('normal')->index();

            // Marcos do ciclo
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // SLA / Escalonamento
            $table->timestamp('sla_due_at')->nullable()->index();
            $table->timestamp('sla_breached_at')->nullable()->index();
            $table->timestamp('escalated_at')->nullable();
            $table->unsignedSmallInteger('escalation_level')->default(0);

            // Saída / observações
            $table->json('outcome')->nullable();
            $table->text('close_reason')->nullable();
            $table->text('notes')->nullable();

            // Booleanos
            $table->boolean('need_evidence')->default(false);
            $table->boolean('is_advance')->default(false);

            // Auditoria
            $table->softDeletes();
            $table->timestamps();

            // Índices úteis
            $table->index(['med_protest_id','status']);
            $table->index(['owner_id','status']);
            $table->index('created_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protest_jobs');
    }
};
