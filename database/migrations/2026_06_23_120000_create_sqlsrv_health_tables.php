<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sqlsrv_health_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('connection_name', 80)->default('sqlsrv1');
            $table->string('database_name', 120)->default('edp-depc');
            $table->timestamp('collected_at')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('status', 30)->default('success')->index();
            $table->unsignedInteger('job_logs_count')->default(0);
            $table->unsignedInteger('metrics_count')->default(0);
            $table->text('error_message')->nullable();
            $table->json('collection_errors')->nullable();
            $table->timestamps();

            $table->index(['connection_name', 'collected_at'], 'idx_sqlsrv_health_conn_collected');
        });

        Schema::create('sqlsrv_job_log_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('sqlsrv_health_snapshots')->cascadeOnDelete();
            $table->string('file_name', 255)->nullable();
            $table->string('file_folder', 255)->nullable();
            $table->timestamp('dt_run')->nullable()->index();
            $table->timestamp('dt_last_date')->nullable();
            $table->string('host', 120)->nullable();
            $table->string('status', 80)->nullable()->index();
            $table->longText('error')->nullable();
            $table->string('error_hash', 64)->nullable()->index();
            $table->boolean('has_error')->default(false)->index();
            $table->timestamps();

            $table->index(['snapshot_id', 'file_name'], 'idx_sqlsrv_job_snapshot_file');
            $table->index(['file_name', 'dt_run'], 'idx_sqlsrv_job_file_run');
            $table->index(['has_error', 'dt_run'], 'idx_sqlsrv_job_error_run');
        });

        Schema::create('sqlsrv_source_metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('sqlsrv_health_snapshots')->cascadeOnDelete();
            $table->string('source_name', 120);
            $table->unsignedBigInteger('row_count')->nullable();
            $table->timestamp('last_update_at')->nullable()->index();
            $table->timestamp('first_update_at')->nullable();
            $table->string('max_reference_value', 120)->nullable();
            $table->json('metric_payload')->nullable();
            $table->timestamps();

            $table->index(['source_name', 'created_at'], 'idx_sqlsrv_metric_source_created');
            $table->index(['source_name', 'last_update_at'], 'idx_sqlsrv_metric_source_last');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sqlsrv_source_metric_snapshots');
        Schema::dropIfExists('sqlsrv_job_log_snapshots');
        Schema::dropIfExists('sqlsrv_health_snapshots');
    }
};
