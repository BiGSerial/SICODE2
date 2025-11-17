<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('protests', function (Blueprint $table) {
            $table->integer('type_note')->nullable();
        });

     

        DB::table('protests')->update(['type_note' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('protests', function (Blueprint $table) {
            $table->dropColumn('type_note');
        });
    }
};
