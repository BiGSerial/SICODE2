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
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('project')->default(false)->after('folder');
            $table->boolean('construction')->default(false)->after('project');
            $table->string('icon')->default('ri-asterisk')->after('construction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('construction');
            $table->dropColumn('project');
            $table->dropColumn('icon');
        });
    }
};
