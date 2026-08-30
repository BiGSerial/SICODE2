<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('work_reports', 'selected_final_scopes')) {
                $table->json('selected_final_scopes')->nullable()->after('acceptance_meta');
            }
        });

        Schema::table('note_inform_flows', function (Blueprint $table) {
            if (!Schema::hasColumn('note_inform_flows', 'final_scope')) {
                $table->string('final_scope', 30)->default('general')->after('inform_type');
            }

            if (!Schema::hasColumn('note_inform_flows', 'final_scope_resolution')) {
                $table->string('final_scope_resolution', 80)->nullable()->after('final_scope');
            }

            if (!Schema::hasColumn('note_inform_flows', 'final_scope_orders')) {
                $table->json('final_scope_orders')->nullable()->after('final_scope_resolution');
            }

            if (!Schema::hasColumn('note_inform_flows', 'publication_required')) {
                $table->boolean('publication_required')->default(true)->after('publication_validated_at');
            }

            if (!Schema::hasColumn('note_inform_flows', 'publication_policy')) {
                $table->string('publication_policy', 40)->default('required')->after('publication_required');
            }

            $table->index(['flow_type', 'final_scope', 'active'], 'nif_flow_scope_active_idx');
            $table->index(['final_scope', 'publication_required', 'active'], 'nif_scope_publication_idx');
        });

        Schema::table('work_report_flow_productions', function (Blueprint $table) {
            if (!Schema::hasColumn('work_report_flow_productions', 'final_scope')) {
                $table->string('final_scope', 30)->default('general')->after('stage');
            }

            if (!Schema::hasColumn('work_report_flow_productions', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('linked_by');
            }

            if (!Schema::hasColumn('work_report_flow_productions', 'reversed_by')) {
                $table->foreignUuid('reversed_by')->nullable()->after('reversed_at')->constrained('users');
            }

            if (!Schema::hasColumn('work_report_flow_productions', 'reverse_reason')) {
                $table->string('reverse_reason', 500)->nullable()->after('reversed_by');
            }
        });

        Schema::table('work_report_flow_productions', function (Blueprint $table) {
            $table->dropUnique('wr_flow_prod_unique');
            $table->unique(
                ['work_report_id', 'production_id', 'stage', 'final_scope'],
                'wr_flow_prod_scope_unique'
            );
            $table->index(['work_report_id', 'stage', 'final_scope', 'is_current'], 'wr_flow_prod_scope_current_idx');
        });
    }

    public function down(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            if (Schema::hasColumn('work_reports', 'selected_final_scopes')) {
                $table->dropColumn('selected_final_scopes');
            }
        });

        Schema::table('work_report_flow_productions', function (Blueprint $table) {
            $table->dropIndex('wr_flow_prod_scope_current_idx');
            $table->dropUnique('wr_flow_prod_scope_unique');
            $table->unique(['work_report_id', 'production_id', 'stage'], 'wr_flow_prod_unique');
        });

        Schema::table('work_report_flow_productions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn(['final_scope', 'reversed_at', 'reverse_reason']);
        });

        Schema::table('note_inform_flows', function (Blueprint $table) {
            $table->dropIndex('nif_flow_scope_active_idx');
            $table->dropIndex('nif_scope_publication_idx');
            $table->dropColumn([
                'final_scope',
                'final_scope_resolution',
                'final_scope_orders',
                'publication_required',
                'publication_policy',
            ]);
        });
    }
};
