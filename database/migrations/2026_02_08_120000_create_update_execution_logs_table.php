<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('update_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->string('task', 120)->index();
            $table->string('status', 20)->index(); // RUNNING | DONE | FAIL
            $table->json('options')->nullable();
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('updated')->default(0);
            $table->unsignedBigInteger('created')->default(0);
            $table->unsignedBigInteger('noteupdated')->nullable();
            $table->unsignedInteger('erros')->default(0);
            $table->json('errosMSGs')->nullable();
            $table->text('fail_reason')->nullable();
            $table->dateTime('date_inicio')->index();
            $table->dateTime('date_fim')->nullable()->index();
            $table->dateTime('failed_at')->nullable();
            $table->timestamps();

            $table->index(['task', 'status', 'date_inicio'], 'idx_update_exec_task_status_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('update_execution_logs');
    }
};
