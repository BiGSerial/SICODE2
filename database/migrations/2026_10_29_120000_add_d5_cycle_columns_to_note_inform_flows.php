<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('note_inform_flows', function (Blueprint $table): void {
            // As colunas fiscalization_* / measurement_* passam a refletir somente o ciclo 1
            // (producoes nao-D5 do informe). Os marcos da D5 ficam em colunas proprias.
            $table->unsignedBigInteger('d5_fiscalization_production_id')->nullable()->after('measurement_production_id');
            $table->string('d5_fiscal_user_name')->nullable()->after('d5_fiscalization_production_id');
            $table->timestamp('d5_fiscalization_entered_at')->nullable()->after('d5_fiscal_user_name');
            $table->timestamp('d5_fiscalization_completed_at')->nullable()->after('d5_fiscalization_entered_at');
            $table->unsignedBigInteger('d5_measurement_production_id')->nullable()->after('d5_fiscalization_completed_at');
            $table->string('d5_payment_user_name')->nullable()->after('d5_measurement_production_id');
            $table->timestamp('d5_measurement_entered_at')->nullable()->after('d5_payment_user_name');
            $table->timestamp('d5_measurement_completed_at')->nullable()->after('d5_measurement_entered_at');
            $table->timestamp('d5_measurement_exited_at')->nullable()->after('d5_measurement_completed_at');
            // Somente o informe final segue o prazo regulatorio (encerra na medicao do ciclo 1);
            // informe parcial grava false.
            $table->boolean('regulatory_deadline_applies')->default(true)->after('d5_measurement_exited_at');
        });
    }

    public function down(): void
    {
        Schema::table('note_inform_flows', function (Blueprint $table): void {
            $table->dropColumn([
                'd5_fiscalization_production_id',
                'd5_fiscal_user_name',
                'd5_fiscalization_entered_at',
                'd5_fiscalization_completed_at',
                'd5_measurement_production_id',
                'd5_payment_user_name',
                'd5_measurement_entered_at',
                'd5_measurement_completed_at',
                'd5_measurement_exited_at',
                'regulatory_deadline_applies',
            ]);
        });
    }
};
