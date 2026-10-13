<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('closure_order_states', function (Blueprint $table) {
            $table->boolean('priority')->default(false)->after('operational_status');
            $table->index(['priority', 'operational_status'], 'closure_state_priority_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('closure_order_states', function (Blueprint $table) {
            $table->dropIndex('closure_state_priority_status_index');
            $table->dropColumn('priority');
        });
    }
};
