<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Bloqueio de depósito no Encerramento. O depósito é o código SAP (ex.: 5503) e chega ao sistema por dois
 * caminhos: a parceira sinaliza, ou o despacho confirma uma data do calendário de inventário (gestão de ativos).
 * As Ordens afetadas são as que têm, na operação 0010, um centro de trabalho (`cenTrab`) cadastrado para esse
 * depósito em `centerjobs` (Administração > Empresa). Bloquear e liberar são uma ação só, e a tabela filha guarda
 * o status anterior de cada Ordem para a liberação devolvê-la ao ponto em que estava.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('closure_partner_signals')) {
            Schema::create('closure_partner_signals', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->string('type', 40);
                $table->string('status', 20)->default('ACTIVE');
                $table->string('origin', 20)->default('PARTNER');
                $table->string('deposit_code', 40);
                $table->string('center', 40)->nullable();
                $table->string('deposit_label')->nullable();
                $table->json('work_centers')->nullable();
                $table->unsignedBigInteger('calendar_entry_id')->nullable();
                $table->text('reason');
                $table->text('release_note')->nullable();
                $table->unsignedInteger('affected_count')->default(0);
                $table->unsignedInteger('released_count')->default(0);
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('released_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('released_at')->nullable();
                $table->timestamps();

                $table->index(['type', 'deposit_code', 'status'], 'closure_partner_signal_deposit_status_index');
            });
        }

        if (!Schema::hasTable('closure_partner_signal_states')) {
            Schema::create('closure_partner_signal_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('closure_partner_signal_id')->constrained('closure_partner_signals')->cascadeOnDelete();
                $table->foreignId('closure_order_state_id')->constrained('closure_order_states')->cascadeOnDelete();
                $table->string('previous_status', 40);
                $table->timestamp('released_at')->nullable();

                $table->unique(['closure_partner_signal_id', 'closure_order_state_id'], 'closure_partner_signal_state_unique');
            });
        }

        if (!Schema::hasTable('closure_deposit_calendar')) {
            Schema::create('closure_deposit_calendar', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('year');
                $table->string('company_label')->nullable();
                $table->string('city')->nullable();
                $table->string('center', 40);
                $table->string('deposit', 40);
                $table->string('denomination')->nullable();
                $table->string('service_type')->nullable();
                $table->unsignedTinyInteger('month')->nullable();
                $table->string('inventory_responsible')->nullable();
                $table->boolean('inventory_done')->default(false);
                $table->date('scheduled_date');
                $table->string('status', 20)->default('PENDING');
                $table->foreignId('signal_id')->nullable()->constrained('closure_partner_signals')->nullOnDelete();
                $table->foreignUuid('imported_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUuid('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('confirmed_at')->nullable();
                $table->text('skip_reason')->nullable();
                $table->timestamps();

                $table->unique(['year', 'center', 'deposit'], 'closure_deposit_calendar_year_deposit_unique');
                $table->index(['status', 'scheduled_date'], 'closure_deposit_calendar_status_date_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('closure_deposit_calendar');
        Schema::dropIfExists('closure_partner_signal_states');
        Schema::dropIfExists('closure_partner_signals');
    }
};
