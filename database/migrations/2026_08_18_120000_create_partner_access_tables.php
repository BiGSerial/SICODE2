<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('partner_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name')->default('Padrão');
            $table->timestamps();

            $table->unique('company_id');
        });

        Schema::create('partner_role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('partner_role_id')->constrained('partner_roles')->cascadeOnDelete();
            $table->string('permission_key');
            $table->string('scope_type', 16);
            $table->boolean('enabled')->default(false);
            $table->timestamps();

            $table->unique(['partner_role_id', 'permission_key']);
            $table->index(['permission_key', 'enabled']);
        });

        Schema::create('partner_user_permission_exceptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('permission_key');
            $table->boolean('enabled')->default(false);
            $table->text('reason')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'company_id', 'permission_key'], 'partner_user_permission_unique');
            $table->index(['company_id', 'permission_key', 'enabled'], 'partner_user_permission_lookup');
        });

        Schema::create('partner_user_branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('andresscompanies')->cascadeOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'company_id', 'branch_id'], 'partner_user_branch_unique');
        });

        Schema::create('partner_admin_audit_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_admin_audit_events');
        Schema::dropIfExists('partner_user_branches');
        Schema::dropIfExists('partner_user_permission_exceptions');
        Schema::dropIfExists('partner_role_permissions');
        Schema::dropIfExists('partner_roles');
    }
};
