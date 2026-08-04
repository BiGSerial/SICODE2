<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_report_flow_productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_report_id')->constrained('work_reports')->cascadeOnDelete();
            $table->foreignId('production_id')->constrained('productions')->cascadeOnDelete();
            $table->string('stage', 40);
            $table->boolean('is_current')->default(true);
            $table->timestamp('linked_at')->nullable();
            $table->foreignUuid('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 80)->default('dispatch');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['work_report_id', 'production_id', 'stage'], 'wr_flow_prod_unique');
            $table->index(['work_report_id', 'stage', 'is_current'], 'wr_flow_prod_current_idx');
            $table->index(['production_id', 'stage'], 'wr_flow_prod_production_stage_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_report_flow_productions');
    }
};
