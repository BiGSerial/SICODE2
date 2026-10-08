<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('note_inform_flows', function (Blueprint $table): void {
            $table->boolean('measurement_closed_in_sap')->default(false)->after('measurement_exited_at');
            $table->timestamp('measurement_closed_in_sap_at')->nullable()->after('measurement_closed_in_sap');
        });
    }

    public function down(): void
    {
        Schema::table('note_inform_flows', function (Blueprint $table): void {
            $table->dropColumn(['measurement_closed_in_sap', 'measurement_closed_in_sap_at']);
        });
    }
};
