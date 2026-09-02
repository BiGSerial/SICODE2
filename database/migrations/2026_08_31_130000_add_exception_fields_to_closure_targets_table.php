<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('closure_targets', function (Blueprint $table) {
            $table->boolean('is_exception')->default(false)->after('entry_rule');
            $table->text('exception_reason')->nullable()->after('is_exception');
            $table->foreignUuid('requested_by')->nullable()->after('exception_reason')->constrained('users');
            $table->foreignUuid('authorized_by')->nullable()->after('requested_by')->constrained('users');
            $table->timestamp('authorized_at')->nullable()->after('authorized_by');

            $table->index('is_exception');
        });
    }

    public function down(): void
    {
        Schema::table('closure_targets', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropForeign(['authorized_by']);
            $table->dropColumn(['is_exception', 'exception_reason', 'requested_by', 'authorized_by', 'authorized_at']);
        });
    }
};
