<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_demands', function (Blueprint $table) {
            $table->timestamp('operator_sla_due_at')->nullable()->after('source_due_at');
            $table->text('operator_sla_note')->nullable()->after('operator_sla_due_at');
            $table->string('resolution_scope', 32)->default('external_compatible')->after('operator_sla_note');

            $table->index('operator_sla_due_at');
            $table->index('resolution_scope');
        });
    }

    public function down(): void
    {
        Schema::table('legal_demands', function (Blueprint $table) {
            $table->dropColumn(['operator_sla_due_at', 'operator_sla_note', 'resolution_scope']);
        });
    }
};
