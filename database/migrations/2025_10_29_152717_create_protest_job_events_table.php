<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('protest_job_events', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('protest_job_id')
                ->constrained('protest_jobs')
                ->cascadeOnDelete();

            $table->string('type'); // created, reassigned, file_uploaded, sla_breach, closed, reopened, etc.

            // quem realizou (UUID de users); pode ser null p/ eventos automáticos
            $table->foreignUuid('actor_id')->nullable()->constrained('users');

            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['protest_job_id','occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protest_job_events');
    }
};
