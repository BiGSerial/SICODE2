<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            if (!$this->hasIndex('notes', 'idx_notes_dt_status')) {
                $table->index('dt_status', 'idx_notes_dt_status');
            }
        });

        Schema::table('wpas', function (Blueprint $table) {
            if (!$this->hasIndex('wpas', 'idx_wpas_note_service')) {
                $table->index(['note_id', 'service_id'], 'idx_wpas_note_service');
            }
        });

        Schema::table('five_notes', function (Blueprint $table) {
            if (!$this->hasIndex('five_notes', 'idx_five_notes_supervision_completed_note')) {
                $table->index(['is_supervisioned', 'is_completed', 'note_id'], 'idx_five_notes_supervision_completed_note');
            }
        });

        Schema::table('work_reports', function (Blueprint $table) {
            if (!$this->hasIndex('work_reports', 'idx_wr_canceled_rejected_note_created')) {
                $table->index(['canceled', 'rejected', 'note_id', 'created_at', 'id'], 'idx_wr_canceled_rejected_note_created');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!$this->hasIndex('orders', 'idx_orders_status_id')) {
                $table->index(['statusSist', 'id'], 'idx_orders_status_id');
            }
        });

        Schema::table('operations', function (Blueprint $table) {
            if (!$this->hasIndex('operations', 'idx_operations_order_operacao_status')) {
                $table->index(['order_id', 'operacao', 'status'], 'idx_operations_order_operacao_status');
            }
        });

        Schema::table('order_work_report', function (Blueprint $table) {
            if (!$this->hasIndex('order_work_report', 'idx_owr_order_work_report')) {
                $table->index(['order_id', 'work_report_id'], 'idx_owr_order_work_report');
            }

            if (!$this->hasIndex('order_work_report', 'idx_owr_work_report_order')) {
                $table->index(['work_report_id', 'order_id'], 'idx_owr_work_report_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_work_report', function (Blueprint $table) {
            if ($this->hasIndex('order_work_report', 'idx_owr_work_report_order')) {
                $table->dropIndex('idx_owr_work_report_order');
            }

            if ($this->hasIndex('order_work_report', 'idx_owr_order_work_report')) {
                $table->dropIndex('idx_owr_order_work_report');
            }
        });

        Schema::table('operations', function (Blueprint $table) {
            if ($this->hasIndex('operations', 'idx_operations_order_operacao_status')) {
                $table->dropIndex('idx_operations_order_operacao_status');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if ($this->hasIndex('orders', 'idx_orders_status_id')) {
                $table->dropIndex('idx_orders_status_id');
            }
        });

        Schema::table('work_reports', function (Blueprint $table) {
            if ($this->hasIndex('work_reports', 'idx_wr_canceled_rejected_note_created')) {
                $table->dropIndex('idx_wr_canceled_rejected_note_created');
            }
        });

        Schema::table('five_notes', function (Blueprint $table) {
            if ($this->hasIndex('five_notes', 'idx_five_notes_supervision_completed_note')) {
                $table->dropIndex('idx_five_notes_supervision_completed_note');
            }
        });

        Schema::table('wpas', function (Blueprint $table) {
            if ($this->hasIndex('wpas', 'idx_wpas_note_service')) {
                $table->dropIndex('idx_wpas_note_service');
            }
        });

        Schema::table('notes', function (Blueprint $table) {
            if ($this->hasIndex('notes', 'idx_notes_dt_status')) {
                $table->dropIndex('idx_notes_dt_status');
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))->isNotEmpty();
    }
};
