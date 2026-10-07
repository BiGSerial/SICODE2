<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('measurement_production_contexts', function (Blueprint $table): void {
            $table->id();

            // A Production pode ter apenas um contexto de Medição/Pagamento.
            $table->foreignId('production_id')
                ->unique()
                ->constrained('productions')
                ->cascadeOnDelete();

            // Exatamente um destes dois alvos deve ser informado, exceto Retorno
            // de D5 passiva sem Informe (identificado obrigatoriamente pela D5).
            $table->foreignId('work_report_id')
                ->nullable()
                ->constrained('work_reports');
            $table->foreignId('partial_id')
                ->nullable()
                ->constrained('partials');

            // Obrigatória para cycle=d5_return; opcional no cycle_1, no qual
            // representa uma D5 existente desde a entrada normal na Medição.
            $table->foreignId('five_note_id')
                ->nullable()
                ->constrained('five_notes');

            $table->enum('cycle', ['partial', 'cycle_1', 'd5_return']);

            // A escrita central deve preencher esta chave somente enquanto a
            // Production estiver ativa. NULL libera um novo despacho legítimo
            // depois do encerramento do Parcial, sem bloquear o histórico.
            $table->string('active_demand_key', 191)->nullable()->unique();

            // Unicidades condicionais, materializadas para funcionar no MySQL.
            // Elas preservam histórico de outros ciclos/D5s, mas tornam Ciclo 1
            // e Retorno de uma mesma D5 consumíveis uma única vez.
            $table->unsignedBigInteger('cycle_1_work_report_id')
                ->nullable()
                ->storedAs("case when `cycle` = 'cycle_1' then `work_report_id` else null end");
            $table->unsignedBigInteger('d5_return_five_note_id')
                ->nullable()
                ->storedAs("case when `cycle` = 'd5_return' then `five_note_id` else null end");

            $table->timestamps();

            $table->unique('cycle_1_work_report_id', 'measurement_context_cycle_1_unique');
            $table->unique('d5_return_five_note_id', 'measurement_context_d5_return_unique');
            $table->index(['work_report_id', 'cycle'], 'measurement_context_work_report_cycle_idx');
            $table->index(['partial_id', 'cycle'], 'measurement_context_partial_cycle_idx');
            $table->index(['five_note_id', 'cycle'], 'measurement_context_five_note_cycle_idx');
        });

        // PostgreSQL aplica as invariantes também para inserções SQL diretas.
        // MySQL/MariaDBs suportados pelo projeto variam no suporte/enforcement de
        // CHECK; por isso a Model e o futuro MeasurementDispatchService também
        // revalidam estes dados antes da escrita.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("\n                ALTER TABLE measurement_production_contexts\n                ADD CONSTRAINT measurement_context_exactly_one_inform_check\n                CHECK (\n                    (work_report_id IS NOT NULL AND partial_id IS NULL)\n                    OR (work_report_id IS NULL AND partial_id IS NOT NULL)\n                    OR (cycle = 'd5_return' AND work_report_id IS NULL AND partial_id IS NULL AND five_note_id IS NOT NULL)\n                )\n            ");

            DB::statement("\n                ALTER TABLE measurement_production_contexts\n                ADD CONSTRAINT measurement_context_cycle_target_check\n                CHECK (\n                    (cycle = 'partial' AND partial_id IS NOT NULL AND five_note_id IS NULL)\n                    OR (cycle = 'cycle_1' AND work_report_id IS NOT NULL)\n                    OR (cycle = 'd5_return' AND partial_id IS NULL AND five_note_id IS NOT NULL)\n                )\n            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_production_contexts');
    }
};
