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
            $table->string('source_type')->nullable();
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
            $table->string('case_number_normalized')->nullable()->index();
            $table->string('process_number')->nullable();
            $table->string('process_number_normalized')->nullable()->index();
            $table->string('process_number_core')->nullable()->index();
            $table->string('company_name')->nullable();
            $table->string('external_status')->nullable();
            $table->string('legal_responsible_name')->nullable();
            $table->string('law_firm_name')->nullable();
            $table->string('main_origin_area')->nullable();
            $table->string('identity_key', 64)->nullable()->index();
            $table->string('identity_strategy')->nullable();
            $table->unsignedTinyInteger('identity_confidence')->default(0);
            $table->json('sources_seen')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->foreignId('last_import_batch_id')->nullable()->constrained('legal_import_batches')->nullOnDelete();
            $table->timestamps();

            $table->index(['case_number_normalized', 'process_number_core'], 'idx_legal_cases_case_core');
            // Unique em identity_key deve ser avaliado somente em etapa posterior.
        });

        Schema::create('legal_demands', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('legal_case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->string('source_type', 50);
            $table->string('source_external_id')->nullable();
            $table->string('source_case_number')->nullable();
            $table->string('source_case_number_normalized')->nullable()->index();
            $table->string('source_process_number')->nullable();
            $table->string('source_process_number_normalized')->nullable()->index();
            $table->string('source_process_number_core')->nullable()->index();
            $table->string('source_entity_key', 64)->nullable()->index();
            $table->string('source_occurrence_key', 64)->nullable()->index();
            $table->string('source_hash', 64)->nullable()->index();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('subject')->nullable();
            $table->string('service_type')->nullable();
            $table->string('external_status')->nullable();
            $table->string('external_flow_status')->nullable();
            $table->string('origin_area_name')->nullable();
            $table->string('target_area_name')->nullable();
            $table->string('target_person_name')->nullable();
            $table->string('requesting_responsible_name')->nullable();
            $table->string('responsible_area_name')->nullable();
            $table->string('opposing_party')->nullable();
            $table->string('process_manager')->nullable();
            $table->string('required_area')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('regional')->nullable();
            $table->timestamp('source_analysis_at')->nullable();
            $table->timestamp('source_started_at')->nullable();
            $table->timestamp('source_due_at')->nullable();
            $table->timestamp('source_executed_at')->nullable();
            $table->timestamp('source_changed_at')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('missing_since')->nullable();
            $table->unsignedInteger('missing_count')->default(0);
            $table->string('source_presence_status')->default('present');
            $table->string('internal_status')->default('new_imported');
            $table->string('priority')->nullable();
            $table->string('risk_level')->nullable();
            $table->uuid('controller_user_id')->nullable();
            $table->foreign('controller_user_id')->references('id')->on('users')->nullOnDelete();
            $table->uuid('current_assigned_user_id')->nullable();
            $table->foreign('current_assigned_user_id')->references('id')->on('users')->nullOnDelete();
            $table->uuid('current_assigned_team_id')->nullable();
            $table->foreignId('last_seen_import_batch_id')->nullable()->constrained('legal_import_batches')->nullOnDelete();
            $table->foreignId('last_missing_batch_id')->nullable()->constrained('legal_import_batches')->nullOnDelete();
            $table->foreignId('last_returned_batch_id')->nullable()->constrained('legal_import_batches')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->uuid('closed_by')->nullable();
            $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
            $table->text('closure_reason')->nullable();
            $table->timestamp('external_closed_at')->nullable();
            $table->string('external_protocol')->nullable();
            $table->text('external_closure_note')->nullable();
            $table->boolean('needs_identity_review')->default(false);
            $table->string('source_identity_strategy')->nullable();
            $table->unsignedTinyInteger('source_identity_confidence')->default(0);
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_case_number_normalized'], 'idx_demands_source_case');
            $table->index(['source_type', 'source_process_number_core'], 'idx_demands_source_core');
            $table->index(['source_presence_status', 'missing_since'], 'idx_demands_presence');
            $table->index(['internal_status', 'source_due_at'], 'idx_demands_status_due');
            // Unique em source_occurrence_key deve ser avaliado somente em etapa posterior.
        });

        Schema::create('legal_source_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->cascadeOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained('legal_import_batches')->nullOnDelete();
            $table->string('source_type', 50);
            $table->string('source_external_id')->nullable();
            $table->string('source_case_number_normalized')->nullable()->index();
            $table->string('source_process_number_core')->nullable()->index();
            $table->string('source_entity_key', 64)->nullable()->index();
            $table->string('source_occurrence_key', 64)->nullable()->index();
            $table->string('source_hash', 64)->nullable()->index();
            $table->json('raw_payload')->nullable();
            $table->json('normalized_payload')->nullable();
            $table->json('changed_fields')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('legal_demand_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable();
            $table->foreignId('import_batch_id')->nullable()->constrained('legal_import_batches')->nullOnDelete();
            $table->string('event_type');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();
            $table->uuid('target_user_id')->nullable();
            $table->foreign('target_user_id')->references('id')->on('users')->nullOnDelete();
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
            $table->foreign('from_user_id')->references('id')->on('users')->nullOnDelete();
            $table->uuid('to_user_id')->nullable();
            $table->foreign('to_user_id')->references('id')->on('users')->nullOnDelete();
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
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->string('file_name');
            $table->string('original_name')->nullable();
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('visibility')->default('internal');
            $table->timestamp('removed_at')->nullable();
            $table->uuid('removed_by')->nullable();
            $table->foreign('removed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('legal_demand_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
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
