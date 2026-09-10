<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->string('disk', 50)->default('local')->after('path');
            $table->string('mime', 120)->nullable()->after('ext');
            $table->unsignedBigInteger('size')->nullable()->after('mime');
            $table->string('sha256', 64)->nullable()->after('size');
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn(['disk', 'mime', 'size', 'sha256']);
        });
    }
};
