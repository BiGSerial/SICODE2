<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_inform_flows', function (Blueprint $table) {
            $table->id();

            // Identificacao principal do ciclo/informe consolidado
            $table->foreignId('note_id')
                ->constrained('notes')
                ->cascadeOnDelete();

            $table->string('flow_type', 30);

            $table->foreignId('partial_id')
                ->nullable()
                ->constrained('partials')
                ->nullOnDelete();

            $table->foreignId('work_report_id')
                ->nullable()
                ->constrained('work_reports')
                ->nullOnDelete();

            $table->string('flow_key', 100)->unique();

            // Campos base de negocio (padrao de ids da casa)
            $table->foreignUuid('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();

            $table->foreignUuid('service_id')
                ->nullable();

            $table->foreign('service_id')
                ->references('uuid')
                ->on('services')
                ->nullOnDelete();

            $table->string('note_number', 80)->nullable();
            $table->string('ovi', 80)->nullable();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            $table->string('order_number', 80)->nullable();

            // Informe de conclusao de obra
            $table->timestamp('informed_at')->nullable();
            $table->string('inform_type', 30)->nullable();
            $table->boolean('is_validated_by_publication')->default(false);
            $table->timestamp('publication_validated_at')->nullable();

            // ADS
            $table->boolean('has_ads')->default(false);

            $table->foreignId('ads_form_id')
                ->nullable()
                ->constrained('adsforms')
                ->nullOnDelete();

            $table->timestamp('ads_sent_at')->nullable();
            $table->string('ads_type', 30)->nullable();
            $table->boolean('ads_is_tacit')->default(false);

            // Fiscalizacao
            $table->timestamp('fiscalization_entered_at')->nullable();
            $table->string('fiscalization_type', 30)->nullable();
            $table->timestamp('fiscal_assigned_at')->nullable();

            $table->foreignUuid('fiscal_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('fiscal_user_name', 255)->nullable();
            $table->timestamp('fiscalization_completed_at')->nullable();

            // Baixa da fiscalizacao
            $table->boolean('fiscalization_closed_in_sicode')->default(false);
            $table->timestamp('fiscalization_closed_in_sicode_at')->nullable();

            $table->boolean('fiscalization_closed_in_sap')->default(false);
            $table->timestamp('fiscalization_closed_in_sap_at')->nullable();

            // D5 / FiveNote
            $table->boolean('has_d5')->default(false);

            $table->foreignId('five_note_id')
                ->nullable()
                ->constrained('five_notes')
                ->nullOnDelete();

            $table->string('five_note_number', 80)->nullable();
            $table->timestamp('five_note_created_at')->nullable();

            // Medicao / Pagamento
            $table->timestamp('measurement_entered_at')->nullable();
            $table->string('measurement_type', 30)->nullable();
            $table->timestamp('measurement_completed_at')->nullable();
            $table->timestamp('measurement_exited_at')->nullable();

            // Productions resolvidas para ciclo final
            $table->foreignId('ads_production_id')
                ->nullable()
                ->constrained('productions')
                ->nullOnDelete();

            $table->foreignId('fiscalization_production_id')
                ->nullable()
                ->constrained('productions')
                ->nullOnDelete();

            $table->foreignId('measurement_production_id')
                ->nullable()
                ->constrained('productions')
                ->nullOnDelete();

            // Janela de calculo do ciclo final
            $table->timestamp('final_cycle_started_at')->nullable();
            $table->timestamp('final_cycle_ended_at')->nullable();

            // Estado calculado
            $table->string('current_stage', 80)->nullable();
            $table->string('blocking_reason', 500)->nullable();

            // Sincronizacao
            $table->boolean('active')->default(true);
            $table->timestamp('source_created_at')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('calculated_at')->nullable();

            // Auditoria
            $table->json('resolver_payload')->nullable();

            $table->timestamps();

            // Indices
            $table->index('note_id');
            $table->index('flow_type');
            $table->index('partial_id');
            $table->index('work_report_id');
            $table->index('company_id');
            $table->index('service_id');

            $table->index('informed_at');
            $table->index('ads_sent_at');
            $table->index('fiscalization_entered_at');
            $table->index('fiscalization_completed_at');
            $table->index('measurement_entered_at');
            $table->index('measurement_exited_at');

            $table->index('current_stage');
            $table->index('active');

            $table->index(['note_id', 'flow_type']);
            $table->index(['note_id', 'active']);
            $table->index(['flow_type', 'active']);
            $table->index(['current_stage', 'active']);
        });

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("\n                ALTER TABLE note_inform_flows\n                ADD CONSTRAINT note_inform_flows_flow_type_check\n                CHECK (flow_type IN ('partial', 'final'))\n            ");

            DB::statement("\n                ALTER TABLE note_inform_flows\n                ADD CONSTRAINT note_inform_flows_origin_check\n                CHECK (\n                    (flow_type = 'partial' AND partial_id IS NOT NULL AND work_report_id IS NULL)\n                    OR\n                    (flow_type = 'final' AND work_report_id IS NOT NULL AND partial_id IS NULL)\n                )\n            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('note_inform_flows');
    }
};
