<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_cases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('process_number');
            $table->string('process_number_normalized');
            $table->string('company_name');
            $table->string('external_status')->nullable();
            $table->string('legal_responsible_name')->nullable();
            $table->string('law_firm_name')->nullable();
            $table->string('main_origin_area')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['process_number_normalized', 'company_name'], 'legal_cases_process_company_unique');
            $table->index('external_status');
            $table->index('company_name');
            $table->index('last_seen_at');
        });

        Schema::create('legal_demands', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('legal_case_id')->constrained('legal_cases')->restrictOnDelete();
            $table->string('source_type');
            $table->string('source_external_id')->nullable();
            $table->string('source_record_key');
            $table->string('source_hash')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('subject')->nullable();
            $table->string('service_type')->nullable();
            $table->string('external_status')->nullable();
            $table->string('external_flow_status')->nullable();
            $table->string('origin_area_name')->nullable();
            $table->string('target_area_name')->nullable();
            $table->string('target_person_name')->nullable();
            $table->timestamp('source_started_at')->nullable();
            $table->timestamp('source_due_at')->nullable();
            $table->timestamp('source_redirected_at')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('missing_since')->nullable();
            $table->string('source_presence_status')->nullable();
            $table->string('internal_status')->nullable();
            $table->string('priority')->nullable();
            $table->string('risk_level')->nullable();
            $table->uuid('controller_user_id')->nullable();
            $table->foreign('controller_user_id')->references('id')->on('users')->nullOnDelete();
            $table->uuid('current_assigned_user_id')->nullable();
            $table->foreign('current_assigned_user_id')->references('id')->on('users')->nullOnDelete();
            $table->unsignedBigInteger('current_assigned_team_id')->nullable();
            $table->uuid('closed_by')->nullable();
            $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('closure_reason')->nullable();
            $table->timestamp('external_closed_at')->nullable();
            $table->string('external_protocol')->nullable();
            $table->text('external_closure_note')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique('source_record_key');
            $table->index('source_type');
            $table->index('source_external_id');
            $table->index('source_hash');
            $table->index('internal_status');
            $table->index('source_presence_status');
            $table->index('source_due_at');
            $table->index('current_assigned_user_id');
            $table->index('current_assigned_team_id');
            $table->index('controller_user_id');
            $table->index('last_seen_at');
            $table->index('missing_since');
        });

        Schema::create('legal_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source_type');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('new_rows')->default(0);
            $table->unsignedInteger('updated_rows')->default(0);
            $table->unsignedInteger('unchanged_rows')->default(0);
            $table->unsignedInteger('missing_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('source_type');
            $table->index('status');
            $table->index('started_at');
            $table->index('finished_at');
        });

        Schema::create('legal_source_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->restrictOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained('legal_import_batches')->nullOnDelete();
            $table->string('source_type');
            $table->string('source_external_id')->nullable();
            $table->string('source_record_key')->nullable();
            $table->string('source_hash')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('legal_demand_id');
            $table->index('import_batch_id');
            $table->index('source_type');
            $table->index('source_external_id');
            $table->index('source_record_key');
            $table->index('source_hash');
            $table->index('seen_at');
        });

        Schema::create('legal_demand_assignments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->restrictOnDelete();
            $table->uuid('assigned_by_user_id')->nullable();
            $table->foreign('assigned_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->uuid('assigned_to_user_id')->nullable();
            $table->foreign('assigned_to_user_id')->references('id')->on('users')->nullOnDelete();
            $table->unsignedBigInteger('assigned_to_team_id')->nullable();
            $table->string('status');
            $table->text('message')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('response_summary')->nullable();
            $table->text('controller_review_note')->nullable();
            $table->timestamps();

            $table->index('legal_demand_id');
            $table->index('assigned_by_user_id');
            $table->index('assigned_to_user_id');
            $table->index('assigned_to_team_id');
            $table->index('status');
            $table->index('due_at');
            $table->index('sent_at');
            $table->index('answered_at');
        });

        Schema::create('legal_demand_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->restrictOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('legal_demand_assignments')->nullOnDelete();
            $table->string('event_type');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();
            $table->uuid('target_user_id')->nullable();
            $table->foreign('target_user_id')->references('id')->on('users')->nullOnDelete();
            $table->unsignedBigInteger('target_team_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('legal_demand_id');
            $table->index('assignment_id');
            $table->index('event_type');
            $table->index('actor_user_id');
            $table->index('target_user_id');
            $table->index('target_team_id');
            $table->index('occurred_at');
        });

        Schema::create('legal_demand_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->restrictOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('legal_demand_assignments')->nullOnDelete();
            $table->foreignId('file_id')->constrained('files')->restrictOnDelete();
            $table->uuid('uploaded_by_user_id')->nullable();
            $table->foreign('uploaded_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('category')->nullable();
            $table->string('visibility')->nullable();
            $table->boolean('can_be_sent_external')->default(false);
            $table->boolean('is_evidence')->default(false);
            $table->boolean('is_final_response')->default(false);
            $table->timestamps();

            $table->index('legal_demand_id');
            $table->index('assignment_id');
            $table->index('file_id');
            $table->index('uploaded_by_user_id');
            $table->index('category');
            $table->index('visibility');
            $table->index('is_evidence');
            $table->index('is_final_response');
        });

        Schema::create('legal_demand_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_demand_id')->constrained('legal_demands')->restrictOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('legal_demand_assignments')->nullOnDelete();
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->text('comment');
            $table->string('visibility')->nullable();
            $table->timestamps();

            $table->index('legal_demand_id');
            $table->index('assignment_id');
            $table->index('user_id');
            $table->index('visibility');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_demand_comments');
        Schema::dropIfExists('legal_demand_files');
        Schema::dropIfExists('legal_demand_events');
        Schema::dropIfExists('legal_demand_assignments');
        Schema::dropIfExists('legal_source_snapshots');
        Schema::dropIfExists('legal_import_batches');
        Schema::dropIfExists('legal_demands');
        Schema::dropIfExists('legal_cases');
    }
};
