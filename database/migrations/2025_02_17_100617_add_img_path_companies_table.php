<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('img_b_path')->nullable()->after('name');
            $table->string('img_w_path')->nullable()->after('img_b_path');
            $table->string('img_rb_path')->nullable()->after('img_w_path');
            $table->string('img_rw_path')->nullable()->after('img_rb_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('img_b_path');
            $table->dropColumn('img_w_path');
            $table->dropColumn('img_rb_path');
            $table->dropColumn('img_rw_path');
        });
    }
};
