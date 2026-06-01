<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_demand_subdemands', function (Blueprint $table) {
            if (!Schema::hasColumn('legal_demand_subdemands', 'external_access_token_hash')) {
                $table->string('external_access_token_hash', 64)->nullable()->after('metadata');
                $table->timestamp('external_access_expires_at')->nullable()->after('external_access_token_hash');
                $table->timestamp('external_access_revoked_at')->nullable()->after('external_access_expires_at');
                $table->uuid('external_access_generated_by')->nullable()->after('external_access_revoked_at');
                $table->foreign('external_access_generated_by')->references('id')->on('users')->nullOnDelete();
                $table->index('external_access_token_hash', 'idx_legal_subdemands_external_token_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('legal_demand_subdemands', function (Blueprint $table) {
            if (Schema::hasColumn('legal_demand_subdemands', 'external_access_token_hash')) {
                $table->dropIndex('idx_legal_subdemands_external_token_hash');
                $table->dropForeign(['external_access_generated_by']);
                $table->dropColumn([
                    'external_access_token_hash',
                    'external_access_expires_at',
                    'external_access_revoked_at',
                    'external_access_generated_by',
                ]);
            }
        });
    }
};
