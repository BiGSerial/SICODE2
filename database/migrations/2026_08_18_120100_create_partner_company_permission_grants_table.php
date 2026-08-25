<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('partner_company_permission_grants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('permission_key');
            $table->string('scope_type', 16);
            $table->boolean('enabled')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'permission_key'], 'partner_company_permission_grants_unique');
            $table->index(['permission_key', 'enabled'], 'partner_company_permission_grants_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_company_permission_grants');
    }
};
