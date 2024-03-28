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
        Schema::table('viabilities', function (Blueprint $table) {
            $table->integer('status')->nullable();
            $table->boolean('hired')->default(false);
            $table->boolean('partner_ok')->default(false);
            $table->dateTime('partnerok_at')->nullable();
            $table->uuid('partner_id')->nullable();
            $table->dateTime('hired_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('viabilities', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('hired');
            $table->dropColumn('partner_ok');
            $table->dropColumn('partnerok_at');
            $table->dropColumn('partner_id');
            $table->dropColumn('hired_at');
        });
    }
};
