<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('legal_controller')->default(false)->after('analyst');
            $table->boolean('legal_field')->default(false)->after('legal_controller');
            $table->boolean('legal_manager')->default(false)->after('legal_field');
        });

        DB::table('users')->where('superadm', true)->update([
            'legal_controller' => true,
            'legal_field'      => true,
            'legal_manager'    => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['legal_controller', 'legal_field', 'legal_manager']);
        });
    }
};
