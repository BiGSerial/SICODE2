<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('ads_requests', function (Blueprint $table) {
            $table->string('url', 255)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('ads_requests', function (Blueprint $table) {
            $table->dropColumn('url');
        });
    }
};
