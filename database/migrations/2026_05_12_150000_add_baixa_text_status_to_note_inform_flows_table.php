<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('note_inform_flows', function (Blueprint $table) {
            $table->string('baixa_fiscal_status', 20)
                ->nullable()
                ->after('fiscalization_closed_in_sap_at');

            $table->string('baixa_measurement_status', 20)
                ->nullable()
                ->after('measurement_exited_at');

            $table->index('baixa_fiscal_status');
            $table->index('baixa_measurement_status');
        });
    }

    public function down(): void
    {
        Schema::table('note_inform_flows', function (Blueprint $table) {
            $table->dropIndex(['baixa_fiscal_status']);
            $table->dropIndex(['baixa_measurement_status']);
            $table->dropColumn(['baixa_fiscal_status', 'baixa_measurement_status']);
        });
    }
};

