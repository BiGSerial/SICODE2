<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('cancellation_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('cancellation_requests', 'requires_engineer_approval')) {
                $table->boolean('requires_engineer_approval')->default(false)->after('status');
            }
            if (!Schema::hasColumn('cancellation_requests', 'engineer_approval_status')) {
                $table->string('engineer_approval_status')->nullable()->after('requires_engineer_approval');
            }
            if (!Schema::hasColumn('cancellation_requests', 'engineer_approval_requested_by')) {
                $table->foreignUuid('engineer_approval_requested_by')->nullable()->after('engineer_approval_status')->constrained('users');
            }
            if (!Schema::hasColumn('cancellation_requests', 'engineer_approval_requested_at')) {
                $table->timestamp('engineer_approval_requested_at')->nullable()->after('engineer_approval_requested_by');
            }
            if (!Schema::hasColumn('cancellation_requests', 'engineer_approver_id')) {
                $table->foreignUuid('engineer_approver_id')->nullable()->after('engineer_approval_requested_at')->constrained('users');
            }
            if (!Schema::hasColumn('cancellation_requests', 'engineer_approval_decided_by')) {
                $table->foreignUuid('engineer_approval_decided_by')->nullable()->after('engineer_approver_id')->constrained('users');
            }
            if (!Schema::hasColumn('cancellation_requests', 'engineer_approval_decided_at')) {
                $table->timestamp('engineer_approval_decided_at')->nullable()->after('engineer_approval_decided_by');
            }
            if (!Schema::hasColumn('cancellation_requests', 'engineer_approval_reason')) {
                $table->text('engineer_approval_reason')->nullable()->after('engineer_approval_decided_at');
            }
        });

        Schema::table('cancellation_requests', function (Blueprint $table) {
            if (!collect(Schema::getIndexes('cancellation_requests'))->has('cxl_req_eng_status_approver_idx')) {
                $table->index(['engineer_approval_status', 'engineer_approver_id'], 'cxl_req_eng_status_approver_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cancellation_requests', function (Blueprint $table) {
            if (collect(Schema::getIndexes('cancellation_requests'))->has('cxl_req_eng_status_approver_idx')) {
                $table->dropIndex('cxl_req_eng_status_approver_idx');
            }
            if (Schema::hasColumn('cancellation_requests', 'engineer_approval_requested_by')) {
                $table->dropConstrainedForeignId('engineer_approval_requested_by');
            }
            if (Schema::hasColumn('cancellation_requests', 'engineer_approver_id')) {
                $table->dropConstrainedForeignId('engineer_approver_id');
            }
            if (Schema::hasColumn('cancellation_requests', 'engineer_approval_decided_by')) {
                $table->dropConstrainedForeignId('engineer_approval_decided_by');
            }

            $toDrop = array_values(array_filter([
                Schema::hasColumn('cancellation_requests', 'requires_engineer_approval') ? 'requires_engineer_approval' : null,
                Schema::hasColumn('cancellation_requests', 'engineer_approval_status') ? 'engineer_approval_status' : null,
                Schema::hasColumn('cancellation_requests', 'engineer_approval_requested_at') ? 'engineer_approval_requested_at' : null,
                Schema::hasColumn('cancellation_requests', 'engineer_approval_decided_at') ? 'engineer_approval_decided_at' : null,
                Schema::hasColumn('cancellation_requests', 'engineer_approval_reason') ? 'engineer_approval_reason' : null,
            ]));

            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
