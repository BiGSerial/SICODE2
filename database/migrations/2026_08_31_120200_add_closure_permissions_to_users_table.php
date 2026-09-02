<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('closure_operator')->default(false)->after('legal_manager');
            $table->boolean('closure_manager')->default(false)->after('closure_operator');
        });

        DB::table('users')->where('superadm', true)->update([
            'closure_operator' => true,
            'closure_manager'  => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['closure_operator', 'closure_manager']);
        });
    }
};
