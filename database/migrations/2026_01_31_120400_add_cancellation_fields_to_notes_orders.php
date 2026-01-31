<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->boolean('canceled')->default(false)->after('status');
            $table->timestamp('canceled_at')->nullable()->after('canceled');
            $table->foreignUuid('canceled_by')->nullable()->after('canceled_at')->constrained('users');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('canceled')->default(false)->after('statusUser');
            $table->timestamp('canceled_at')->nullable()->after('canceled');
            $table->foreignUuid('canceled_by')->nullable()->after('canceled_at')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('canceled_by');
            $table->dropColumn(['canceled', 'canceled_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('canceled_by');
            $table->dropColumn(['canceled', 'canceled_at']);
        });
    }
};
