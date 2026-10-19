<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * ESQUEMA LEGADO: o banco compartilhado/produção ainda tem closure_order_states / closure_order_events do
 * esquema antigo do Encerramento (migrations de 2026-09-02, removidas desta branch). Esta migration os
 * DESCARTA e recria no desenho novo. Decisão do usuário: o Encerramento ainda não é atividade em uso, então
 * os dados legados são descartados de propósito. Idempotente: tabelas já no esquema novo nunca são dropadas.
 */
return new class () extends Migration {
    public function up(): void
    {
        $this->dropLegacyTables();

        $createdStates = !Schema::hasTable('closure_order_states');
        $createdEvents = !Schema::hasTable('closure_order_events');

        if ($createdStates) {
            $this->createStates();
        }

        if ($createdEvents) {
            $this->createEvents();
        }

        if ($createdStates || $createdEvents) {
            Schema::table('closure_order_states', function (Blueprint $table) {
                $table->foreign('last_event_id')
                    ->references('id')
                    ->on('closure_order_events')
                    ->nullOnDelete();
            });
        }

        $this->createPeriods();
    }

    private function dropLegacyTables(): void
    {
        $legacyEvents = Schema::hasTable('closure_order_events') && !Schema::hasColumn('closure_order_events', 'closure_order_state_id');
        $legacyStates = Schema::hasTable('closure_order_states') && !Schema::hasColumn('closure_order_states', 'operational_status');

        if (!$legacyEvents && !$legacyStates) {
            return;
        }

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('closure_order_state_periods');
        Schema::dropIfExists('closure_order_events');
        Schema::dropIfExists('closure_order_states');
        Schema::enableForeignKeyConstraints();
    }

    private function createStates(): void
    {
        Schema::create('closure_order_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
            $table->foreignId('production_id')->constrained('productions')->restrictOnDelete();
            $table->string('operational_status')->default('AVAILABLE');
            $table->foreignUuid('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('current_holder_type')->nullable();
            $table->foreignUuid('current_holder_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('current_holder_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->timestamp('entered_current_status_at')->nullable();
            $table->unsignedBigInteger('last_event_id')->nullable();
            $table->string('last_status_sist_seen')->nullable();
            $table->timestamp('last_status_sist_checked_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancelled_reason')->nullable();
            $table->timestamps();

            $table->index(['production_id', 'operational_status'], 'closure_state_production_status_index');
            $table->index(['operational_status', 'entered_current_status_at'], 'closure_state_status_entered_index');
            $table->index(['current_holder_user_id', 'operational_status'], 'closure_state_holder_user_status_index');
            $table->index(['current_holder_company_id', 'operational_status'], 'closure_state_holder_company_status_index');
            $table->index(['assigned_user_id', 'operational_status'], 'closure_state_assignee_status_index');
        });
    }

    private function createEvents(): void
    {
        Schema::create('closure_order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('closure_order_state_id')->constrained('closure_order_states')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('origin');
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->json('meta')->nullable();
            $table->uuid('batch_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'created_at'], 'closure_order_event_order_created_index');
            $table->index(['closure_order_state_id', 'created_at'], 'closure_order_event_state_created_index');
            $table->index('batch_id', 'closure_order_event_batch_index');
        });
    }

    private function createPeriods(): void
    {
        if (Schema::hasTable('closure_order_state_periods')) {
            return;
        }

        Schema::create('closure_order_state_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('closure_order_state_id')->constrained('closure_order_states')->restrictOnDelete();
            $table->string('operational_status');
            $table->string('holder_type')->nullable();
            $table->foreignUuid('holder_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('holder_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('started_event_id')->constrained('closure_order_events')->restrictOnDelete();
            $table->foreignId('ended_event_id')->nullable()->constrained('closure_order_events')->restrictOnDelete();
            $table->unsignedBigInteger('open_state_id')
                ->nullable()
                ->storedAs('CASE WHEN `ended_at` IS NULL THEN `closure_order_state_id` ELSE NULL END');

            $table->index(['closure_order_state_id', 'started_at'], 'closure_state_period_state_started_index');
            $table->index(['holder_user_id', 'started_at'], 'closure_state_period_holder_user_started_index');
            $table->index(['holder_company_id', 'started_at'], 'closure_state_period_holder_company_started_index');
            $table->unique('open_state_id', 'closure_state_period_one_open_per_state');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('closure_order_state_periods');
        Schema::dropIfExists('closure_order_events');
        Schema::dropIfExists('closure_order_states');
        Schema::enableForeignKeyConstraints();
    }
};
