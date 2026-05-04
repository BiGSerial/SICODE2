<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_hash', 64)->index();
            $table->string('command_label', 255)->index();
            $table->text('command');
            $table->string('expression', 120)->index();
            $table->string('status', 20)->index();
            $table->dateTime('scheduled_at')->nullable()->index();
            $table->dateTime('started_at')->nullable()->index();
            $table->dateTime('finished_at')->nullable()->index();
            $table->decimal('duration_seconds', 10, 2)->nullable();
            $table->integer('exit_code')->nullable();
            $table->text('exception_message')->nullable();
            $table->text('skip_reason')->nullable();
            $table->string('output_path')->nullable();
            $table->boolean('without_overlapping')->default(false);
            $table->boolean('run_in_background')->default(false);
            $table->timestamps();

            $table->index(['event_hash', 'status', 'started_at'], 'idx_schedule_event_status_start');
            $table->index(['status', 'scheduled_at'], 'idx_schedule_status_scheduled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_execution_logs');
    }
};
