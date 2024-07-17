<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('viabilities', function (Blueprint $table) {
            $table->bigInteger('note_id')->nullable();
            $table->boolean('visible_partner')->default(false);
            $table->boolean('rehired')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('viabilities', function (Blueprint $table) {
            $table->dropColumn('note_id');
            $table->dropColumn('visible_partner');
            $table->dropColumn('rehired');
        });
    }
};
