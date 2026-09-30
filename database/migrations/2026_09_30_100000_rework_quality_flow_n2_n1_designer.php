<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('quality_processes', function (Blueprint $table) {
            $table->string('state', 32)->default('AWAITING_N1_DISPATCH')->after('status');
            $table->string('phase', 16)->default('PROJECT')->after('state');
            $table->unsignedInteger('round_number')->default(1)->after('phase');
            $table->foreignUuid('n1_user_id')->nullable()->after('company_id')->constrained('users')->nullOnDelete();
            $table->foreignUuid('current_designer_id')->nullable()->after('original_designer_id')->constrained('users')->nullOnDelete();
            $table->index(['company_id', 'status', 'state', 'phase'], 'quality_processes_queue_idx');
        });

        Schema::table('quality_stages', function (Blueprint $table) {
            $table->string('kind', 16)->default('EXECUTION')->after('level');
            $table->foreignUuid('dispatched_by')->nullable()->after('assigned_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable()->after('dispatched_by');
            $table->foreignUuid('executed_by')->nullable()->after('dispatched_at')->constrained('users')->nullOnDelete();
            $table->index('quality_process_id', 'quality_stages_process_idx');
        });

        Schema::table('quality_stages', function (Blueprint $table) {
            $table->dropUnique('quality_stage_round_level_unique');
            $table->unique(['quality_process_id', 'type', 'level', 'kind', 'round_number'], 'quality_stage_round_kind_unique');
        });

        Schema::table('quality_events', function (Blueprint $table) {
            $table->string('actor_role', 16)->nullable()->after('actor_id');
            $table->foreignUuid('target_user_id')->nullable()->after('designer_id')->constrained('users')->nullOnDelete();
            $table->string('from_state', 32)->nullable()->after('type');
            $table->string('to_state', 32)->nullable()->after('from_state');
        });

        Schema::table('quality_rejections', function (Blueprint $table) {
            $table->string('returned_to_level', 16)->nullable()->after('level');
        });

        Schema::create('quality_sap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_process_id')->constrained('quality_processes')->cascadeOnDelete();
            $table->foreignId('production_id')->constrained('productions')->cascadeOnDelete();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignUuid('requested_by')->constrained('users');
            $table->string('driver', 40);
            $table->string('previous_status', 40)->nullable();
            $table->string('target_status', 40)->nullable();
            $table->string('status', 16)->default('PENDING');
            $table->unsignedInteger('attempt')->default(1);
            $table->json('response')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['quality_process_id', 'status']);
        });

        $this->backfill();
    }

    /** Processos criados pela primeira versão (fluxo N2 → empresa) são mapeados para o novo modelo de estados. */
    private function backfill(): void
    {
        DB::table('quality_stages')->where('level', 'N1')->update(['kind' => 'REVIEW']);
        DB::table('quality_stages')->where('level', 'N2')->update(['kind' => 'REVIEW']);

        foreach (DB::table('quality_processes')->get() as $process) {
            $stage = DB::table('quality_stages')->where('quality_process_id', $process->id)->orderByDesc('id')->first();
            $state = match (true) {
                $process->status === 'COMPLETED'      => 'COMPLETED',
                $process->current_stage === 'DRAWING' => 'AWAITING_DESIGNER',
                $process->current_stage === 'N2'      => 'AWAITING_N2_REVIEW',
                default                               => 'AWAITING_N1_REVIEW',
            };

            DB::table('quality_processes')->where('id', $process->id)->update([
                'state'               => $state,
                'phase'               => $stage->type ?? 'PROJECT',
                'round_number'        => $stage->round_number ?? 1,
                'current_designer_id' => $process->original_designer_id,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_sap_requests');

        Schema::table('quality_rejections', fn (Blueprint $table) => $table->dropColumn('returned_to_level'));

        Schema::table('quality_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('target_user_id');
            $table->dropColumn(['actor_role', 'from_state', 'to_state']);
        });

        Schema::table('quality_stages', function (Blueprint $table) {
            $table->dropUnique('quality_stage_round_kind_unique');
            $table->unique(['quality_process_id', 'type', 'level', 'round_number'], 'quality_stage_round_level_unique');
        });

        Schema::table('quality_stages', function (Blueprint $table) {
            $table->dropIndex('quality_stages_process_idx');
            $table->dropConstrainedForeignId('dispatched_by');
            $table->dropConstrainedForeignId('executed_by');
            $table->dropColumn(['kind', 'dispatched_at']);
        });

        Schema::table('quality_processes', function (Blueprint $table) {
            $table->dropIndex('quality_processes_queue_idx');
            $table->dropConstrainedForeignId('n1_user_id');
            $table->dropConstrainedForeignId('current_designer_id');
            $table->dropColumn(['state', 'phase', 'round_number']);
        });
    }
};
