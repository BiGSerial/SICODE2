<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('closure_order_states', 'closed_meta_type')) {
            Schema::table('closure_order_states', function (Blueprint $table) {
                $table->char('closed_meta_type', 1)->nullable()->after('closed_at');
                $table->char('closed_meta_period', 7)->nullable()->after('closed_meta_type');
            });
        }

        if (Schema::hasTable('closure_settings')) {
            return;
        }

        Schema::create('closure_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closure_settings');

        Schema::table('closure_order_states', function (Blueprint $table) {
            $table->dropColumn(['closed_meta_type', 'closed_meta_period']);
        });
    }
};
