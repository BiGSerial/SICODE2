<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cancellation_request_work_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cancellation_request_id');
            $table->unsignedBigInteger('work_report_id');
            $table->timestamps();

            $table->unique(['cancellation_request_id', 'work_report_id'], 'cxl_req_work_report_unique');
            $table->index('work_report_id', 'cxl_req_wr_work_report_idx');

            $table->foreign('cancellation_request_id', 'cxl_req_wr_request_fk')
                ->references('id')->on('cancellation_requests')->cascadeOnDelete();
            $table->foreign('work_report_id', 'cxl_req_wr_work_report_fk')
                ->references('id')->on('work_reports');
        });

        Schema::create('uncancellation_request_work_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uncancellation_request_id');
            $table->unsignedBigInteger('work_report_id');
            $table->timestamps();

            $table->unique(['uncancellation_request_id', 'work_report_id'], 'uncxl_req_work_report_unique');
            $table->index('work_report_id', 'uncxl_req_wr_work_report_idx');

            $table->foreign('uncancellation_request_id', 'uncxl_req_wr_request_fk')
                ->references('id')->on('uncancellation_requests')->cascadeOnDelete();
            $table->foreign('work_report_id', 'uncxl_req_wr_work_report_fk')
                ->references('id')->on('work_reports');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uncancellation_request_work_reports');
        Schema::dropIfExists('cancellation_request_work_reports');
    }
};
