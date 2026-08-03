<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table): void {
            $table->id();
            $table->string('external_id')->nullable();
            $table->char('state', 2);
            $table->unsignedSmallInteger('year');
            $table->date('date');
            $table->string('name');
            $table->string('type', 30)->nullable();
            $table->boolean('is_banking_holiday')->default(false);
            $table->string('source')->default('feriados_api');
            $table->json('source_payload')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->unique(['state', 'date']);
            $table->index(['state', 'year']);
        });

        Schema::create('ads_non_working_day_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_report_id')->constrained('work_reports')->cascadeOnDelete();
            $table->date('date');
            $table->text('reason');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['work_report_id', 'date'], 'ads_non_working_adjustments_unique');
            $table->index(['date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads_non_working_day_adjustments');
        Schema::dropIfExists('holidays');
    }
};
