<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('note_inform_flows', function (Blueprint $table): void {
            $table->string('op20_order_number', 80)->nullable()->after('order_number');
            $table->timestamp('op20_finished_at')->nullable()->after('op20_order_number');
            $table->decimal('ads_amount', 14, 2)->nullable()->after('ads_is_tacit');
        });
    }

    public function down(): void
    {
        Schema::table('note_inform_flows', function (Blueprint $table): void {
            $table->dropColumn(['op20_order_number', 'op20_finished_at', 'ads_amount']);
        });
    }
};
