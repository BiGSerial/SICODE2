<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 50)->nullable();
            $table->string('source_table')->nullable();
            $table->string('source_version')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('new_rows')->default(0);
            $table->unsignedInteger('updated_rows')->default(0);
            $table->unsignedInteger('unchanged_rows')->default(0);
            $table->unsignedInteger('missing_rows')->default(0);
            $table->unsignedInteger('returned_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->string('status')->default('running');
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('source_type');
            $table->index('status');
            $table->index('started_at');
            $table->index('finished_at');
        });

        Schema::create('legal_cases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('case_number')->nullable();
            $table->string('case_number_normalized')->nullable();

            $table->string('process_number')->nullable();
            $table->string('process_number_normalized')->nullable();

            $table->string('installation_number')->nullable();
            $table->string('installation_number_normalized')->nullable();

            $table->string('process_status')->nullable();
            $table->string('district')->nullable();
            $table->string('company_name')->nullable();
            $table->string('process_manager')->nullable();
            $table->string('law_firm')->nullable();
            $table->string('process_nature')->nullable();
            $table->string('process_cause')->nullable();

            $table->string('identity_key', 64)->nullable();
            $table->string('identity_strategy')->nullable();
            $table->string('identity_confidence')->nullable();

            $table->json('sources_seen')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->foreignId('last_import_batch_id')->nullable()->constrained('legal_import_batches');

            $table->json('raw_latest_payload')->nullable();
            $table->json('normalized_latest_payload')->nullable();

            $table->timestamps();

            $table->unique('identity_key');
            $table->index('case_number_normalized');
            $table->index('process_number_normalized');
            $table->index('company_name');
            $table->index('process_status');
            $table->index('last_seen_at');
        });

        Schema::create('legal_demands', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('legal_case_id')->constrained('legal_cases')->cascadeOnDelete();

            $table->string('source_type', 50);
            $table->string('source_table')->nullable();
            $table->string('source_version')->nullable();

            $table->string('source_record_key', 64)->nullable();
            $table->string('source_record_key_strategy')->nullable();
            $table->string('source_record_key_confidence')->nullable();
            $table->string('source_entity_key', 64)->nullable();
            $table->string('source_hash', 64)->nullable();
            $table->boolean('needs_identity_review')->default(false);

            $table->string('source_case_number')->nullable();
            $table->string('source_process_number')->nullable();
            $table->string('source_installation_number')->nullable();

            $table->text('source_subject')->nullable();
            $table->text('source_description')->nullable();
            $table->string('source_status')->nullable();
            $table->timestamp('source_status_at')->nullable();
            $table->string('source_status_group')->default('unknown');
            $table->boolean('needs_status_review')->default(false);

            $table->string('process_status_at_import')->nullable();

            $table->string('requesting_area_name')->nullable();
            $table->string('requesting_responsible_name')->nullable();
            $table->string('responsible_area_name')->nullable();
            $table->string('delegated_responsible_name')->nullable();
            $table->string('delegated_by_name')->nullable();
            $table->timestamp('delegated_at')->nullable();

            $table->timestamp('source_due_at')->nullable();
            $table->timestamp('source_decision_at')->nullable();
            $table->timestamp('source_end_at')->nullable();

            $table->string('title')->nullable();
            $table->text('summary')->nullable();

            $table->string('priority')->nullable();
            $table->string('risk_level')->nullable();

            $table->string('source_presence_status')->default('present');
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('missing_since')->nullable();
            $table->unsignedInteger('missing_count')->default(0);

            $table->string('internal_status')->default('new_imported');
            $table->string('action_state')->default('waiting_controller_triage');

            $table->uuid('controller_user_id')->nullable();
            $table->foreign('controller_user_id')->references('id')->on('users');
            $table->uuid('current_assigned_user_id')->nullable();
            $table->foreign('current_assigned_user_id')->references('id')->on('users');
            $table->uuid('current_assigned_team_id')->nullable();

            $table->timestamp('closed_at')->nullable();
            $table->uuid('closed_by')->nullable();
            $table->foreign('closed_by')->references('id')->on('users');
            $table->text('closure_reason')->nullable();

            $table->timestamp('external_closed_at')->nullable();
            $table->string('external_protocol')->nullable();
            $table->text('external_closure_note')->nullable();

            $table->json('raw_payload')->nullable();
            $table->json('normalized_payload')->nullable();
            $table->json('source_specific_payload')->nullable();

            $table->foreignId('last_seen_import_batch_id')->nullable()->constrained('legal_import_batches');
            $table->foreignId('last_missing_batch_id')->nullable()->constrained('legal_import_batches');
            $table->foreignId('last_returned_batch_id')->nullable()->constrained('legal_import_batches');

            $table->timestamps();

            $table->unique(['source_type', 'source_record_key'], 'uq_legal_demands_source_record_key');
            $table->index(['source_type', 'source_entity_key'], 'idx_legal_demands_source_entity');
            $table->index(['source_type', 'source_hash'], 'idx_legal_demands_source_hash');
            $table->index(['source_status_group', 'action_state'], 'idx_legal_demands_action');
            $table->index(['internal_status', 'source_due_at'], 'idx_legal_demands_status_due');
            $table->index(['source_presence_status', 'missing_since'], 'idx_legal_demands_presence');
        });

        Schema::create('legal_source_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->cascadeOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained('legal_import_batches');
            $table->string('source_type', 50);
            $table->string('source_record_key', 64)->nullable();
            $table->string('source_hash', 64)->nullable();
            $table->json('raw_payload')->nullable();
            $table->json('normalized_payload')->nullable();
            $table->json('source_specific_payload')->nullable();
            $table->json('changed_fields')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_record_key'], 'idx_legal_snapshots_source_record');
            $table->index(['source_type', 'source_hash'], 'idx_legal_snapshots_source_hash');
            $table->index('seen_at');
        });

        Schema::create('legal_demand_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable();
            $table->foreignId('import_batch_id')->nullable()->constrained('legal_import_batches');
            $table->string('event_type');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->foreign('actor_user_id')->references('id')->on('users');
            $table->uuid('target_user_id')->nullable();
            $table->foreign('target_user_id')->references('id')->on('users');
            $table->uuid('target_team_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'occurred_at']);
            $table->index(['import_batch_id', 'event_type']);
        });

        Schema::create('legal_demand_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->cascadeOnDelete();
            $table->uuid('from_user_id')->nullable();
            $table->foreign('from_user_id')->references('id')->on('users');
            $table->uuid('to_user_id')->nullable();
            $table->foreign('to_user_id')->references('id')->on('users');
            $table->uuid('to_team_id')->nullable();
            $table->string('status')->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->text('message')->nullable();
            $table->text('answer')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('legal_demand_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable();
            $table->uuid('uploaded_by')->nullable();
            $table->foreign('uploaded_by')->references('id')->on('users');
            $table->string('file_name');
            $table->string('original_name')->nullable();
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('visibility')->default('internal');
            $table->timestamp('removed_at')->nullable();
            $table->uuid('removed_by')->nullable();
            $table->foreign('removed_by')->references('id')->on('users');
            $table->timestamps();
        });

        Schema::create('legal_demand_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->text('comment');
            $table->string('visibility')->default('internal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_demand_comments');
        Schema::dropIfExists('legal_demand_files');
        Schema::dropIfExists('legal_demand_assignments');
        Schema::dropIfExists('legal_demand_events');
        Schema::dropIfExists('legal_source_snapshots');
        Schema::dropIfExists('legal_demands');
        Schema::dropIfExists('legal_cases');
        Schema::dropIfExists('legal_import_batches');
    }
};
